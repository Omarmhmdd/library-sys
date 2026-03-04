<?php

namespace App\Console\Commands;

use App\Models\Book;
use App\Services\AI\BookEmbeddingService;
use Illuminate\Console\Command;

class IndexBookEmbeddings extends Command
{
    protected $signature = 'books:index-embeddings';

    protected $description = 'Build or rebuild embeddings for all books (requires OPENAI_API_KEY or COHERE_API_KEY)';

    public function handle(BookEmbeddingService $service): int
    {
        if (! config('openai.api_key') && ! config('cohere.api_key')) {
            $this->error('Set OPENAI_API_KEY or COHERE_API_KEY in .env');
            return self::FAILURE;
        }
        $books = Book::all();
        $bar = $this->output->createProgressBar($books->count());
        $bar->start();
        foreach ($books as $book) {
            try {
                $service->upsertForBook($book);
            } catch (\Throwable $e) {
                $this->newLine();
                $this->warn("Book {$book->id} ({$book->title}): " . $e->getMessage());
            }
            $bar->advance();
        }
        $bar->finish();
        $this->newLine();
        $this->info('Done.');
        return self::SUCCESS;
    }
}
