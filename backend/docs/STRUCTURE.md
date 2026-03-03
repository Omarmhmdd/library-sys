# Backend structure

- **app/Http/Requests** – Form requests (validation). Extend `BaseFormRequest` for API (returns 422 JSON).
- **app/Http/Resources** – API resources. Extend `BaseJsonResource` for consistent `data` + `meta`.
- **app/Http/Controllers/Api** – API controllers. Extend `App\Http\Controllers\Api\Controller` (uses `HasApiResponse`).
- **app/Services** – Business logic. Extend `BaseService`. Keep controllers thin.
- **app/Services/AI** – AI services (RAG, embeddings, metadata suggestion). Use `Contracts\*Interface` for testability.
- **app/Traits** – Reusable behaviour (e.g. `HasApiResponse` for success/created/error).
- **app/Data** – DTOs / value objects (e.g. `BookData`) for type-safe data between layers.
- **routes/api.php** – API routes (prefix `/api` by default). Use `auth:sanctum` for protected routes.
