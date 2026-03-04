# Google SSO setup (required for “Sign in with Google”)

Follow these steps once so **any user** can sign in with Google.

---

## 1. Open Google Cloud Console

Go to: **https://console.cloud.google.com/** and sign in.

---

## 2. Create or select a project

- Top bar: click the **project** dropdown → **New Project**.
- Name it (e.g. **Library App**) → **Create** → select that project.

---

## 3. OAuth consent screen (required for all users)

- Left menu: **APIs & Services** → **OAuth consent screen**.
- Choose **External** (so any Google user can sign in, not just test users).
- **Create**.
- Fill **App name**, **User support email**, **Developer contact** (your email).
- **Save and Continue**.
- **Scopes**: **Save and Continue** (no need to add scopes for basic login).
- **Test users**: skip; you’ll use “Publish app” so all users can sign in.
- Click **Publish app** (so the app is not in “Testing” and any user can sign in).
- Confirm **Publish**.

---

## 4. Create OAuth credentials

- **APIs & Services** → **Credentials** → **+ Create Credentials** → **OAuth client ID**.
- **Application type**: **Web application**.
- **Name**: e.g. `Library web`.
- **Authorized redirect URIs** → **+ Add URI**:
  - Local: `http://localhost:8000/api/auth/google/callback`
  - Production: `https://your-api-domain.com/api/auth/google/callback` (use your real backend URL).
- **Create**.

---

## 5. Copy Client ID and Client secret

From the popup, copy:

- **Client ID** (ends in `.apps.googleusercontent.com`)
- **Client secret**

---

## 6. Configure backend `.env`

In the **backend** folder, in `.env`:

```env
GOOGLE_CLIENT_ID=paste-your-client-id-here.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=paste-your-client-secret-here
GOOGLE_REDIRECT_URI=http://localhost:8000/api/auth/google/callback
```

For production, set `GOOGLE_REDIRECT_URI` to your real backend URL, e.g.  
`https://api.yourdomain.com/api/auth/google/callback`.

So the SPA can receive the token after login:

```env
FRONTEND_URL=http://localhost:5173
```

(Use your real frontend URL in production.)

Restart the backend after changing `.env`.

---

## 7. Result

- “Sign in with Google” appears on the login page.
- **Any user** with a Google account can click it, sign in with Google, and be logged into your app.
- New users are created automatically; existing users are matched by email.

---

# GitHub SSO setup (optional – “Sign in with GitHub”)

To make **Sign in with GitHub** work like Google:

## 1. Create a GitHub OAuth App

- Go to **https://github.com/settings/developers** → **OAuth Apps** → **New OAuth App**.
- **Application name:** e.g. `Library App`.
- **Homepage URL:** e.g. `http://localhost:5173` (your frontend or app URL).
- **Authorization callback URL:** set **exactly** to:
  - Local: `http://localhost:8000/api/auth/github/callback`
  - Production: `https://your-api-domain.com/api/auth/github/callback`
- **Register application**.

## 2. Get Client ID and Secret

- On the app page, copy **Client ID**.
- Click **Generate a new client secret**, copy the **Client secret** (you won’t see it again).

## 3. Configure backend `.env`

In the **backend** folder, add:

```env
GITHUB_CLIENT_ID=your-github-client-id
GITHUB_CLIENT_SECRET=your-github-client-secret
GITHUB_REDIRECT_URI=http://localhost:8000/api/auth/github/callback
```

Use the same `FRONTEND_URL` as for Google so after GitHub login you’re sent back to the SPA.

Restart the backend. “Sign in with GitHub” will then work; if GitHub doesn’t provide an email, the app uses a placeholder so login still works.
