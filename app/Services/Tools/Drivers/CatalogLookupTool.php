<?php

namespace App\Services\Tools\Drivers;

use App\Models\Product;
use App\Services\Tools\BaseTool;
use App\Services\Tools\ToolContext;

class CatalogLookupTool extends BaseTool
{
    public function name(): string
    {
        return 'catalog_lookup';
    }

    public function description(): string
    {
        return 'Busca productos y servicios en el catálogo del negocio por palabras clave. Devuelve título, precio, moneda y unidad.';
    }

    public function permission(): string
    {
        return 'read';
    }

    public function parameters(): array
    {
        return [
            'query' => ['type' => 'string', 'required' => true, 'description' => 'Términos de búsqueda (nombre o categoría del producto).'],
            'limit' => ['type' => 'integer', 'required' => false, 'description' => 'Máximo de resultados (por defecto 5).'],
        ];
    }

    public function execute(array $args, ToolContext $context): array
    {
        $query = trim((string) ($args['query'] ?? ''));
        $limit = min((int) ($args['limit'] ?? 5), 20);

        if ($query === '') {
            return ['items' => [], 'count' => 0];
        }

        $terms = collect(preg_split('/\s+/', mb_strtolower($query)) ?? [])
            ->filter(fn ($t) => mb_strlen($t) >= 2)
            ->values();

        $products = Product::query()
            ->where('is_active', true)
            ->get()
            ->filter(function (Product $p) use ($terms) {
                if ($terms->isEmpty()) {
                    return true;
                }

                $haystack = mb_strtolower($p->title.' '.($p->description ?? '').' '.($p->category ?? ''));

                return $terms->contains(fn ($t) => str_contains($haystack, $t));
            })
            ->take($limit)
            ->map(fn (Product $p) => [
                'id' => $p->id,
                'title' => $p->title,
                'description' => $p->description,
                'price' => (float) $p->price,
                'currency' => $p->currency,
                'unit' => $p->unit,
                'category' => $p->category,
            ])
            ->values()
            ->all();

        return ['items' => $products, 'count' => count($products)];
    }
}
