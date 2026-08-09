<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use Illuminate\Http\Request;

class LeadsController extends Controller
{
    public function index()
    {
        $leads = Lead::query()
            ->with('contact')
            ->latest()
            ->get()
            ->map(fn ($l) => [
                'id' => $l->id,
                'contact' => $l->contact?->name ?: 'Visitante',
                'email' => $l->contact?->email,
                'phone' => $l->contact?->phone,
                'source' => $l->source_channel,
                'intent' => $l->intent,
                'score' => $l->lead_score,
                'status' => $l->status,
                'created_at' => $l->created_at,
            ]);

        return inertia('Leads', ['leads' => $leads]);
    }

    public function updateStatus(Request $request, string $leadId)
    {
        $request->validate(['status' => ['required', 'in:new,qualified,negotiation,won,lost']]);

        Lead::findOrFail($leadId)->update(['status' => $request->status]);

        return back()->with('success', 'Estado del lead actualizado.');
    }
}
