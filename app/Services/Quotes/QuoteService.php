<?php

namespace App\Services\Quotes;

use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Quote;
use App\Models\Tenant;
use Illuminate\Support\Facades\Storage;

class QuoteService
{
    public function __construct(private QuotePdfGenerator $pdf) {}

    public function create(Tenant $tenant, array $data, ?Contact $contact = null, ?Conversation $conversation = null): Quote
    {
        $number = $this->nextNumber($tenant);
        $taxRate = (float) ($data['tax_rate'] ?? $tenant->settings['tax_rate'] ?? 0);
        $currency = $data['currency'] ?? $tenant->settings['currency'] ?? 'USD';

        $items = $data['items'] ?? [];
        $subtotal = collect($items)->sum(fn ($i) => (float) $i['amount']);
        $taxAmount = round($subtotal * ($taxRate / 100), 2);
        $total = round($subtotal + $taxAmount, 2);

        $quote = Quote::create([
            'tenant_id' => $tenant->id,
            'contact_id' => $contact?->id,
            'conversation_id' => $conversation?->id,
            'number' => $number,
            'status' => $data['status'] ?? 'draft',
            'subtotal' => $subtotal,
            'tax_rate' => $taxRate,
            'tax_amount' => $taxAmount,
            'total' => $total,
            'currency' => $currency,
            'items' => $items,
            'notes' => $data['notes'] ?? null,
            'created_by' => $data['created_by'] ?? null,
        ]);

        $pdf = $this->pdf->generate($quote);
        $path = "quotes/{$quote->tenant_id}/".$this->pdf->fileName($quote);

        Storage::disk('local')->put($path, $pdf);

        $quote->update(['pdf_path' => $path]);

        return $quote;
    }

    public function nextNumber(Tenant $tenant): string
    {
        $last = Quote::query()
            ->where('tenant_id', $tenant->id)
            ->where('number', 'like', 'Q-'.$tenant->slug.'-%')
            ->orderByDesc('number')
            ->value('number');

        $seq = 1;

        if ($last) {
            $seq = ((int) substr($last, strrpos($last, '-') + 1)) + 1;
        }

        return 'Q-'.$tenant->slug.'-'.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}
