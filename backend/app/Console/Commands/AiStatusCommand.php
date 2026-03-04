<?php

namespace App\Console\Commands;

use App\Models\BookEmbedding;
use App\Services\AI\QdrantClient;
use Illuminate\Console\Command;

class AiStatusCommand extends Command
{
    protected $signature = 'ai:status';

    protected $description = 'Check if AI is configured and how many vectors are stored (Qdrant or MySQL)';

    public function handle(): int
    {
        $hasOpenAI = (bool) config('openai.api_key');
        $hasCohere = (bool) config('cohere.api_key');

        if (! $hasOpenAI && ! $hasCohere) {
            $this->error('AI is NOT configured. Set OPENAI_API_KEY or COHERE_API_KEY in .env');
            return self::FAILURE;
        }

        $provider = $hasCohere ? 'Cohere' : 'OpenAI';
        $this->info("AI provider: {$provider}");

        $qdrantUrl = config('qdrant.url');
        if ($qdrantUrl) {
            $this->info('Vector DB: Qdrant @ ' . preg_replace('#^https?://([^/]+).*#', '$1', $qdrantUrl));
            try {
                $client = app(QdrantClient::class);
                $info = $client->collectionInfo();
                if ($info === null) {
                    $this->warn('Collection "' . config('qdrant.collection', 'books') . '" does not exist yet. Run: php artisan books:index-embeddings');
                } else {
                    $count = $info['result']['points_count'] ?? $info['points_count'] ?? '?';
                    $this->info('Qdrant collection "' . config('qdrant.collection', 'books') . '": ' . $count . ' points (book vectors).');
                }
            } catch (\Throwable $e) {
                $this->warn('Could not reach Qdrant: ' . $e->getMessage());
            }
        } else {
            $this->info('Vector DB: MySQL (book_embeddings table)');
            $count = BookEmbedding::count();
            $this->info('Stored embeddings: ' . $count);
            if ($count === 0) {
                $this->warn('No embeddings yet. Run: php artisan books:index-embeddings');
            }
        }

        $this->newLine();
        $this->comment('To view data in Qdrant Cloud: https://cloud.qdrant.io → your cluster → Cluster UI → collection "books" → Points tab.');
        return self::SUCCESS;
    }
}
