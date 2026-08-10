<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $tenants = Tenant::query()->get();

        foreach ($tenants as $tenant) {
            TenantContext::set($tenant->id);

            $profile = $tenant->profile()->withoutGlobalScopes()->first();

            if (! $profile) {
                continue;
            }

            $currency = $profile->contact['currency'] ?? $tenant->settings['currency'] ?? 'USD';

            foreach ($profile->products ?? [] as $index => $item) {
                $price = $this->extractPrice($item['price'] ?? null);

                if ($price === null) {
                    continue;
                }

                $existing = Product::query()->where('title', $item['title'])->first();

                if ($existing) {
                    continue;
                }

                Product::create([
                    'title' => $item['title'],
                    'description' => $item['description'] ?? null,
                    'price' => $price,
                    'currency' => $currency,
                    'unit' => 'unidad',
                    'category' => 'Catálogo',
                    'is_active' => true,
                ]);
            }
        }
    }

    private function extractPrice(mixed $raw): ?float
    {
        if ($raw === null) {
            return null;
        }

        if (is_numeric($raw)) {
            return (float) $raw;
        }

        preg_match('/\d[\d.,]*/', (string) $raw, $m);

        if (empty($m)) {
            return null;
        }

        return (float) str_replace(',', '', $m[0]);
    }
}
