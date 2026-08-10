<?php

namespace App\Http\Controllers;

use App\Models\AiRun;
use App\Models\AnalyticsEvent;
use App\Models\Conversation;
use App\Models\Lead;
use App\Models\Message;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function __invoke()
    {
        $days = min(30, max(7, (int) request()->integer('days', 14)));

        return inertia('Analytics', [
            'range_days' => $days,
            'totals' => [
                'conversations' => Conversation::count(),
                'leads' => Lead::count(),
                'messages' => Message::count(),
                'ai_runs' => AiRun::count(),
                'ai_cost_month' => (float) AiRun::where('created_at', '>=', now()->startOfMonth())->sum('cost_usd'),
                'unanswered_total' => AnalyticsEvent::where('kind', 'unanswered_question')->count(),
            ],
            'by_channel' => Conversation::query()
                ->select('channel', DB::raw('count(*) as total'))
                ->groupBy('channel')
                ->orderByDesc('total')
                ->get()
                ->map(fn ($c) => ['channel' => $c->channel, 'total' => $c->total]),
            'leads_by_status' => Lead::query()
                ->select('status', DB::raw('count(*) as total'))
                ->groupBy('status')
                ->orderByDesc('total')
                ->get()
                ->map(fn ($l) => ['status' => $l->status, 'total' => $l->total]),
            'leads_by_source' => Lead::query()
                ->select('source_channel', DB::raw('count(*) as total'))
                ->groupBy('source_channel')
                ->orderByDesc('total')
                ->get()
                ->map(fn ($l) => ['source' => $l->source_channel, 'total' => $l->total]),
            'trend' => $this->trend($days),
        ]);
    }

    private function trend(int $days): array
    {
        $start = now()->startOfDay()->subDays($days - 1);

        $conversations = Conversation::query()
            ->select(DB::raw('date(created_at) as day'), DB::raw('count(*) as total'))
            ->where('created_at', '>=', $start)
            ->groupBy(DB::raw('date(created_at)'))
            ->pluck('total', 'day');

        $messages = Message::query()
            ->select(DB::raw('date(created_at) as day'), DB::raw('count(*) as total'))
            ->where('created_at', '>=', $start)
            ->groupBy(DB::raw('date(created_at)'))
            ->pluck('total', 'day');

        $leads = Lead::query()
            ->select(DB::raw('date(created_at) as day'), DB::raw('count(*) as total'))
            ->where('created_at', '>=', $start)
            ->groupBy(DB::raw('date(created_at)'))
            ->pluck('total', 'day');

        $unanswered = AnalyticsEvent::query()
            ->select(DB::raw('date(created_at) as day'), DB::raw('count(*) as total'))
            ->where('kind', 'unanswered_question')
            ->where('created_at', '>=', $start)
            ->groupBy(DB::raw('date(created_at)'))
            ->pluck('total', 'day');

        $rows = [];

        for ($i = 0; $i < $days; $i++) {
            $date = $start->copy()->addDays($i)->format('Y-m-d');
            $rows[] = [
                'date' => $date,
                'conversations' => (int) ($conversations[$date] ?? 0),
                'messages' => (int) ($messages[$date] ?? 0),
                'leads' => (int) ($leads[$date] ?? 0),
                'unanswered' => (int) ($unanswered[$date] ?? 0),
            ];
        }

        return $rows;
    }
}
