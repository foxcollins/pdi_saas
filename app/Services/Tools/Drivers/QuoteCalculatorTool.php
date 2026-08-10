<?php

namespace App\Services\Tools\Drivers;

use App\Models\Product;
use App\Services\Tools\BaseTool;
use App\Services\Tools\ToolContext;

class QuoteCalculatorTool extends BaseTool
{
    public function name(): string
    {
        return 'quote_calculator';
    }

    public function description(): string
    {
        return 'Calcula una cotización a partir de una lista de productos (ids o nombres) con cantidades. Devuelve subtotal, impuestos y total.';
    }

    public function permission(): string
    {
        return 'read';
    }

    public function parameters(): array
    {
        return [
            'items' => ['type' => 'array', 'required' => true, 'description' => 'Lista de items: [{product_id|title, quantity}]'],
            'tax_rate' => ['type' => 'number', 'required' => false, 'description' => 'Tasa de impuesto en % (por defecto la del tenant, 0).'],
        ];
    }

    public function execute(array $args, ToolContext $context): array
    {
        $taxRate = (float) ($args['tax_rate'] ?? $this->tenantTaxRate($context));
        $lines = $this->resolveItems($args['items'] ?? [], $context);

        $subtotal = 0.0;
        $items = [];

        foreach ($lines as $line) {
            $price = (float) $line['price'];
            $qty = max(1, (int) $line['quantity']);
            $amount = round($price * $qty, 2);
            $subtotal += $amount;

            $items[] = [
                'product_id' => $line['id'] ?? null,
                'title' => $line['title'],
                'quantity' => $qty,
                'unit_price' => $price,
                'amount' => $amount,
                'currency' => $line['currency'],
            ];
        }

        $taxAmount = round($subtotal * ($taxRate / 100), 2);
        $total = round($subtotal + $taxAmount, 2);

        return [
            'items' => $items,
            'subtotal' => $subtotal,
            'tax_rate' => $taxRate,
            'tax_amount' => $taxAmount,
            'total' => $total,
            'currency' => $items[0]['currency'] ?? $context->tenant->settings['currency'] ?? 'USD',
        ];
    }

    private function resolveItems(array $raw, ToolContext $context): array
    {
        $results = [];

        foreach ($raw as $line) {
            $qty = (int) ($line['quantity'] ?? 1);
            $product = null;

            if (! empty($line['product_id'])) {
                $product = Product::query()->find($line['product_id']);
            } elseif (! empty($line['title'])) {
                $product = Product::query()
                    ->where('is_active', true)
                    ->whereRaw('lower(title) = ?', [mb_strtolower($line['title'])])
                    ->first();
            }

            if ($product) {
                $results[] = [
                    'id' => $product->id,
                    'title' => $product->title,
                    'price' => (float) $product->price,
                    'currency' => $product->currency,
                    'quantity' => $qty,
                ];
            }
        }

        return $results;
    }

    private function tenantTaxRate(ToolContext $context): float
    {
        return (float) ($context->tenant->settings['tax_rate'] ?? 0);
    }
}
