<?php

namespace App\Services\AI;

use App\Models\Book;
use App\Services\AI\Contracts\LlmServiceInterface;

class RagService
{
    public function __construct(
        private readonly \App\Services\AI\Contracts\EmbeddingServiceInterface $embedding,
        private readonly BookEmbeddingService $bookEmbedding,
        private readonly LlmServiceInterface $llm
    ) {}

    public function ask(string $question, int $maxBooks = 5): string
    {
        $queryVector = $this->embedding->embed($question, 'search_query');
        $bookIds = $this->bookEmbedding->searchSimilar($queryVector, $maxBooks);
        if (empty($bookIds)) {
            return 'No books in the catalog yet, or embeddings are not built. Add books and try again.';
        }
        $books = Book::whereIn('id', $bookIds)->get();
        $context = $books->map(fn (Book $b) => sprintf(
            '- %s by %s. %s',
            $b->title,
            $b->author,
            $b->description ? substr($b->description, 0, 200) . '...' : ''
        ))->implode("\n");

        $systemPrompt = 'You are a helpful library assistant. Answer only based on the following book catalog. If the answer is not in the catalog, say so. Be concise.';
        $prompt = "Catalog:\n" . $context . "\n\nQuestion: " . $question;

        return $this->llm->generate($prompt, $systemPrompt, 500);
    }
}
