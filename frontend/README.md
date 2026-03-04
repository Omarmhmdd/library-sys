# Library – Frontend

React 18 + TypeScript + Vite. Uses the Laravel API (auth, books, borrowals, AI).

## Setup

```bash
npm install
```

## Dev

With the backend running on `http://127.0.0.1:8000`:

```bash
npm run dev
```

Open `http://localhost:5173`. API requests are proxied to the backend (see `vite.config.ts`).

## Build

```bash
npm run build
```

Output in `dist/`. For production, set `VITE_API_BASE` to your API URL (e.g. `https://api.example.com`) so requests go to the deployed backend.

## Scripts

- `npm run dev` – dev server (port 5173)
- `npm run build` – production build
- `npm run preview` – preview production build locally
