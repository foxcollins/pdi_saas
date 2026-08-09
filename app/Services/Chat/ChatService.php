<?php

namespace App\Services\Chat;

use App\Models\Agent;
use App\Models\AnalyticsEvent;
use App\Models\BusinessProfile;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Lead;
use App\Models\Message;
use App\Models\Tenant;
use App\Services\Ai\RetrievalService;
use App\Support\TenantContext;
use Illuminate\Support\Str;

class ChatService
{
    public function __construct(private RetrievalService $retrieval) {}

    public function respond(string $tenantSlug, string $message, array $visitor = [], ?callable $onChunk = null): array
    {
        $tenant = Tenant::query()->where('slug', $tenantSlug)->firstOrFail();

        TenantContext::set($tenant->id);

        $profile = $tenant->profile;
        $agent = Agent::query()->where('slug', 'assistant')->first() ?? new Agent(['name' => $tenant->name]);

        $contact = $this->findOrCreateContact($tenant, $visitor);
        $conversation = $this->findOrCreateConversation($tenant, $contact);

        Message::create([
            'conversation_id' => $conversation->id,
            'direction' => 'in',
            'author_type' => 'visitor',
            'content' => $message,
        ]);

        $results = $this->retrieval->search($message);

        $system = $this->buildSystemPrompt($tenant, $profile, $agent, $results);
        $messages = $this->buildMessages($system, $conversation, $message);

        $reply = '';
        $onChunk = $onChunk ?: fn ($c) => null;

        ai()->chatStream($messages, function ($chunk) use (&$reply, $onChunk) {
            $reply .= $chunk;
            $onChunk($chunk);
        }, ['trigger' => 'chat']);

        Message::create([
            'conversation_id' => $conversation->id,
            'direction' => 'out',
            'author_type' => 'agent',
            'content' => $reply,
        ]);

        $contact->update(['last_activity_at' => now()]);

        AnalyticsEvent::create(['kind' => 'chat_message', 'context' => ['conversation_id' => $conversation->id]]);

        return [
            'reply' => $reply,
            'conversation_id' => $conversation->id,
            'sources' => array_map(fn ($r) => $r['source_ref'], array_filter($results, fn ($r) => $r['source_ref'])),
        ];
    }

    private function findOrCreateContact(Tenant $tenant, array $visitor): Contact
    {
        $email = trim($visitor['email'] ?? '');
        $phone = trim($visitor['phone'] ?? '');

        $contact = Contact::query()
            ->when($email, fn ($q) => $q->orWhere('email', $email))
            ->when($phone, fn ($q) => $q->orWhere('phone', $phone))
            ->first();

        if (! $contact) {
            $contact = Contact::create([
                'name' => trim($visitor['name'] ?? '') ?: 'Visitante',
                'email' => $email ?: null,
                'phone' => $phone ?: null,
                'last_activity_at' => now(),
            ]);

            Lead::create([
                'contact_id' => $contact->id,
                'source_channel' => 'web',
                'intent' => 'chat',
                'lead_score' => 10,
                'status' => 'new',
            ]);

            AnalyticsEvent::create(['kind' => 'lead_generated', 'context' => ['contact_id' => $contact->id]]);
        }

        return $contact;
    }

    private function findOrCreateConversation(Tenant $tenant, Contact $contact): Conversation
    {
        $conversation = $contact->conversations()
            ->where('channel', 'web')
            ->where('status', 'open')
            ->first();

        if (! $conversation) {
            $conversation = Conversation::create([
                'contact_id' => $contact->id,
                'channel' => 'web',
                'subject' => 'Conversación web',
                'status' => 'open',
                'started_at' => now(),
            ]);
        }

        return $conversation;
    }

    private function buildSystemPrompt(Tenant $tenant, ?BusinessProfile $profile, Agent $agent, array $results): string
    {
        $name = $profile?->name ?: $tenant->name;
        $industry = $profile?->industry ?: $tenant->industry;
        $description = $profile?->description;
        $services = collect($profile?->services ?: [])->pluck('title')->implode(', ');
        $contact = $profile?->contact ?: [];
        $phone = $contact['phone'] ?? $contact['whatsapp'] ?? '';

        $knowledge = collect($results)
            ->map(fn ($r) => '- '.$r['content'])
            ->implode("\n");

        $instructions = $agent->instructions ?: 'Responde en español, de forma amable, profesional y concisa.';

        $prompt = <<<PROMPT
        $instructions

        Eres el asistente virtual del sitio web de "$name" ({$industry}).
        Ayudas a los visitantes con dudas sobre servicios, productos, precios, horarios y contacto.

        INFORMACIÓN DE LA EMPRESA:
        - Descripción: {$description}
        - Servicios: {$services}
        - Contacto / teléfono: {$phone}

        CONOCIMIENTO:
        {$knowledge}

        REGLAS:
        1. Responde SOLO con la información proporcionada arriba.
        2. Si la consulta NO está cubierta por el conocimiento, responde que no tienes la información y deriva al formulario de contacto o al teléfono.
        3. Nunca inventes precios, horarios o datos que no estén en el conocimiento.
        4. Sé breve y claro.
        PROMPT;

        return $prompt;
    }

    private function buildMessages(string $system, Conversation $conversation, string $message): array
    {
        $history = $conversation->messages()
            ->orderByDesc('created_at')
            ->limit(6)
            ->get()
            ->reverse()
            ->map(fn (Message $m) => [
                'role' => $m->direction === 'in' ? 'user' : 'assistant',
                'content' => Str::limit($m->content, 500),
            ])
            ->values()
            ->all();

        return array_merge([['role' => 'system', 'content' => $system]], $history, [['role' => 'user', 'content' => $message]]);
    }
}
