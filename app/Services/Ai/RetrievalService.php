<?php

namespace App\Services\Ai;

use Illuminate\Support\Facades\DB;
use Throwable;

class RetrievalService
{
    public function search(string $query, ?int $k = null): array
    {
        $k = $k ?? (int) config('ai.retrieval_k', 5);
        $threshold = (float) config('ai.confidence_threshold', 0.16);

        if (ai()->isFake()) {
            $threshold = min($threshold, 0.10);
        }

        try {
            if (config('ai.embedding_provider') === 'fake') {
                return $this->normalize($this->keywordFallback($query, $k), $k);
            }

            $vector = ai()->queryEmbedding($query);

            $rows = DB::select(
                <<<'SQL'
                SELECT id, content, source_ref,
                       1 - (embedding <=> ?) AS score
                FROM knowledge_chunks
                WHERE tenant_id = ?
                  AND 1 - (embedding <=> ?) > ?
                ORDER BY embedding <=> ?
                LIMIT ?
                SQL,
                [$this->vectorLiteral($vector), tenant_id(), $this->vectorLiteral($vector), $threshold, $this->vectorLiteral($vector), $k]
            );
        } catch (Throwable $e) {
            $rows = $this->keywordFallback($query, $k);
        }

        $results = $this->normalize($rows, $k);

        if ($results === [] && $this->hasChunks()) {
            $results = $this->normalize($this->keywordFallback($query, $k), $k);
        }

        return $results;
    }

    public function hasKnowledge(): bool
    {
        return DB::table('knowledge_chunks')
            ->where('tenant_id', tenant_id())
            ->exists();
    }

    private function hasChunks(): bool
    {
        return $this->hasKnowledge();
    }

    /**
     * @param  array<int, object>  $rows
     */
    private function normalize(array $rows, int $k): array
    {
        $results = [];

        foreach (array_slice($rows, 0, $k) as $row) {
            $results[] = [
                'id' => $row->id,
                'content' => $row->content,
                'source_ref' => $row->source_ref,
                'score' => round((float) $row->score, 4),
            ];
        }

        return $results;
    }

    private function keywordFallback(string $query, int $k): array
    {
        $terms = array_slice(explode(' ', mb_strtolower(preg_replace('/[^\pL\pN ]+/u', ' ', $query))), 0, 8);
        $terms = array_filter($terms, fn ($t) => mb_strlen($t) > 2);

        if (count($terms) === 0) {
            return [];
        }

        $sql = 'SELECT id, content, source_ref, 0.5 AS score, ('
            .implode(' + ', array_fill(0, count($terms), 'CASE WHEN content ILIKE ? THEN 1 ELSE 0 END'))
            .') AS hits
                FROM knowledge_chunks
                WHERE tenant_id = ? AND ('
            .implode(' OR ', array_fill(0, count($terms), 'content ILIKE ?'))
            .')
                ORDER BY hits DESC, token_count ASC
                LIMIT '.((int) $k);

        $binds = [];

        foreach ($terms as $i => $term) {
            $binds[] = "%{$term}%";
        }

        $binds[] = tenant_id();

        foreach ($terms as $term) {
            $binds[] = "%{$term}%";
        }

        return DB::select($sql, $binds);
    }

    private function vectorLiteral(array $vector): string
    {
        return '['.implode(',', $vector).']';
    }
}
