<?php

namespace App\Providers;

use App\Services\AI\BookEmbeddingService;
use App\Services\AI\CohereEmbeddingService;
use App\Services\AI\CohereLlmService;
use App\Services\AI\MetadataSuggestionService;
use App\Services\AI\OpenAIEmbeddingService;
use App\Services\AI\OpenAILlmService;
use App\Services\AI\QdrantClient;
use App\Services\AI\RagService;
use App\Services\AI\Contracts\EmbeddingServiceInterface;
use App\Services\AI\Contracts\LlmServiceInterface;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $useCohere = (bool) config('cohere.api_key');

        if ($useCohere) {
            $this->app->singleton(EmbeddingServiceInterface::class, function () {
                return new CohereEmbeddingService(
                    config('cohere.api_key') ?: '',
                    config('cohere.embed_model', 'embed-english-v3.0')
                );
            });
            $this->app->singleton(LlmServiceInterface::class, function () {
                return new CohereLlmService(
                    config('cohere.api_key') ?: '',
                    config('cohere.chat_model', 'command')
                );
            });
        } else {
            $this->app->singleton(EmbeddingServiceInterface::class, function () {
                return new OpenAIEmbeddingService(
                    config('openai.api_key') ?: '',
                    config('openai.embedding_model', 'text-embedding-3-small')
                );
            });
            $this->app->singleton(LlmServiceInterface::class, function () {
                return new OpenAILlmService(
                    config('openai.api_key') ?: '',
                    config('openai.chat_model', 'gpt-4o-mini')
                );
            });
        }

        $qdrant = null;
        if (config('qdrant.url')) {
            $vectorSize = $useCohere
                ? config('cohere.embedding_dimensions', 1024)
                : config('qdrant.vector_size', 1536);
            $qdrant = new QdrantClient(
                config('qdrant.url'),
                config('qdrant.collection'),
                $vectorSize,
                config('qdrant.api_key')
            );
            $this->app->instance(QdrantClient::class, $qdrant);
        }
        $this->app->singleton(BookEmbeddingService::class, fn () => new BookEmbeddingService(
            app(EmbeddingServiceInterface::class),
            $qdrant
        ));
        $this->app->singleton(RagService::class, fn () => new RagService(
            app(EmbeddingServiceInterface::class),
            app(BookEmbeddingService::class),
            app(LlmServiceInterface::class)
        ));
        $this->app->singleton(MetadataSuggestionService::class, fn () => new MetadataSuggestionService(
            app(LlmServiceInterface::class)
        ));
    }

    public function boot(): void
    {
        //
    }
}
