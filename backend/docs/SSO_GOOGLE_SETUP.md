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
