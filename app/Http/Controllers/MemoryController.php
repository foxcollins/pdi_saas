<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Services\Memory\MemoryService;
use Illuminate\Http\Request;

class MemoryController extends Controller
{
    public function index()
    {
        $contacts = Contact::query()
            ->with('memoryEntries')
            ->latest('last_activity_at')
            ->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name ?: 'Visitante',
                'email' => $c->email,
                'consent_status' => $c->consent_status,
                'memory_summary' => $c->memory_summary ?: [],
                'entries_count' => $c->memoryEntries->count(),
                'last_activity_at' => $c->last_activity_at,
            ])
            ->filter(fn ($c) => $c['entries_count'] > 0)
            ->values();

        $retentionDays = config('memory.retention_days');

        return inertia('Memory', [
            'contacts' => $contacts,
            'retention_days' => $retentionDays,
        ]);
    }

    public function setConsent(Request $request, string $contactId)
    {
        $data = $request->validate(['consent_status' => ['required', 'in:granted,withdrawn']]);

        app(MemoryService::class)->setConsent(Contact::findOrFail($contactId), $data['consent_status']);

        return back()->with('success', 'Consentimiento actualizado.');
    }

    public function forget(string $contactId)
    {
        app(MemoryService::class)->forget(Contact::findOrFail($contactId), true);

        return back()->with('success', 'Datos del contacto anonimizados y memoria eliminada.');
    }

    public function prune(Request $request)
    {
        $data = $request->validate(['days' => ['nullable', 'integer', 'min:1']]);

        $pruned = app(MemoryService::class)->pruneExpired($data['days'] ?? null);

        return back()->with('success', "Memoria podada: {$pruned} registros.");
    }
}
