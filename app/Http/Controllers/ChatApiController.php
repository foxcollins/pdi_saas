<?php

namespace App\Http\Controllers;

use App\Services\Chat\ChatService;
use Illuminate\Http\Request;

class ChatApiController extends Controller
{
    public function stream(Request $request, string $slug)
    {
        $message = (string) $request->input('message', '');

        if (mb_strlen($message) < 1 || mb_strlen($message) > 2000) {
            return response()->json(['error' => 'Mensaje inválido.'], 422);
        }

        $visitor = array_filter($request->only(['name', 'email', 'phone']), fn ($v) => is_string($v) && $v !== '');

        return response()->stream(function () use ($slug, $message, $visitor) {
            $this->sse(['type' => 'start']);

            try {
                $chat = app(ChatService::class);

                $result = $chat->respond($slug, $message, $visitor, function ($chunk) {
                    $this->sse(['type' => 'chunk', 'text' => $chunk]);
                });

                $this->sse(['type' => 'done', 'sources' => $result['sources']]);
            } catch (\Throwable $e) {
                report($e);
                $this->sse(['type' => 'error', 'message' => 'Ocurrió un error al procesar la consulta.']);
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
            'Connection' => 'keep-alive',
        ]);
    }

    private function sse(array $payload): void
    {
        echo 'data: '.json_encode($payload)."\n\n";
        @ob_flush();
        flush();
    }
}
