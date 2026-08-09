<?php

namespace App\Http\Controllers;

use App\Models\AnalyticsEvent;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Lead;
use App\Models\Message;
use App\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Http\Request;

class ContactApiController extends Controller
{
    public function store(Request $request, string $slug)
    {
        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:60'],
            'subject' => ['nullable', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:3000'],
        ]);

        if (! ($data['email'] ?? null) && ! ($data['phone'] ?? null) && ! ($data['name'] ?? null)) {
            return response()->json(['error' => 'Indica al menos un medio de contacto.'], 422);
        }

        $tenant = Tenant::query()->where('slug', $slug)->first();

        if (! $tenant) {
            return response()->json(['error' => 'Tenant no encontrado.'], 404);
        }

        TenantContext::set($tenant->id);

        $contact = Contact::query()
            ->when($data['email'] ?? null, fn ($q) => $q->orWhere('email', $data['email']))
            ->when($data['phone'] ?? null, fn ($q) => $q->orWhere('phone', $data['phone']))
            ->first();

        if (! $contact) {
            $contact = Contact::create([
                'name' => $data['name'] ?? 'Visitante',
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'last_activity_at' => now(),
            ]);
        } else {
            $contact->update([
                'name' => $data['name'] ?? $contact->name,
                'email' => $data['email'] ?? $contact->email,
                'phone' => $data['phone'] ?? $contact->phone,
                'last_activity_at' => now(),
            ]);
        }

        $conversation = Conversation::create([
            'contact_id' => $contact->id,
            'channel' => 'web',
            'subject' => $data['subject'] ?? 'Formulario de contacto',
            'status' => 'open',
            'started_at' => now(),
        ]);

        Message::create([
            'conversation_id' => $conversation->id,
            'direction' => 'in',
            'author_type' => 'visitor',
            'content' => $data['message'],
        ]);

        Lead::create([
            'contact_id' => $contact->id,
            'source_channel' => 'web',
            'intent' => $data['subject'] ?? 'contact_form',
            'lead_score' => 20,
            'status' => 'new',
            'next_action' => 'Contactar al visitante',
        ]);

        AnalyticsEvent::create(['kind' => 'lead_generated', 'context' => ['contact_id' => $contact->id]]);

        return response()->json(['ok' => true, 'message' => 'Mensaje enviado. Te contactaremos pronto.']);
    }
}
