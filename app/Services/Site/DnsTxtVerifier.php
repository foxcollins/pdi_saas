<?php

namespace App\Services\Site;

use Illuminate\Support\Facades\Http;

class DnsTxtVerifier
{
    /**
     * Comprueba que el registro TXT `_pdi-verify.<host>` contiene el token esperado.
     * Usa DNS-over-HTTPS (agnóstico del resolver local) con fallback a dns_get_record.
     */
    public function verifyTxt(string $host, string $token): bool
    {
        $recordName = $this->recordName($host);

        $values = $this->resolveTxt($recordName);

        $prefix = config('site.domain_verification.txt_prefix', 'pdi-verify');
        $prefixed = "{$prefix}={$token}";

        return in_array($token, $values, true) || in_array($prefixed, $values, true);
    }

    /**
     * @return string[]
     */
    public function resolveTxt(string $recordName): array
    {
        if (config('site.domain_verification.doh_enabled')) {
            try {
                $values = $this->viaDoh($recordName);
                if ($values !== []) {
                    return $values;
                }
            } catch (\Throwable $e) {
                logger()->warning('DoH TXT lookup falló, se usa fallback nativo', [
                    'record' => $recordName,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if (config('site.domain_verification.native_enabled')) {
            return $this->viaNative($recordName);
        }

        return [];
    }

    public function recordName(string $host): string
    {
        $prefix = config('site.domain_verification.record_name', '_pdi-verify');
        $host = mb_strtolower(trim($host));

        return "{$prefix}.{$host}";
    }

    /**
     * @return string[]
     */
    private function viaDoh(string $recordName): array
    {
        $url = rtrim((string) config('site.domain_verification.doh_url', 'https://cloudflare-dns.com/dns-query'), '/')
            .'?name='.rawurlencode($recordName).'&type=TXT';

        $response = Http::timeout(5)
            ->withHeaders(['accept' => 'application/dns-json'])
            ->get($url);

        if (! $response->successful()) {
            return [];
        }

        $payload = $response->json();

        $answers = $payload['Answer'] ?? [];

        return collect($answers)
            ->where('type', 16)
            ->pluck('data')
            ->map(fn ($data) => trim($data, '"'))
            ->all();
    }

    /**
     * @return string[]
     */
    private function viaNative(string $recordName): array
    {
        if (! function_exists('dns_get_record')) {
            return [];
        }

        $records = @dns_get_record($recordName, DNS_TXT);

        if ($records === false) {
            return [];
        }

        return collect($records)
            ->where('type', 'TXT')
            ->flatMap(fn ($record) => $record['entries'] ?? [])
            ->map(fn ($value) => trim($value, '"'))
            ->all();
    }
}
