<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use App\Services\AI\BookEmbeddingService;
use App\Services\AI\Contracts\EmbeddingServiceInterface;
use App\Services\AI\MetadataSuggestionService;
use App\Services\AI\RagService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class ApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
    }

    private function authHeaders(User $user): array
    {
        $token = $user->createToken('test')->plainTextToken;
        return ['Authorization' => 'Bearer ' . $token];
    }

    public function test_ping_returns_ok(): void
    {
        $response = $this->getJson('/api/ping');
        $response->assertStatus(200)->assertJson(['pong' => true]);
    }

    public function test_register_creates_user_and_returns_token(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'New User',
            'email' => 'new@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);
        $response->assertStatus(201)
            ->assertJsonStructure(['data' => ['user' => ['id', 'name', 'email', 'role'], 'token', 'token_type']]);
        $this->assertDatabaseHas('users', ['email' => 'new@example.com']);
    }

    public function test_login_returns_token(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);
        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['user', 'token', 'token_type']]);
    }

    public function test_me_requires_auth(): void
    {
        $this->getJson('/api/auth/me')->assertStatus(401);
    }

    public function test_me_returns_user_when_authenticated(): void
    {
        $user = User::where('email', 'test@example.com')->first();
        $token = $user->createToken('test')->plainTextToken;
        $response = $this->getJson('/api/auth/me', ['Authorization' => 'Bearer ' . $token]);
        $response->assertStatus(200)->assertJsonPath('data.email', 'test@example.com');
    }

    public function test_books_index_requires_auth(): void
    {
        $this->getJson('/api/books')->assertStatus(401);
    }

    public function test_books_index_returns_paginated_books(): void
    {
        $user = User::where('email', 'test@example.com')->first();
        $token = $user->createToken('test')->plainTextToken;
        $response = $this->getJson('/api/books', ['Authorization' => 'Bearer ' . $token]);
        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['data', 'meta' => ['current_page', 'total']]]);
    }

    public function test_books_show_returns_one_book(): void
    {
        $user = User::where('email', 'test@example.com')->first();
        $token = $user->createToken('test')->plainTextToken;
        $book = Book::first();
        $response = $this->getJson('/api/books/' . $book->id, ['Authorization' => 'Bearer ' . $token]);
        $response->assertStatus(200)->assertJsonPath('data.id', $book->id);
    }

    public function test_borrow_and_return_flow(): void
    {
        $user = User::where('email', 'test@example.com')->first();
        $token = $user->createToken('test')->plainTextToken;
        $book = Book::first();
        $headers = ['Authorization' => 'Bearer ' . $token];

        $borrow = $this->postJson('/api/books/' . $book->id . '/borrow', [], $headers);
        $borrow->assertStatus(201)->assertJsonStructure(['data' => ['borrowal_id', 'book', 'borrowed_at']]);

        $returnResp = $this->postJson('/api/books/' . $book->id . '/return', [], $headers);
        $returnResp->assertStatus(200)->assertJsonStructure(['data' => ['book', 'returned_at']]);
    }

    public function test_borrowals_list_returns_my_borrowals(): void
    {
        $user = User::where('email', 'test@example.com')->first();
        $response = $this->getJson('/api/borrowals', $this->authHeaders($user));
        $response->assertStatus(200)->assertJsonStructure(['data' => ['data', 'meta']]);
    }

    // ---- AI endpoints (auth + validation + 503 when no keys) ----

    public function test_ai_ask_requires_auth(): void
    {
        $this->postJson('/api/ai/ask', ['question' => 'Any books?'])->assertStatus(401);
    }

    public function test_ai_ask_requires_question(): void
    {
        $user = User::where('email', 'test@example.com')->first();
        $this->postJson('/api/ai/ask', [], $this->authHeaders($user))->assertStatus(422);
        $this->postJson('/api/ai/ask', ['question' => '  '], $this->authHeaders($user))->assertStatus(422);
    }

    public function test_ai_ask_returns_503_when_ai_not_configured(): void
    {
        $user = User::where('email', 'test@example.com')->first();
        $response = $this->postJson('/api/ai/ask', ['question' => 'What books do you have?'], $this->authHeaders($user));
        $response->assertStatus(503);
        $this->assertStringContainsString('not configured', (string) $response->json('message'));
    }

    public function test_ai_ask_returns_200_with_mocked_rag(): void
    {
        Config::set('cohere.api_key', 'test-key');
        $this->mock(RagService::class, function ($mock): void {
            $mock->shouldReceive('ask')->once()->with('What books?')->andReturn('We have programming and fiction books.');
        });
        $user = User::where('email', 'test@example.com')->first();
        $response = $this->postJson('/api/ai/ask', ['question' => 'What books?'], $this->authHeaders($user));
        $response->assertStatus(200)->assertJsonPath('data.answer', 'We have programming and fiction books.');
    }

    public function test_ai_suggest_metadata_requires_auth(): void
    {
        $this->postJson('/api/ai/suggest-metadata', ['title' => 'Foo', 'author' => 'Bar'])->assertStatus(401);
    }

    public function test_ai_suggest_metadata_requires_title_and_author(): void
    {
        $user = User::where('email', 'test@example.com')->first();
        $h = $this->authHeaders($user);
        $this->postJson('/api/ai/suggest-metadata', [], $h)->assertStatus(422);
        $this->postJson('/api/ai/suggest-metadata', ['title' => 'Foo'], $h)->assertStatus(422);
        $this->postJson('/api/ai/suggest-metadata', ['author' => 'Bar'], $h)->assertStatus(422);
    }

    public function test_ai_suggest_metadata_returns_503_when_ai_not_configured(): void
    {
        $user = User::where('email', 'test@example.com')->first();
        $response = $this->postJson('/api/ai/suggest-metadata', ['title' => 'Clean Code', 'author' => 'Robert Martin'], $this->authHeaders($user));
        $response->assertStatus(503);
    }

    public function test_ai_suggest_metadata_returns_200_with_mocked_service(): void
    {
        Config::set('cohere.api_key', 'test-key');
        $this->mock(MetadataSuggestionService::class, function ($mock): void {
            $mock->shouldReceive('suggest')->once()->with('Clean Code', 'Robert Martin')->andReturn(['genre' => 'Technology', 'description' => 'A handbook of agile software craftsmanship.']);
        });
        $user = User::where('email', 'test@example.com')->first();
        $response = $this->postJson('/api/ai/suggest-metadata', ['title' => 'Clean Code', 'author' => 'Robert Martin'], $this->authHeaders($user));
        $response->assertStatus(200)->assertJsonPath('data.genre', 'Technology');
        $this->assertStringContainsString('agile', (string) $response->json('data.description'));
    }

    public function test_similar_books_requires_auth(): void
    {
        $book = Book::first();
        $this->getJson('/api/books/' . $book->id . '/similar')->assertStatus(401);
    }

    public function test_similar_books_returns_404_when_book_not_found(): void
    {
        $user = User::where('email', 'test@example.com')->first();
        $this->getJson('/api/books/99999/similar', $this->authHeaders($user))->assertStatus(404);
    }

    public function test_similar_books_returns_503_when_ai_not_configured(): void
    {
        $user = User::where('email', 'test@example.com')->first();
        $book = Book::first();
        $response = $this->getJson('/api/books/' . $book->id . '/similar', $this->authHeaders($user));
        $response->assertStatus(503);
    }

    public function test_similar_books_returns_200_with_empty_results_when_no_embeddings(): void
    {
        Config::set('cohere.api_key', 'test-key');
        Config::set('qdrant.url', null);
        $this->app->forgetInstance(EmbeddingServiceInterface::class);
        $this->app->forgetInstance(BookEmbeddingService::class);

        $vector = array_fill(0, 1024, 0.0);
        $this->mock(EmbeddingServiceInterface::class, function ($mock) use ($vector): void {
            $mock->shouldReceive('embed')->once()->andReturn($vector);
        });

        $book = Book::first();
        $user = User::where('email', 'test@example.com')->first();
        $response = $this->getJson('/api/books/' . $book->id . '/similar', $this->authHeaders($user));

        $response->assertStatus(200)->assertJsonStructure(['data' => ['data']]);
        $this->assertIsArray($response->json('data.data'));
    }

    // ---- Admin book CRUD ----

    public function test_books_store_requires_admin_or_librarian(): void
    {
        $user = User::where('email', 'test@example.com')->first(); // member
        $response = $this->postJson('/api/books', [
            'title' => 'New Book',
            'author' => 'Author',
            'genre' => 'Fiction',
            'description' => 'A book',
        ], $this->authHeaders($user));
        $response->assertStatus(403);
    }

    public function test_books_store_creates_book_as_admin(): void
    {
        $admin = User::where('email', 'admin@example.com')->first();
        $response = $this->postJson('/api/books', [
            'title' => 'New Book',
            'author' => 'Author',
            'genre' => 'Fiction',
            'description' => 'A book',
        ], $this->authHeaders($admin));
        $response->assertStatus(201)->assertJsonPath('data.title', 'New Book');
        $this->assertDatabaseHas('books', ['title' => 'New Book']);
    }

    public function test_books_update_requires_admin_or_librarian(): void
    {
        $user = User::where('email', 'test@example.com')->first();
        $book = Book::first();
        $this->putJson('/api/books/' . $book->id, ['title' => 'Updated', 'author' => $book->author, 'genre' => $book->genre], $this->authHeaders($user))->assertStatus(403);
    }

    public function test_books_destroy_requires_admin_or_librarian(): void
    {
        $user = User::where('email', 'test@example.com')->first();
        $book = Book::first();
        $this->deleteJson('/api/books/' . $book->id, [], $this->authHeaders($user))->assertStatus(403);
    }
}
