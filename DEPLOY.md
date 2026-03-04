# Deploy to a live server

Deploy the **backend** (Laravel API) and **frontend** (React SPA) so they work together in production.

---

## Overview

- **Backend:** Laravel runs on a PHP server (e.g. VPS, shared hosting, Railway, Render). Serves the API at `https://your-api.com` (or a subdomain).
- **Frontend:** Build the React app and serve the built files (e.g. Nginx, Vercel, Netlify, or same server). The app runs at `https://your-app.com` and calls the backend API.
- **Important:** Backend must allow requests from the frontend origin (CORS). SSO redirect URIs must use your **live** URLs.

---

## 1. Backend (Laravel) on the server

### 1.1 Server requirements

- PHP 8.2+
- Composer
- MySQL (or PostgreSQL/SQLite)
- Optional: Redis for cache/sessions (or use `file`/`database`)

### 1.2 Deploy the code

- Upload the `backend/` folder (or clone the repo and use only `backend/`).
- Document root must point to **`backend/public`** (not `backend`). All web requests go through `public/index.php`.

### 1.3 Environment (`.env`)

Create or edit `backend/.env` on the server:

```env
APP_NAME="Library API"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.yourdomain.com
APP_KEY=base64:xxxx   # generate with: php artisan key:generate

# Database (use your live DB)
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_db
DB_USERNAME=your_user
DB_PASSWORD=your_password

# Frontend URL (for SSO redirect and CORS)
FRONTEND_URL=https://yourdomain.com

# Session (use database or file in production)
SESSION_DRIVER=database
SESSION_DOMAIN=null

# Optional: AI
OPENAI_API_KEY=sk-...
# or COHERE_API_KEY=...

# Optional: Qdrant
QDRANT_URL=https://...
QDRANT_API_KEY=...

# Optional: SSO – use LIVE callback URLs
GOOGLE_CLIENT_ID=...
GOOGLE_CLIENT_SECRET=...
GOOGLE_REDIRECT_URI=https://api.yourdomain.com/api/auth/google/callback

GITHUB_CLIENT_ID=...
GITHUB_CLIENT_SECRET=...
GITHUB_REDIRECT_URI=https://api.yourdomain.com/api/auth/github/callback
```

Replace `https://api.yourdomain.com` with your real API URL and `https://yourdomain.com` with your real frontend URL.

### 1.4 Run migrations and seed (once)

```bash
cd /path/to/backend
composer install --no-dev --optimize-autoloader
php artisan key:generate   # if APP_KEY not set
php artisan migrate --force
php artisan db:seed --force
php artisan config:cache
php artisan route:cache
```

### 1.5 Web server config (Nginx example)

Point the domain to `backend/public`:

```nginx
server {
    listen 80;
    server_name api.yourdomain.com;
    root /var/www/library-sys/backend/public;

    add_header X-Frame-Options "SAMEORIGIN";
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

Use HTTPS in production (e.g. Let’s Encrypt with certbot).

### 1.6 CORS

The project has `backend/config/cors.php` that reads **`FRONTEND_URL`** from `.env`. Set:

```env
FRONTEND_URL=https://yourdomain.com
```

(No trailing slash.) That origin is then allowed for API requests. For multiple origins use:

```env
CORS_ALLOWED_ORIGINS=https://yourdomain.com,https://www.yourdomain.com
```

Clear config cache after changing: `php artisan config:cache`. If you still see CORS errors, add the same headers in Nginx for `/api` as in Option B in the next paragraph.

**Option B (if needed):** In Nginx for the API server block:

```nginx
add_header Access-Control-Allow-Origin "https://yourdomain.com" always;
add_header Access-Control-Allow-Methods "GET, POST, PUT, DELETE, OPTIONS";
add_header Access-Control-Allow-Headers "Authorization, Content-Type, Accept";
```

---

## 2. Frontend (React) on the server

### 2.1 Build with the live API URL

The frontend must call your **live** API, not localhost. Set `VITE_API_BASE` when building:

```bash
cd frontend
npm ci
VITE_API_BASE=https://api.yourdomain.com npm run build
```

This produces `frontend/dist/` with `index.html` and assets.

### 2.2 Serve the built files

- **Option A – Same server (Nginx):** Add a second server block (or location) that serves `frontend/dist` for `https://yourdomain.com` (e.g. `root /var/www/library-sys/frontend/dist; try_files $uri $uri/ /index.html;`).
- **Option B – Vercel / Netlify:** Upload or connect the repo, set build command to `cd frontend && npm ci && VITE_API_BASE=https://api.yourdomain.com npm run build`, set publish directory to `frontend/dist`.
- **Option C – Static host:** Upload the contents of `frontend/dist/` to any static host; ensure the host is configured to serve `index.html` for SPA routes.

Use **HTTPS** in production.

---

## 3. SSO (Google / GitHub) on the live URLs

- In **Google Cloud Console** (OAuth client): add to **Authorized redirect URIs**:
  - `https://api.yourdomain.com/api/auth/google/callback`
- In **GitHub** OAuth App: set **Authorization callback URL** to:
  - `https://api.yourdomain.com/api/auth/github/callback`
- In backend `.env` (already in step 1.3):
  - `GOOGLE_REDIRECT_URI=https://api.yourdomain.com/api/auth/google/callback`
  - `GITHUB_REDIRECT_URI=https://api.yourdomain.com/api/auth/github/callback`
  - `FRONTEND_URL=https://yourdomain.com` so after login the user is sent back to your SPA.

---

## 4. Checklist

- [ ] Backend `.env`: `APP_URL`, `FRONTEND_URL`, `DB_*`, optional AI and SSO with **live** callback URLs.
- [ ] Backend document root is `backend/public`.
- [ ] `php artisan migrate --force` and `php artisan config:cache` run on the server.
- [ ] Frontend built with `VITE_API_BASE=https://api.yourdomain.com`.
- [ ] Frontend served at `https://yourdomain.com` (or your chosen URL).
- [ ] CORS allows the frontend origin; no CORS errors in the browser.
- [ ] Google and GitHub OAuth apps use the **live** callback URLs above.
- [ ] HTTPS enabled for both API and frontend.

---

## 5. Optional: one-server setup

If API and frontend are on the **same domain** (e.g. `https://yourdomain.com` for the app and `https://yourdomain.com/api` for the API):

- Configure the web server so `/api` is proxied to Laravel (`backend/public`) and everything else is served from `frontend/dist` (SPA).
- Build the frontend with `VITE_API_BASE=` (empty) so it uses relative URLs like `/api`.
- Then `APP_URL` and `FRONTEND_URL` can both be `https://yourdomain.com`, and SSO callbacks would be `https://yourdomain.com/api/auth/google/callback` and same for GitHub.

This avoids CORS because the frontend and API share the same origin.
