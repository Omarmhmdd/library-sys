import type { ApiResponse, Book, Borrowal, Paginated, User } from '../types';

const getBase = () => (import.meta.env.VITE_API_BASE as string) ?? '';

function getToken(): string | null {
  return localStorage.getItem('token');
}

export async function api<T = unknown>(
  path: string,
  options: RequestInit = {}
): Promise<ApiResponse<T>> {
  const url = `${getBase()}${path.startsWith('/') ? path : `/${path}`}`;
  const headers: HeadersInit = {
    Accept: 'application/json',
    'Content-Type': 'application/json',
    ...(options.headers as Record<string, string>),
  };
  const token = getToken();
  if (token) headers['Authorization'] = `Bearer ${token}`;

  const res = await fetch(url, { ...options, headers });
  const json = (await res.json().catch(() => ({}))) as ApiResponse<T> & { message?: string; errors?: Record<string, string[]> };
  if (!res.ok) {
    const msg = json.message ?? `Request failed: ${res.status}`;
    const err = new Error(msg) as Error & { errors?: Record<string, string[]> };
    if (json.errors) err.errors = json.errors;
    throw err;
  }
  return json;
}

export const auth = {
  async login(email: string, password: string) {
    const { data } = await api<{ user: User; token: string; token_type: string }>(
      '/api/auth/login',
      { method: 'POST', body: JSON.stringify({ email, password }) }
    );
    if (data?.token) localStorage.setItem('token', data.token);
    return data;
  },
  async register(name: string, email: string, password: string, password_confirmation: string) {
    const { data } = await api<{ user: User; token: string; token_type: string }>(
      '/api/auth/register',
      { method: 'POST', body: JSON.stringify({ name, email, password, password_confirmation }) }
    );
    if (data?.token) localStorage.setItem('token', data.token);
    return data;
  },
  async logout() {
    try {
      await api('/api/auth/logout', { method: 'POST' });
    } finally {
      localStorage.removeItem('token');
    }
  },
  async me() {
    const { data } = await api<User>('/api/auth/me');
    return data;
  },
  async getSsoProviders(): Promise<{ google: boolean; github: boolean }> {
    const res = await fetch(`${getBase()}/api/auth/sso-providers`, { headers: { Accept: 'application/json' } });
    const json = (await res.json()) as { data?: { google?: boolean; github?: boolean } };
    return { google: !!json.data?.google, github: !!json.data?.github };
  },
  async getSsoRedirectUrl(provider: 'google' | 'github'): Promise<string> {
    const res = await fetch(`${getBase()}/api/auth/${provider}/redirect`, { headers: { Accept: 'application/json' } });
    const json = (await res.json()) as { data?: { url?: string }; message?: string };
    const url = json.data?.url;
    if (!url) throw new Error(json.message ?? 'Failed to get SSO URL');
    return url;
  },
  async ssoRedirect(provider: 'google' | 'github'): Promise<void> {
    const url = await auth.getSsoRedirectUrl(provider);
    window.location.href = url;
  },
};

export const books = {
  list(params: { search?: string; author?: string; genre?: string; title?: string; per_page?: number } = {}) {
    const q = new URLSearchParams();
    Object.entries(params).forEach(([k, v]) => v != null && v !== '' && q.set(k, String(v)));
    return api<{ data: Book[]; meta: Paginated<Book>['meta'] }>(`/api/books?${q}`);
  },
  get(id: number) {
    return api<Book>(`/api/books/${id}`);
  },
  create(payload: Partial<Book>) {
    return api<Book>('/api/books', { method: 'POST', body: JSON.stringify(payload) });
  },
  update(id: number, payload: Partial<Book>) {
    return api<Book>(`/api/books/${id}`, { method: 'PUT', body: JSON.stringify(payload) });
  },
  delete(id: number) {
    return api(`/api/books/${id}`, { method: 'DELETE' });
  },
  borrow(id: number) {
    return api<{ borrowal_id: number; book: Book; borrowed_at: string }>(
      `/api/books/${id}/borrow`,
      { method: 'POST' }
    );
  },
  return(id: number) {
    return api<{ book: Book; returned_at: string }>(`/api/books/${id}/return`, { method: 'POST' });
  },
  similar(id: number) {
    return api<{ data: Book[] }>(`/api/books/${id}/similar`);
  },
};

export const borrowals = {
  my(params: { per_page?: number } = {}) {
    const q = new URLSearchParams();
    if (params.per_page != null) q.set('per_page', String(params.per_page));
    return api<{ data: Borrowal[]; meta: Paginated<Borrowal>['meta'] }>(`/api/borrowals?${q}`);
  },
};

export const ai = {
  ask(question: string) {
    return api<{ answer: string }>('/api/ai/ask', {
      method: 'POST',
      body: JSON.stringify({ question }),
    });
  },
  suggestMetadata(title: string, author: string) {
    return api<{ genre?: string; description?: string }>('/api/ai/suggest-metadata', {
      method: 'POST',
      body: JSON.stringify({ title, author }),
    });
  },
};
