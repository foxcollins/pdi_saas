<?php

namespace App\Http\Controllers;

use App\Models\AiRun;
use App\Models\AnalyticsEvent;
use App\Models\Conversation;
use App\Models\KnowledgeDocument;
use App\Models\Lead;
use App\Models\Message;
use App\Services\Ai\AiUsageService;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $start = now()->startOfDay();

        return inertia('Dashboard', [
            'metrics' => [
                'conversations' => Conversation::count(),
                'open_conversations' => Conversation::where('status', 'open')->count(),
                'messages' => Message::count(),
                'messages_today' => Message::where('created_at', '>=', $start)->count(),
                'leads' => Lead::count(),
                'new_leads' => Lead::where('created_at', '>=', $start)->count(),
                'documents' => KnowledgeDocument::count(),
                'ready_documents' => KnowledgeDocument::where('status', 'ready')->count(),
                'ai_runs' => AiRun::count(),
                'ai_cost_month' => (float) AiRun::where('created_at', '>=', now()->startOfMonth())->sum('cost_usd'),
                'chat_messages' => AnalyticsEvent::where('kind', 'chat_message')->count(),
                'page_views' => AnalyticsEvent::where('kind', 'page_view')->count(),
                'unanswered_today' => AnalyticsEvent::where('kind', 'unanswered_question')->where('created_at', '>=', $start)->count(),
                'unanswered_total' => AnalyticsEvent::where('kind', 'unanswered_question')->count(),
            ],
            'ai_usage' => app(AiUsageService::class)->summary(tenant()),
            'unanswered_questions' => AnalyticsEvent::query()
                ->where('kind', 'unanswered_question')
                ->latest()
                ->limit(8)
                ->get()
                ->map(fn ($e) => [
                    'question' => $e->context['question'] ?? '',
                    'created_at' => $e->created_at,
                ]),
            'recent_conversations' => Conversation::query()
                ->with(['contact', 'messages' => fn ($q) => $q->latest()->limit(3)])
                ->latest()
                ->limit(5)
                ->get()
                ->map(fn ($c) => [
                    'id' => $c->id,
                    'contact' => $c->contact?->name,
                    'channel' => $c->channel,
                    'status' => $c->status,
                    'preview' => $c->messages->first()?->content,
                    'created_at' => $c->created_at,
                ]),
        ]);
    }
}
