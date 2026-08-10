<?php

namespace App\Services\Tools\Drivers;

use App\Models\Contact;
use App\Services\Quotes\QuoteService;
use App\Services\Tools\BaseTool;
use App\Services\Tools\ToolContext;

class CreateQuoteTool extends BaseTool
{
    public function name(): string
    {
        return 'create_quote';
    }

    public function description(): string
    {
        return 'Genera una cotización formal con items del catálogo, calcula totales y emite un PDF. Devuelve el número y el total.';
    }

    public function permission(): string
    {
        return 'internal';
    }

    public function parameters(): array
    {
        return [
            'items' => ['type' => 'array', 'required' => true, 'description' => 'Items calculados: [{product_id, title, quantity, unit_price, amount, currency}]'],
            'customer_name' => ['type' => 'string', 'required' => false, 'description' => 'Nombre del cliente (crea o usa el contacto existente).'],
            'customer_phone' => ['type' => 'string', 'required' => false, 'description' => 'Teléfono del cliente.'],
            'customer_email' => ['type' => 'string', 'required' => false, 'description' => 'Email del cliente.'],
            'notes' => ['type' => 'string', 'required' => false, 'description' => 'Notas de la cotización.'],
        ];
    }

    public function execute(array $args, ToolContext $context): array
    {
        $contact = $this->findOrCreateContact($context, $args);
        $service = app(QuoteService::class);

        $quote = $service->create($context->tenant, [
            'items' => $args['items'] ?? [],
            'notes' => $args['notes'] ?? null,
        ], $contact, $context->conversation);

        return [
            'quote_id' => $quote->id,
            'number' => $quote->number,
            'status' => $quote->status,
            'total' => (float) $quote->total,
            'currency' => $quote->currency,
            'pdf_path' => $quote->pdf_path,
        ];
    }

    private function findOrCreateContact(ToolContext $context, array $args): ?Contact
    {
        $email = trim((string) ($args['customer_email'] ?? ''));
        $phone = trim((string) ($args['customer_phone'] ?? ''));
        $name = trim((string) ($args['customer_name'] ?? ''));

        if ($email === '' && $phone === '') {
            return null;
        }

        $contact = Contact::query()
            ->when($email, fn ($q) => $q->orWhere('email', $email))
            ->when($phone, fn ($q) => $q->orWhere('phone', $phone))
            ->first();

        if (! $contact) {
            $contact = Contact::create([
                'name' => $name ?: 'Cliente',
                'email' => $email ?: null,
                'phone' => $phone ?: null,
                'last_activity_at' => now(),
            ]);
        }

        return $contact;
    }
}
