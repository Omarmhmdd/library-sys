<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use App\Http\Resources\BookResource;
use App\Models\User;
use App\Services\BookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;

class ApiController extends Controller
{
    public function __construct(
        private readonly BookService $bookService
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
        return $this->created((new BookResource($book))->toArray($request));
    }

    public function booksUpdate(UpdateBookRequest $request, int $id): JsonResponse
    {
        $book = $this->bookService->find($id);
        if (! $book) {
            return $this->error('Book not found', 404);
        }
        $book = $this->bookService->update($book, $request->validated());
        return $this->success((new BookResource($book))->toArray($request));
    }

    public function booksDestroy(int $id): JsonResponse
    {
        $book = $this->bookService->find($id);
        if (! $book) {
            return $this->error('Book not found', 404);
        }
        $this->bookService->delete($book);
        return $this->success(null, 'Book deleted');
    }
}
