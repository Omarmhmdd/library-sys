<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use App\Http\Resources\BookResource;
use App\Models\Book;
use App\Models\User;
use App\Services\AI\BookEmbeddingService;
use App\Services\AI\MetadataSuggestionService;
use App\Services\AI\RagService;
use App\Services\BookService;
use App\Services\BorrowalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;

class ApiController extends Controller
{
    public function __construct(
        private readonly BookService $bookService,
        private readonly BorrowalService $borrowalService,
        private readonly BookEmbeddingService $bookEmbeddingService,
    ) {}

    /** JSON response helpers (no trait – methods live here) */
    private function success(mixed $data = null, string $message = 'OK', int $code = 200): JsonResponse
    {
        $body = ['message' => $message];
        if ($data !== null) {
            $body['data'] = $data;
        }
        return response()->json($body, $code);
    }

    private function created(mixed $data = null, string $message = 'Created'): JsonResponse
    {
        return $this->success($data, $message, 201);
    }

    private function error(string $message, int $code = 400, mixed $errors = null): JsonResponse
    {
        $body = ['message' => $message];
        if ($errors !== null) {
            $body['errors'] = $errors;
        }
        return response()->json($body, $code);
    }

    // ---- Auth ----

    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => User::ROLE_MEMBER,
        ]);

        $token = $user->createToken('auth')->plainTextToken;

        return $this->created([
            'user' => $user->only(['id', 'name', 'email', 'role']),
            'token' => $token,
            'token_type' => 'Bearer',
        ], 'Registered successfully');
    }

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::guard('web')->attempt($request->only('email', 'password'))) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $user = User::where('email', $request->email)->firstOrFail();
        $user->tokens()->delete();
        $token = $user->createToken('auth')->plainTextToken;

        return $this->success([
            'user' => $user->only(['id', 'name', 'email', 'role']),
            'token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();
        return $this->success(null, 'Logged out');
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        return $this->success($user->only(['id', 'name', 'email', 'role']));
    }

    public function redirectToProvider(string $provider): JsonResponse
    {
        $this->validateProvider($provider);
        $driver = Socialite::driver($provider);
        /** @var object $driver */
        if (method_exists($driver, 'stateless')) {
            $driver = $driver->stateless();
        }
        $url = $driver->redirect()->getTargetUrl();
        return $this->success(['url' => $url]);
    }

    public function handleProviderCallback(string $provider): JsonResponse
    {
        $this->validateProvider($provider);
        $driver = Socialite::driver($provider);
        /** @var object $driver */
        if (method_exists($driver, 'stateless')) {
            $driver = $driver->stateless();
        }
        $socialUser = $driver->user();

        $user = User::firstOrCreate(
            ['email' => $socialUser->getEmail()],
            [
                'name' => $socialUser->getName() ?? $socialUser->getNickname() ?? $socialUser->getEmail(),
                'password' => Hash::make(rand() . $socialUser->getId()),
                'role' => User::ROLE_MEMBER,
            ]
        );

        $user->tokens()->delete();
        $token = $user->createToken('auth')->plainTextToken;

        return $this->success([
            'user' => $user->only(['id', 'name', 'email', 'role']),
            'token' => $token,
            'token_type' => 'Bearer',
        ], 'Authenticated via ' . $provider);
    }

    private function validateProvider(string $provider): void
    {
        if (! in_array($provider, ['google', 'github'], true)) {
            abort(404, 'Unknown provider');
        }
    }

    // ---- Books ----

    /** List books. Query: search, author, genre, title, per_page */
    public function booksIndex(Request $request): JsonResponse
    {
        $filters = $request->only(['search', 'author', 'genre', 'title']);
        $perPage = min((int) $request->input('per_page', 15), 50);
        $paginator = $this->bookService->list($filters, $perPage);

        $items = [];
        foreach ($paginator->items() as $book) {
            $items[] = (new BookResource($book))->toArray($request);
        }

        return $this->success([
            'data' => $items,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function booksShow(Request $request, int $id): JsonResponse
    {
        $book = $this->bookService->find($id);
        if (! $book) {
            return $this->error('Book not found', 404);
        }
        return $this->success((new BookResource($book))->toArray($request));
    }

    public function booksStore(StoreBookRequest $request): JsonResponse
    {
        $book = $this->bookService->store($request->validated());
        $this->upsertBookEmbedding($book);
        return $this->created((new BookResource($book))->toArray($request));
    }

    public function booksUpdate(UpdateBookRequest $request, int $id): JsonResponse
    {
        $book = $this->bookService->find($id);
        if (! $book) {
            return $this->error('Book not found', 404);
        }
        $book = $this->bookService->update($book, $request->validated());
        $this->upsertBookEmbedding($book);
        return $this->success((new BookResource($book))->toArray($request));
    }

    public function booksDestroy(int $id): JsonResponse
    {
        $book = $this->bookService->find($id);
        if (! $book) {
            return $this->error('Book not found', 404);
        }
        $this->bookService->delete($book);
        $this->bookEmbeddingService->deleteForBook($id);
        return $this->success(null, 'Book deleted');
    }

    private function upsertBookEmbedding(Book $book): void
    {
        if (config('openai.api_key') || config('cohere.api_key')) {
            try {
                $this->bookEmbeddingService->upsertForBook($book);
            } catch (\Throwable) {
                // ignore embedding failures
            }
        }
    }

    // ---- Borrow / Return ----

    public function borrow(Request $request, int $id): JsonResponse
    {
        try {
            $borrowal = $this->borrowalService->borrow($request->user(), $id);
            $borrowal->load('book');
            return $this->created([
                'borrowal_id' => $borrowal->id,
                'book' => (new BookResource($borrowal->book))->toArray($request),
                'borrowed_at' => $borrowal->borrowed_at->toIso8601String(),
            ], 'Book borrowed');
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function returnBook(Request $request, int $id): JsonResponse
    {
        try {
            $borrowal = $this->borrowalService->returnBook($request->user(), $id);
            $borrowal->load('book');
            return $this->success([
                'book' => (new BookResource($borrowal->book))->toArray($request),
                'returned_at' => $borrowal->returned_at?->toIso8601String(),
            ], 'Book returned');
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function myBorrowals(Request $request): JsonResponse
    {
        $perPage = min((int) $request->input('per_page', 15), 50);
        $paginator = $this->borrowalService->myBorrowals($request->user(), $perPage);
        $items = [];
        foreach ($paginator->items() as $borrowal) {
            $items[] = [
                'id' => $borrowal->id,
                'book' => (new BookResource($borrowal->book))->toArray($request),
                'borrowed_at' => $borrowal->borrowed_at->toIso8601String(),
                'returned_at' => $borrowal->returned_at?->toIso8601String(),
                'is_active' => $borrowal->isActive(),
            ];
        }
        return $this->success([
            'data' => $items,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    // ---- AI ----

    public function aiAsk(Request $request): JsonResponse
    {
        $question = $request->input('question', '');
        if (trim($question) === '') {
            return $this->error('question is required', 422);
        }
        if (! config('openai.api_key') && ! config('cohere.api_key')) {
            return $this->error('AI is not configured (set OPENAI_API_KEY or COHERE_API_KEY).', 503);
        }
        try {
            $answer = app(RagService::class)->ask($question);
            return $this->success(['answer' => $answer]);
        } catch (\Throwable $e) {
            return $this->error('AI request failed: ' . $e->getMessage(), 502);
        }
    }

    public function aiSuggestMetadata(Request $request): JsonResponse
    {
        $title = $request->input('title', '');
        $author = $request->input('author', '');
        if (trim($title) === '' || trim($author) === '') {
            return $this->error('title and author are required', 422);
        }
        if (! config('openai.api_key') && ! config('cohere.api_key')) {
            return $this->error('AI is not configured (set OPENAI_API_KEY or COHERE_API_KEY).', 503);
        }
        try {
            $suggested = app(MetadataSuggestionService::class)->suggest($title, $author);
            return $this->success($suggested);
        } catch (\Throwable $e) {
            return $this->error('AI request failed: ' . $e->getMessage(), 502);
        }
    }

    public function similarBooks(Request $request, int $id): JsonResponse
    {
        $book = $this->bookService->find($id);
        if (! $book) {
            return $this->error('Book not found', 404);
        }
        if (! config('openai.api_key') && ! config('cohere.api_key')) {
            return $this->error('AI is not configured (set OPENAI_API_KEY or COHERE_API_KEY).', 503);
        }
        try {
            $text = $this->bookEmbeddingService->textForBook($book);
            $vector = app(\App\Services\AI\Contracts\EmbeddingServiceInterface::class)->embed($text);
            $ids = $this->bookEmbeddingService->searchSimilar($vector, 5, $id);
            $books = $ids === [] ? [] : Book::whereIn('id', $ids)->orderByRaw('FIELD(id, ' . implode(',', $ids) . ')')->get();
            $items = [];
            foreach ($books as $b) {
                $items[] = (new BookResource($b))->toArray($request);
            }
            return $this->success(['data' => $items]);
        } catch (\Throwable $e) {
            return $this->error('Similar books failed: ' . $e->getMessage(), 502);
        }
    }
}
