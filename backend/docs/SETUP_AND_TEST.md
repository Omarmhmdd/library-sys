# Setup and test the backend (full stack: DB, Qdrant, OpenAI)

Follow this to get everything running and verify all features.

---

## 1. Get an OpenAI API key

1. Go to [https://platform.openai.com/](https://platform.openai.com/).
2. Sign up or log in.
3. Open **API keys** (or **Settings → API keys**).
4. Create a new key and copy it (starts with `sk-`).
5. You need a small amount of credits; new accounts often get free trial usage.

---

## 2. Start Qdrant and MySQL (Docker)

From the **backend** folder:

```bash
docker-compose up -d
```

- **Qdrant** → `http://127.0.0.1:6333` (REST), dashboard: `http://127.0.0.1:6333/dashboard`
- **MySQL** → port **3307** on host (mapped from 3306 in container)

If you prefer to use **XAMPP MySQL** instead of Docker MySQL, skip the `mysql` service (run only Qdrant) and create the database `library_sys` in XAMPP. Then use `DB_HOST=127.0.0.1` and `DB_PORT=3306` in `.env`.

---

## 3. Configure `.env`

In **backend** folder:

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` and set at least:

```env
APP_URL=http://localhost:8000

# Database (Docker MySQL)
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3307
DB_DATABASE=library_sys
DB_USERNAME=root
DB_PASSWORD=secret

# Or if using XAMPP MySQL:
# DB_PORT=3306
# DB_PASSWORD=

# OpenAI (required for AI features)
OPENAI_API_KEY=sk-your-key-here

# Qdrant (required for vector search / similar books / RAG)
QDRANT_URL=http://127.0.0.1:6333
```

Save the file.

---

## 4. Migrate and seed

```bash
cd backend
php artisan migrate --force
php artisan db:seed --force
```

This creates tables and seeds users (e.g. `test@example.com` / `admin@example.com`) and 5 books.

---

## 5. Index book embeddings (OpenAI + Qdrant)

```bash
php artisan books:index-embeddings
```

You should see a progress bar. This fills Qdrant with vectors so **similar books** and **Ask the Library (RAG)** work.

---

## 6. Start the API

```bash
php artisan serve
```

API base: **http://localhost:8000/api**

---

## 7. Test the API

Use a REST client (Postman, Insomnia) or `curl`. Examples below assume `BASE=http://localhost:8000/api`.

### 7.1 Health

```bash
curl -s http://localhost:8000/api/ping
# Expect: {"pong":true}
```

### 7.2 Register and login

```bash
# Register
curl -s -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{"name":"Test User","email":"test2@example.com","password":"password123","password_confirmation":"password123"}'

# Login (get token from response above or use seeded user)
curl -s -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"password"}'
```

Copy the `token` from the login response and set it for next requests:

```bash
export TOKEN="your-bearer-token-here"
```

### 7.3 Protected routes (use `$TOKEN`)

```bash
# Me
curl -s http://localhost:8000/api/auth/me -H "Authorization: Bearer $TOKEN"

# List books
curl -s "http://localhost:8000/api/books?per_page=5" -H "Authorization: Bearer $TOKEN"

# One book
curl -s http://localhost:8000/api/books/1 -H "Authorization: Bearer $TOKEN"

# Search
curl -s "http://localhost:8000/api/books?search=Orwell" -H "Authorization: Bearer $TOKEN"
```

### 7.4 Borrow and return

```bash
# Borrow book 1
curl -s -X POST http://localhost:8000/api/books/1/borrow -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json"

# My borrowals
curl -s http://localhost:8000/api/borrowals -H "Authorization: Bearer $TOKEN"

# Return book 1
curl -s -X POST http://localhost:8000/api/books/1/return -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json"
```

### 7.5 AI (needs OPENAI_API_KEY and indexed embeddings)

```bash
# Ask the Library (RAG)
curl -s -X POST http://localhost:8000/api/ai/ask \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"question":"Do you have books about dystopia?"}'

# Suggest metadata (for adding a book)
curl -s -X POST http://localhost:8000/api/ai/suggest-metadata \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"title":"The Hobbit","author":"J.R.R. Tolkien"}'

# Similar books (needs Qdrant + indexed embeddings)
curl -s http://localhost:8000/api/books/1/similar -H "Authorization: Bearer $TOKEN"
```

### 7.6 Admin: create book (use admin token)

Login as admin to get a token, then:

```bash
curl -s -X POST http://localhost:8000/api/books \
  -H "Authorization: Bearer $ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"title":"The Hobbit","author":"J.R.R. Tolkien","genre":"Fantasy","description":"A fantasy novel."}'
```

Seeded users (password for both: **password**):
- Member: `test@example.com`
- Admin: `admin@example.com`

---

## 8. Run automated tests

```bash
cd backend
php artisan test
```

Tests use in-memory SQLite and do **not** require OpenAI or Qdrant. They cover auth and basic book/borrow flows.

---

## Checklist

- [ ] OpenAI API key in `.env`
- [ ] Qdrant running (`docker-compose up -d` or `docker run -p 6333:6333 qdrant/qdrant`)
- [ ] MySQL running (Docker or XAMPP), `.env` DB_* correct
- [ ] `php artisan migrate --force` and `db:seed --force`
- [ ] `php artisan books:index-embeddings` (after OPENAI + QDRANT set)
- [ ] `php artisan serve` and test with curl/Postman
- [ ] `php artisan test` passes
