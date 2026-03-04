# Get a live URL – deploy in ~15 minutes

Follow this to get **a live URL** for your Mini Library app (frontend + API) using **free tiers**.

---

## What you’ll get

- **Frontend:** `https://your-app.vercel.app` (or your custom domain)
- **Backend API:** `https://your-api.railway.app` (or Render)
- **Live testing:** Sign up, log in (email or Google/GitHub), browse books, borrow/return, Ask AI, etc.

---

## Step 1: Push your code to GitHub

If the repo is already on GitHub, push latest. If not:

```bash
cd c:\Users\Omar\library-sys
git add .
git commit -m "Ready for deploy"
git remote add origin https://github.com/YOUR_USERNAME/library-sys.git
git push -u origin main
```

Use your real GitHub username and repo URL.

---

## Step 2: Deploy the backend (API) on Railway

1. Go to **[railway.app](https://railway.app)** and sign in with GitHub.
2. **New Project** → **Deploy from GitHub repo** → choose **library-sys**.
3. Railway will detect the repo. We’ll use a **Dockerfile** in `backend/`:
   - After the repo is connected, add a **service** (or use the one created).
   - Open the service → **Settings** → **Root Directory** (or **Source**): set to **`backend`**.
   - **Build:** Railway should detect `backend/Dockerfile`. If not, set **Builder** to **Dockerfile** and **Dockerfile path** to `Dockerfile`.
4. Add **MySQL**:
   - In the same project click **+ New** → **Database** → **MySQL**.
   - Once created, open the MySQL service → **Variables** (or **Connect**) and copy the URL or host/user/password.
5. Set **Variables** for the **backend service** (not MySQL):
   - **Variables** tab (or **Settings** → **Variables**), add:

   | Name | Value |
   |------|--------|
   | `APP_KEY` | Run `php artisan key:generate` locally in `backend/`, copy the `APP_KEY` from `.env` (base64:...) |
   | `APP_ENV` | `production` |
   | `APP_DEBUG` | `false` |
   | `APP_URL` | Leave empty for now; we’ll set it after deploy (e.g. `https://your-api.up.railway.app`) |
   | `DB_CONNECTION` | `mysql` |
   | `DB_HOST` | From Railway MySQL: e.g. `containers-us-west-xxx.railway.app` |
   | `DB_PORT` | From Railway MySQL: `3306` (or the port shown) |
   | `DB_DATABASE` | From Railway MySQL |
   | `DB_USERNAME` | From Railway MySQL |
   | `DB_PASSWORD` | From Railway MySQL |
   | `FRONTEND_URL` | Leave empty for now; we’ll set to the Vercel URL in Step 4 |

   If Railway gives a single **`DATABASE_URL`**, check their Laravel docs; otherwise use the separate `DB_*` vars above.

6. **Deploy:** Trigger a deploy (push a commit or **Deploy** in the dashboard). Wait until the build and deploy succeed.
7. **Public URL:** In the backend service → **Settings** → **Networking** → **Generate domain**. Copy the URL (e.g. `https://library-sys-api.up.railway.app`). This is your **API URL**.
8. **Seed the database (once):**  
   In the backend service → **Settings** → **Deploy** or **Shell**, run (or use Railway CLI):  
   `php artisan db:seed --force`  
   so you have books and the admin user (`admin@example.com` / `password`).
9. Set **`APP_URL`** in Variables to the generated domain (e.g. `https://library-sys-api.up.railway.app`). Redeploy if needed.

---

## Step 3: Deploy the frontend on Vercel

1. Go to **[vercel.com](https://vercel.com)** and sign in with GitHub.
2. **Add New** → **Project** → import **library-sys**.
3. **Configure:**
   - **Root Directory:** click **Edit** → set to **`frontend`** (not the repo root).
   - **Framework Preset:** Vite (or leave as auto).
   - **Build Command:** `npm run build` (default).
   - **Output Directory:** `dist` (default).
   - **Environment Variable:** Add:
     - **Name:** `VITE_API_BASE`  
     - **Value:** your Railway API URL from Step 2 (e.g. `https://library-sys-api.up.railway.app`)  
     - **No trailing slash.**
4. Click **Deploy**. Wait for the build to finish.
5. Copy your **frontend URL** (e.g. `https://library-sys.vercel.app`).

---

## Step 4: Wire frontend and backend

1. **Backend (Railway):** In the API service **Variables**, set:
   - **`FRONTEND_URL`** = your Vercel URL (e.g. `https://library-sys.vercel.app`)  
   No trailing slash.  
   Redeploy the backend so CORS and SSO redirect use this URL.
2. **Optional – SSO (Google/GitHub):**  
   In Google Cloud and GitHub OAuth apps, add the **live** callback URLs:
   - Google: `https://YOUR_RAILWAY_API_DOMAIN/api/auth/google/callback`
   - GitHub: `https://YOUR_RAILWAY_API_DOMAIN/api/auth/github/callback`  
   Then in Railway backend Variables set `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI`, and the same for GitHub. Redeploy.

---

## Step 5: Test and share your URL

- **Live app URL:** Open the **Vercel URL** (e.g. `https://library-sys.vercel.app`).
- **Register** or use **demo admin:** `admin@example.com` / `password` (after seeding in Step 2).
- **Test:** Books, search, borrow/return, My borrowals, Ask AI, Add book (as admin), SSO if configured.

**Provide this URL** for “live testing” in your challenge: your **Vercel frontend URL**.

---

## If you prefer Render instead of Railway (backend)

1. **[render.com](https://render.com)** → Sign in with GitHub.
2. **New** → **Web Service** → connect **library-sys**.
3. **Root Directory:** `backend`.
4. **Environment:** Docker.
5. **Build Command:** (leave default; Render uses the Dockerfile.)
6. **Start Command:** (leave default; Dockerfile CMD runs.)
7. Add **MySQL:** **New** → **PostgreSQL** or use **MySQL** if available; copy host, database, user, password.
8. In the **backend** Web Service, set **Environment Variables** the same as in the Railway table (APP_KEY, APP_URL, DB_*, FRONTEND_URL). For **APP_URL** use the Render URL Render gives you (e.g. `https://library-sys-api.onrender.com`).
9. After first deploy, run **Shell** (or one-off job) to seed: `php artisan db:seed --force`.
10. Use the Render backend URL as **VITE_API_BASE** in Vercel and as **FRONTEND_URL** and in SSO callbacks.

---

## Troubleshooting

- **CORS / “blocked by CORS policy”:** Ensure **FRONTEND_URL** on the backend exactly matches the Vercel URL (no trailing slash). Redeploy backend.
- **401 on API calls:** You’re not logged in; use Register or Login. For admin, use `admin@example.com` / `password` after seeding.
- **500 from API:** Check Railway (or Render) **Logs**. Often missing **APP_KEY** or wrong **DB_***. Fix Variables and redeploy.
- **Blank frontend:** Build must have **VITE_API_BASE** set. Rebuild on Vercel with the variable set.
