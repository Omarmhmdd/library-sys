<?php

namespace App\Services\AI;

use App\Models\Book;
use App\Models\BookEmbedding;
use App\Services\AI\Contracts\EmbeddingServiceInterface;

class BookEmbeddingService
{
    private bool $qdrantCollectionEnsured = false;

    public function __construct(
        private readonly EmbeddingServiceInterface $embedding,
        private readonly ?QdrantClient $qdrant = null
    ) {}

    public function textForBook(Book $book): string
    {
        $parts = array_filter([
            $book->title,
            $book->author,
            $book->description,
            $book->genre,
            $book->isbn ? "ISBN: {$book->isbn}" : null,
            $book->published_year ? "Published: {$book->published_year}" : null,
        ]);
        return implode('. ', $parts);
    }

    /** Minimum cosine similarity to count as "similar" (only show genuinely similar books, not the whole catalog). */
    private const MIN_SIMILARITY = 0.55;

    /**
     * @param array<float> $vector
     * @return array<int> book IDs
     */
    public function searchSimilar(array $vector, int $topK = 5, ?int $excludeBookId = null): array
    {
        if ($this->qdrant !== null) {
            $this->ensureQdrantCollection();
            $results = $this->qdrant->search($vector, $topK, $excludeBookId, self::MIN_SIMILARITY);
            return array_map(fn (array $r) => $r['id'], $results);
        }
        return $this->searchSimilarMySQL($vector, $topK, $excludeBookId);
    }

    public function upsertForBook(Book $book): void
    {
        $text = $this->textForBook($book);
        $vector = $this->embedding->embed($text);

        if ($this->qdrant !== null) {
            $this->ensureQdrantCollection();
            $this->qdrant->upsert($book->id, $vector);
            return;
        }

        BookEmbedding::updateOrCreate(
            ['book_id' => $book->id],
            ['embedding' => $vector]
        );
    }

    public function deleteForBook(int $bookId): void
    {
        if ($this->qdrant !== null) {
            $this->qdrant->delete($bookId);
            return;
        }
        BookEmbedding::where('book_id', $bookId)->delete();
    }

    private function ensureQdrantCollection(): void
    {
        if ($this->qdrantCollectionEnsured) {
            return;
        }
        $this->qdrant->ensureCollection();
        $this->qdrantCollectionEnsured = true;
    }

    /**
     * @param array<float> $vector
     * @return array<int>
     */
    private function searchSimilarMySQL(array $vector, int $topK, ?int $excludeBookId): array
    {
        $query = BookEmbedding::query();
        if ($excludeBookId !== null) {
            $query->where('book_id', '!=', $excludeBookId);
        }
        $rows = $query->get();
        if ($rows->isEmpty()) {
            return [];
        }
        $scores = [];
        foreach ($rows as $row) {
            $emb = $row->embedding;
            if (! is_array($emb)) {
                continue;
            }
            $sim = $this->cosineSimilarity($vector, $emb);
            if ($sim >= self::MIN_SIMILARITY) {
                $scores[$row->book_id] = $sim;
            }
        }
        arsort($scores);
        return array_slice(array_keys($scores), 0, $topK);
    }

    /**
     * @param array<float> $a
     * @param array<float> $b
     */
    private function cosineSimilarity(array $a, array $b): float
    {
        $n = min(count($a), count($b));
        if ($n === 0) {
            return 0.0;
        }
        $dot = 0.0;
        $normA = 0.0;
        $normB = 0.0;
        for ($i = 0; $i < $n; $i++) {
            $dot += $a[$i] * $b[$i];
            $normA += $a[$i] * $a[$i];
            $normB += $b[$i] * $b[$i];
        }
        $denom = sqrt($normA) * sqrt($normB);
        return $denom > 0 ? $dot / $denom : 0.0;
    }
}
