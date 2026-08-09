<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessKnowledgeDocument;
use App\Models\KnowledgeDocument;
use App\Models\KnowledgeSource;
use App\Services\Knowledge\KnowledgePipelineService;
use Illuminate\Http\Request;

class KnowledgeController extends Controller
{
    public function show()
    {
        $sources = KnowledgeSource::query()
            ->with('documents')
            ->latest()
            ->get()
            ->map(fn ($s) => [
                'id' => $s->id,
                'type' => $s->type,
                'title' => $s->title,
                'status' => $s->status,
                'chunks' => $s->documents->sum('chunk_count'),
                'document_id' => $s->documents->first()?->id,
                'error' => $s->documents->first()?->error,
                'created_at' => $s->created_at,
            ]);

        return inertia('Knowledge', ['sources' => $sources]);
    }

    public function upload(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'max:15360'],
            'title' => ['nullable', 'string', 'max:255'],
        ]);

        $pipeline = app(KnowledgePipelineService::class);

        $document = $pipeline->createFromUpload(tenant(), $request->file('file'), $request->title);

        dispatch(new ProcessKnowledgeDocument($document));

        return back()->with('success', 'Documento en proceso.');
    }

    public function addUrl(Request $request)
    {
        $request->validate([
            'url' => ['required', 'url', 'max:500'],
            'title' => ['nullable', 'string', 'max:255'],
        ]);

        $pipeline = app(KnowledgePipelineService::class);

        $document = $pipeline->createFromUrl(tenant(), $request->url, $request->title);

        dispatch(new ProcessKnowledgeDocument($document));

        return back()->with('success', 'URL en proceso.');
    }

    public function addText(Request $request)
    {
        $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string', 'min:10'],
        ]);

        $pipeline = app(KnowledgePipelineService::class);

        $document = $pipeline->createFromText(tenant(), $request->title, $request->content, $request->input('type', 'manual'));

        dispatch(new ProcessKnowledgeDocument($document));

        return back()->with('success', 'Conocimiento agregado.');
    }

    public function reprocess(Request $request, string $documentId)
    {
        $document = KnowledgeDocument::findOrFail($documentId);

        dispatch(new ProcessKnowledgeDocument($document));

        return back()->with('success', 'Reprocesando documento.');
    }

    public function destroy(Request $request, string $sourceId)
    {
        KnowledgeSource::findOrFail($sourceId)->delete();

        return back()->with('success', 'Fuente eliminada.');
    }
}
