<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Lead;
use App\Models\Note;
use App\Models\Task;
use Illuminate\Http\Request;

class CrmController extends Controller
{
    public function index()
    {
        $contacts = Contact::query()
            ->withCount(['conversations'])
            ->with(['conversations.messages', 'leads'])
            ->latest('last_activity_at')
            ->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name ?: 'Visitante',
                'email' => $c->email,
                'phone' => $c->phone,
                'lifecycle' => $c->lifecycle,
                'conversations_count' => $c->conversations_count,
                'last_activity_at' => $c->last_activity_at,
                'score' => $c->leads->max('lead_score'),
                'lead_status' => $c->leads->max('status') ?? null,
            ]);

        $pipeline = collect(['new', 'qualified', 'negotiation', 'won', 'lost'])
            ->mapWithKeys(fn ($status) => [
                $status => Lead::query()
                    ->where('status', $status)
                    ->with('contact')
                    ->latest()
                    ->get()
                    ->map(fn ($l) => [
                        'id' => $l->id,
                        'contact' => $l->contact?->name ?: 'Visitante',
                        'score' => $l->lead_score,
                        'intent' => $l->intent,
                    ]),
            ]);

        $inbox = Conversation::query()
            ->where('needs_human', true)
            ->with(['contact', 'messages' => fn ($q) => $q->latest()->limit(1)])
            ->latest('escalated_at')
            ->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'contact' => $c->contact?->name ?: 'Visitante',
                'subject' => $c->subject,
                'channel' => $c->channel,
                'escalated_at' => $c->escalated_at,
                'last_message' => $c->messages->first()?->content,
                'question' => $c->messages->first(fn ($m) => $m->direction === 'in')?->content,
            ]);

        $tasks = Task::query()
            ->with('contact')
            ->latest()
            ->get()
            ->map(fn ($t) => [
                'id' => $t->id,
                'title' => $t->title,
                'description' => $t->description,
                'status' => $t->status,
                'contact' => $t->contact?->name ?: null,
                'due_at' => $t->due_at,
            ]);

        $notes = Note::query()
            ->with('contact')
            ->latest()
            ->get()
            ->map(fn ($n) => [
                'id' => $n->id,
                'body' => $n->body,
                'contact' => $n->contact?->name ?: null,
                'created_at' => $n->created_at,
            ]);

        return inertia('Crm', [
            'contacts' => $contacts,
            'pipeline' => $pipeline,
            'inbox' => $inbox,
            'tasks' => $tasks,
            'notes' => $notes,
        ]);
    }

    public function showContact(string $contactId)
    {
        $contact = Contact::query()
            ->with(['conversations.messages', 'leads', 'notes', 'tasks'])
            ->findOrFail($contactId);

        return inertia('CrmContact', [
            'contact' => [
                'id' => $contact->id,
                'name' => $contact->name ?: 'Visitante',
                'email' => $contact->email,
                'phone' => $contact->phone,
                'lifecycle' => $contact->lifecycle,
                'consent_status' => $contact->consent_status,
                'last_activity_at' => $contact->last_activity_at,
                'conversations' => $contact->conversations
                    ->sortByDesc('started_at')
                    ->values()
                    ->map(fn ($c) => [
                        'id' => $c->id,
                        'channel' => $c->channel,
                        'subject' => $c->subject,
                        'status' => $c->status,
                        'messages' => $c->messages->map(fn ($m) => [
                            'direction' => $m->direction,
                            'author_type' => $m->author_type,
                            'content' => $m->content,
                            'created_at' => $m->created_at,
                        ]),
                    ]),
                'leads' => $contact->leads->map(fn ($l) => [
                    'id' => $l->id,
                    'source' => $l->source_channel,
                    'intent' => $l->intent,
                    'score' => $l->lead_score,
                    'status' => $l->status,
                ]),
                'notes' => $contact->notes->map(fn ($n) => [
                    'id' => $n->id,
                    'body' => $n->body,
                    'created_at' => $n->created_at,
                ]),
                'tasks' => $contact->tasks->map(fn ($t) => [
                    'id' => $t->id,
                    'title' => $t->title,
                    'description' => $t->description,
                    'status' => $t->status,
                    'due_at' => $t->due_at,
                ]),
            ],
        ]);
    }

    public function storeNote(Request $request)
    {
        $data = $request->validate([
            'contact_id' => ['required', 'string'],
            'body' => ['required', 'string', 'max:2000'],
        ]);

        Note::create([
            'contact_id' => $data['contact_id'],
            'author_id' => $request->user()?->id,
            'body' => $data['body'],
        ]);

        return back()->with('success', 'Nota agregada.');
    }

    public function storeTask(Request $request)
    {
        $data = $request->validate([
            'contact_id' => ['nullable', 'string'],
            'lead_id' => ['nullable', 'string'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'due_at' => ['nullable', 'date'],
        ]);

        Task::create([
            'contact_id' => $data['contact_id'] ?? null,
            'lead_id' => $data['lead_id'] ?? null,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'due_at' => $data['due_at'] ?? null,
            'status' => 'open',
        ]);

        return back()->with('success', 'Tarea creada.');
    }

    public function updateTask(Request $request, string $taskId)
    {
        $data = $request->validate([
            'status' => ['required', 'in:open,done'],
            'title' => ['nullable', 'string', 'max:255'],
        ]);

        Task::findOrFail($taskId)->update([
            'status' => $data['status'],
            'title' => $data['title'] ?? Task::findOrFail($taskId)->title,
        ]);

        return back()->with('success', 'Tarea actualizada.');
    }

    public function resolveConversation(string $conversationId)
    {
        Conversation::findOrFail($conversationId)->update([
            'needs_human' => false,
            'escalated_at' => null,
            'status' => 'resolved',
        ]);

        return back()->with('success', 'Conversación resuelta.');
    }
}
