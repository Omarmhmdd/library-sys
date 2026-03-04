# Library System – Backend (Laravel)

Mini Library Management API: books CRUD, borrow/return, search, auth (Sanctum + SSO), and AI (RAG, metadata suggestion, similar books).

**Full setup (OpenAI key, Qdrant, MySQL, test steps):** see [docs/SETUP_AND_TEST.md](docs/SETUP_AND_TEST.md).

## Requirements

- PHP 8.2+
- Composer
- MySQL 8+ (or SQLite)
- Node/npm (for frontend build if needed)

## Setup

1. **Clone and install**
   ```bash
   cd backend
   composer install
   cp .env.example .env
   php artisan key:generate
   ```

2. **Database**
   - Create a MySQL database (e.g. `library_sys`).
   - In `.env` set: `DB_CONNECTION=mysql`, `DB_DATABASE=library_sys`, `DB_USERNAME`, `DB_PASSWORD`.
   ```bash
   php artisan migrate --force
   php artisan db:seed --force
   ```

3. **Optional – AI**
   - Add `OPENAI_API_KEY` to `.env` for RAG, metadata suggestion, and similar books.
   - **Optional – Qdrant** (recommended for vector search): run Qdrant (e.g. `docker run -p 6333:6333 qdrant/qdrant`) and set `QDRANT_URL=http://localhost:6333` in `.env`. If unset, vectors are stored in MySQL.
   - After adding books, index embeddings: `php artisan books:index-embeddings`.

4. **SSO (Google sign-in for all users)**
   - Set `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, and `GOOGLE_REDIRECT_URI` in `.env`. **Step-by-step:** [docs/SSO_GOOGLE_SETUP.md](docs/SSO_GOOGLE_SETUP.md). Optional: GitHub via `GITHUB_*`; set `FRONTEND_URL` so the SPA receives the token after SSO.

## Run

```bash
php artisan serve
```

API base: `http://localhost:8000/api`

## API overview

- **Auth:** `POST /auth/register`, `POST /auth/login`, `POST /auth/logout`, `GET /auth/me`, `GET /auth/{google|github}/redirect`, `GET /auth/{google|github}/callback`
- **Books:** `GET /books` (search: `?search=`, `?author=`, `?genre=`, `?title=`, `?per_page=`), `GET /books/{id}`, `POST /books`, `PUT /books/{id}`, `DELETE /books/{id}` (create/update/delete require admin or librarian)
- **Borrowals:** `POST /books/{id}/borrow`, `POST /books/{id}/return`, `GET /borrowals` (my borrowals)
- **AI:** `POST /ai/ask` body `{ "question": "..." }`, `POST /ai/suggest-metadata` body `{ "title", "author" }`, `GET /books/{id}/similar`

Protected routes use `Authorization: Bearer {token}`.

## Artisan

- `php artisan books:index-embeddings` – (re)build embeddings for all books (needs `OPENAI_API_KEY`; uses Qdrant if `QDRANT_URL` is set).

## Test

- **Automated:** `php artisan test` (uses in-memory SQLite; no OpenAI/Qdrant needed).
- **Manual:** follow [docs/SETUP_AND_TEST.md](docs/SETUP_AND_TEST.md) to run with Docker (Qdrant + MySQL), set OpenAI key, and test all endpoints with curl.
