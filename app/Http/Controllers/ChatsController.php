<?php

namespace App\Http\Controllers;

use App\Models\Conversation;

class ChatsController extends Controller
{
    public function index()
    {
        $conversations = Conversation::query()
            ->with(['contact', 'messages' => fn ($q) => $q->oldest()])
            ->latest()
            ->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'contact' => $c->contact?->name ?: 'Visitante',
                'email' => $c->contact?->email,
                'channel' => $c->channel,
                'status' => $c->status,
                'started_at' => $c->started_at,
                'messages' => $c->messages->map(fn ($m) => [
                    'direction' => $m->direction,
                    'author_type' => $m->author_type,
                    'content' => $m->content,
                    'created_at' => $m->created_at,
                ]),
            ]);

        return inertia('Chats', ['conversations' => $conversations]);
    }
}
