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

        try {
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
            $rows = $this->keywordFallback($query, $k, $threshold);
        }

        $results = [];

        foreach ($rows as $row) {
            $results[] = [
                'id' => $row->id,
                'content' => $row->content,
                'source_ref' => $row->source_ref,
                'score' => round((float) $row->score, 4),
            ];
        }

        return $results;
    }

    public function hasKnowledge(): bool
    {
        return DB::table('knowledge_chunks')->exists();
    }

    private function keywordFallback(string $query, int $k, float $threshold): array
    {
        $terms = array_slice(explode(' ', mb_strtolower(preg_replace('/[^\pL\pN ]+/u', ' ', $query))), 0, 6);
        $terms = array_filter($terms, fn ($t) => mb_strlen($t) > 2);

        if (count($terms) === 0) {
            return [];
        }

        $sql = 'SELECT id, content, source_ref, 0.5 AS score
                FROM knowledge_chunks
                WHERE tenant_id = ?';
        $binds = [tenant_id()];

        foreach ($terms as $i => $term) {
            $sql .= ' AND content ILIKE ?';
            $binds[] = "%{$term}%";
        }

        $sql .= ' ORDER BY token_count ASC LIMIT '.((int) $k);

        return DB::select($sql, $binds);
    }

    private function vectorLiteral(array $vector): string
    {
        return '['.implode(',', $vector).']';
    }
}
