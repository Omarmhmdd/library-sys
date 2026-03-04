# Mini Library Management System

Full-stack app: Laravel API (books, borrow/return, auth, AI) + React frontend.

## Quick start

### Backend

```bash
cd backend
cp .env.example .env
php artisan key:generate
# Set DB_*, then:
php artisan migrate --force
php artisan db:seed --force
php artisan serve
```

API: `http://127.0.0.1:8000/api`  
See [backend/README.md](backend/README.md) and [backend/docs/SETUP_AND_TEST.md](backend/docs/SETUP_AND_TEST.md) for env (MySQL, optional OpenAI/Cohere, Qdrant, SSO).

### Frontend

```bash
cd frontend
npm install
npm run dev
```

App: `http://localhost:5173`  
Uses the backend via Vite proxy when both run locally.

### Test users (after seed)

- **Member:** `test@example.com` / `password`
- **Admin:** `admin@example.com` / `password`

## Features

- **Auth:** Register, login, logout, SSO (Google/GitHub)
- **Books:** List, search (title/author/genre), view, borrow, return
- **Roles:** Member (browse, borrow), Admin/Librarian (+ add/edit/delete books)
- **AI:** Ask the Library (RAG), similar books, metadata suggestion when adding/editing
- **My borrowals:** List current and past borrowals

## Deploy

- **Backend:** Deploy Laravel (e.g. Railway, Forge, VPS). Set `APP_URL` and CORS for the frontend origin.
- **Frontend:** `cd frontend && npm run build`. Serve `dist/` (e.g. Vercel, Netlify, same host). Set `VITE_API_BASE` to the backend URL.
