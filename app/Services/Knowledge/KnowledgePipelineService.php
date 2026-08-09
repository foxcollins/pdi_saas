<?php

namespace App\Services\Knowledge;

use App\Models\KnowledgeChunk;
use App\Models\KnowledgeDocument;
use App\Models\KnowledgeSource;
use App\Models\Tenant;
use GuzzleHttp\Client;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Smalot\PdfParser\Parser as PdfParser;
use Throwable;

class KnowledgePipelineService
{
    public const CHUNK_SIZE = 600;
    public const CHUNK_OVERLAP = 100;

    public function createFromUpload(Tenant $tenant, UploadedFile $file, string $title): KnowledgeDocument
    {
        $key = Storage::disk('local')->putFile('knowledge/'.Str::slug($tenant->slug), $file);

        $source = KnowledgeSource::create([
            'tenant_id' => $tenant->id,
            'type' => 'upload',
            'title' => $title ?: $file->getClientOriginalName(),
            'status' => 'pending',
        ]);

        return KnowledgeDocument::create([
            'tenant_id' => $tenant->id,
            'source_id' => $source->id,
            'filename' => $file->getClientOriginalName(),
            'mime' => $file->getMimeType(),
            'storage_key' => $key,
            'status' => 'pending',
        ]);
    }

    public function createFromUrl(Tenant $tenant, string $url, string $title): KnowledgeDocument
    {
        $source = KnowledgeSource::create([
            'tenant_id' => $tenant->id,
            'type' => 'url',
            'title' => $title ?: $url,
            'status' => 'pending',
            'meta' => ['url' => $url],
        ]);

        return KnowledgeDocument::create([
            'tenant_id' => $tenant->id,
            'source_id' => $source->id,
            'filename' => $title ?: $url,
            'mime' => 'text/html',
            'storage_key' => $url,
            'status' => 'pending',
        ]);
    }

    public function createFromText(Tenant $tenant, string $title, string $text, string $type = 'manual'): KnowledgeDocument
    {
        $key = 'knowledge/'.Str::slug($tenant->slug).'/'.Str::slug($title).'-'.Str::uuid().'.txt';

        Storage::disk('local')->put($key, $text);

        $source = KnowledgeSource::create([
            'tenant_id' => $tenant->id,
            'type' => $type,
            'title' => $title,
            'status' => 'pending',
        ]);

        return KnowledgeDocument::create([
            'tenant_id' => $tenant->id,
            'source_id' => $source->id,
            'filename' => $title,
            'mime' => 'text/plain',
            'storage_key' => $key,
            'status' => 'pending',
        ]);
    }

    public function process(KnowledgeDocument $document): void
    {
        $document->update(['status' => 'processing', 'error' => null]);

        try {
            $text = match (true) {
                $document->source->type === 'url' => $this->extractUrl($document->storage_key),
                $document->mime === 'application/pdf' => $this->extractPdf($document),
                default => $this->extractStoredText($document),
            };

            $text = $this->clean($text);

            if (mb_strlen($text) < 20) {
                throw new \RuntimeException('No se pudo extraer contenido legible del documento.');
            }

            $chunks = $this->chunk($text);

            $embeddings = ai()->embed(array_column($chunks, 'content'));
            $model = $this->embeddingModel();

            DB::transaction(function () use ($document, $chunks, $embeddings, $model) {
                $document->chunks()->delete();

                foreach ($chunks as $i => $chunk) {
                    KnowledgeChunk::create([
                        'tenant_id' => $document->tenant_id,
                        'document_id' => $document->id,
                        'chunk_index' => $i,
                        'content' => $chunk['content'],
                        'token_count' => $chunk['tokens'],
                        'source_ref' => $document->filename.' · fragmento '.($i + 1),
                        'embedding' => $embeddings[$i] ?? null,
                    ]);
                }
            });

            $dimensions = count($embeddings[0] ?? []);
            $document->update([
                'status' => 'ready',
                'chunk_count' => count($chunks),
                'embedding_model' => $model,
                'embedding_dimensions' => $dimensions,
            ]);

            $document->source->update(['status' => 'ready']);
        } catch (Throwable $e) {
            $document->update(['status' => 'error', 'error' => $e->getMessage()]);
            $document->source->update(['status' => 'error']);

            report($e);
        }
    }

    private function extractPdf(KnowledgeDocument $document): string
    {
        $path = Storage::disk('local')->path($document->storage_key);

        if (! file_exists($path)) {
            throw new \RuntimeException('Archivo no encontrado en el almacenamiento.');
        }

        $parser = new PdfParser;

        return $parser->parseFile($path)->getText();
    }

    private function extractStoredText(KnowledgeDocument $document): string
    {
        if (! $document->storage_key) {
            throw new \RuntimeException('El documento no tiene contenido almacenado.');
        }

        return Storage::disk('local')->get($document->storage_key);
    }

    private function extractUrl(string $url): string
    {
        $client = new Client(['timeout' => 20, 'http_errors' => false, 'verify' => false]);

        $response = $client->get($url, ['headers' => ['User-Agent' => 'PDI-SAAS-Bot/1.0']]);

        if ($response->getStatusCode() !== 200) {
            throw new \RuntimeException("La URL respondió con estado {$response->getStatusCode()}.");
        }

        $html = (string) $response->getBody();

        $html = preg_replace('/<script.*?<\/script>|<style.*?<\/style>/si', ' ', $html);

        $html = preg_replace('/<(h[1-6]|p|li|br)[^>]*>/i', "\n", $html);
        $text = trim(strip_tags($html));

        return html_entity_decode($text, ENT_QUOTES, 'UTF-8');
    }

    private function clean(string $text): string
    {
        $text = preg_replace('/[\r\t]+/', ' ', $text);
        $text = preg_replace('/[ \t]+/', ' ', $text);
        $text = preg_replace('/\n\s*\n+/', "\n\n", $text);

        return trim($text);
    }

    private function chunk(string $text): array
    {
        $paragraphs = preg_split('/\n\s*\n/', $text);
        $chunks = [];
        $buffer = '';

        foreach ($paragraphs as $paragraph) {
            $paragraph = trim($paragraph);

            if ($paragraph === '') {
                continue;
            }

            if (mb_strlen($paragraph) > self::CHUNK_SIZE) {
                if ($buffer !== '') {
                    $chunks[] = ['content' => $buffer, 'tokens' => $this->tokens($buffer)];
                    $buffer = '';
                }

                $chunks = array_merge($chunks, $this->splitParagraph($paragraph));

                continue;
            }

            if (mb_strlen($buffer) + mb_strlen($paragraph) > self::CHUNK_SIZE && $buffer !== '') {
                $chunks[] = ['content' => $buffer, 'tokens' => $this->tokens($buffer)];
                $buffer = mb_substr($buffer, -self::CHUNK_OVERLAP);
            }

            $buffer = trim($buffer.' '.$paragraph);
        }

        if ($buffer !== '') {
            $chunks[] = ['content' => $buffer, 'tokens' => $this->tokens($buffer)];
        }

        return $chunks;
    }

    private function splitParagraph(string $paragraph): array
    {
        $chunks = [];
        $length = mb_strlen($paragraph);

        for ($offset = 0; $offset < $length; $offset += self::CHUNK_SIZE - self::CHUNK_OVERLAP) {
            $piece = mb_substr($paragraph, $offset, self::CHUNK_SIZE);

            if ($piece === '') {
                break;
            }

            $chunks[] = ['content' => $piece, 'tokens' => $this->tokens($piece)];
        }

        return $chunks;
    }

    private function tokens(string $text): int
    {
        return (int) ceil(mb_strlen($text) / 4);
    }

    private function embeddingModel(): string
    {
        $provider = config('ai.default_provider');

        return config("ai.providers.{$provider}.embedding_model", 'text-embedding-3-small');
    }
}
