<?php

namespace App\Services\Tools;

use App\Models\Agent;
use App\Models\Conversation;
use App\Models\Tenant;
use App\Services\Tools\Drivers\CatalogLookupTool;

class ToolOrchestrator
{
    public function __construct(private ToolManager $manager) {}

    /**
     * Detección ligera de intención de cotización por palabras clave.
     */
    public function wantsQuote(string $message): bool
    {
        return (bool) preg_match('/(cotiz|presupuest|cu[aá]nto cuesta|cu[aá]nto vale|precio de|quiero comprar|vender|comprar|adquirir|reserva el|pido cotiz)/iu', $message);
    }

    /**
     * Ejecuta el flujo de cotización: busca en catálogo, calcula, crea la cotización con PDF,
     * registra lead y notifica a N8N. Devuelve texto de respuesta para el chat.
     */
    public function runQuoteFlow(Tenant $tenant, Agent $agent, Conversation $conversation, array $visitor, string $message): ?string
    {
        $context = new ToolContext($tenant, $agent, $conversation, $visitor);

        if (! in_array('catalog_lookup', (array) $agent->tools, true)) {
            return null;
        }

        $lookup = new CatalogLookupTool;
        $catalog = $lookup->execute(['query' => $message], $context);

        $items = $catalog['items'] ?? [];

        if ($items === []) {
            return 'No tengo productos en el catálogo que coincidan con tu consulta. Puedo derivarte con un asesor para ayudarte mejor.';
        }

        $pick = collect($items)->take(3)->map(fn ($item) => [
            'product_id' => $item['id'],
            'title' => $item['title'],
            'quantity' => 1,
            'unit_price' => $item['price'],
            'amount' => round((float) $item['price'], 2),
            'currency' => $item['currency'],
        ])->values()->all();

        $calculator = $this->manager->run('quote_calculator', ['items' => $pick], $context);

        $quoteArgs = [
            'items' => $calculator['items'],
            'customer_name' => $visitor['name'] ?? '',
            'customer_phone' => $visitor['phone'] ?? '',
            'customer_email' => $visitor['email'] ?? '',
            'notes' => 'Generada automáticamente por el asistente.',
        ];

        $quote = $this->manager->run('create_quote', $quoteArgs, $context);

        $leadArgs = [
            'name' => $visitor['name'] ?? '',
            'phone' => $visitor['phone'] ?? '',
            'email' => $visitor['email'] ?? '',
            'intent' => 'cotizacion',
            'score' => 40,
        ];

        try {
            $this->manager->run('create_lead', $leadArgs, $context);
        } catch (ToolException $e) {
            // la tool create_lead puede no estar habilitada; no bloquea la cotización.
        }

        if (in_array('n8n_webhook', (array) $agent->tools, true)) {
            try {
                $this->manager->run('n8n_webhook', [
                    'event' => 'quote.created',
                    'payload' => ['number' => $quote['number'], 'total' => $quote['total'], 'currency' => $quote['currency']],
                ], $context);
            } catch (ToolException $e) {
                // el webhook puede requerir aprobación; se omite silenciosamente.
            }
        }

        return $this->formatQuoteReply($catalog['items'], $calculator, $quote);
    }

    private function formatQuoteReply(array $catalogItems, array $calculator, array $quote): string
    {
        $currency = $quote['currency'];
        $lines = collect($calculator['items'])
            ->map(fn ($i) => "- {$i['title']} x{$i['quantity']}: {$currency} ".number_format((float) $i['amount'], 2))
            ->implode("\n");

        $total = $currency.' '.number_format((float) $quote['total'], 2);

        return "He preparado una cotización para ti:\n\n{$lines}\n\nSubtotal: {$currency} ".number_format((float) $calculator['subtotal'], 2)."\nTotal: {$total}\n\nReferencia: {$quote['number']}. ¿Quieres que un asesor te contacte para confirmarla?";
    }
}
