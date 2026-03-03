<?php

namespace App\Services\AI\Contracts;

interface VectorStoreInterface
{
    /**
     * Upsert one or more vectors with metadata (e.g. book id).
     *
     * @param array<int, array<float>> $vectors
     * @param array<int, array<string, mixed>> $metadatas
     */
    public function upsert(array $vectors, array $metadatas): void;

    /**
     * Search by vector, return list of matching ids (or id + score).
     *
     * @param array<float> $vector
     * @return array<array{id: string|int, score?: float}>
     */
    public function search(array $vector, int $topK = 5): array;

    /**
     * Delete vectors by external ids (e.g. book ids).
     *
     * @param array<int|string> $ids
     */
    public function deleteByIds(array $ids): void;
}
