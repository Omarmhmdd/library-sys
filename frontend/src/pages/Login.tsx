import { useState, useEffect } from 'react';
import { Link, useNavigate, useSearchParams } from 'react-router-dom';
import { useAuth } from '../hooks';
import { auth } from '../api/client';

export function Login() {
  const [searchParams, setSearchParams] = useSearchParams();
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');
  useEffect(() => {
    const err = searchParams.get('error');
    if (err) {
      setError(decodeURIComponent(err));
      setSearchParams({}, { replace: true });
    }
  }, [searchParams, setSearchParams]);
  const [fieldErrors, setFieldErrors] = useState<Record<string, string[]>>({});
  const [loading, setLoading] = useState(false);
  const [ssoLoading, setSsoLoading] = useState<'google' | 'github' | null>(null);
  const { login } = useAuth();
  const navigate = useNavigate();

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');
    setFieldErrors({});
    setLoading(true);
    try {
      await login(email, password);
      navigate('/books');
    } catch (err) {
      const e = err as Error & { errors?: Record<string, string[]> };
      setError(e.message ?? 'Login failed');
      setFieldErrors(e.errors ?? {});
    } finally {
      setLoading(false);
    }
  };

  const handleSso = async (provider: 'google' | 'github') => {
    setError('');
    setSsoLoading(provider);
    try {
      await auth.ssoRedirect(provider);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'SSO failed');
      setSsoLoading(null);
    }
  };

  return (
    <div className="max-w-md mx-auto mt-12 px-4">
      <h1 className="font-display font-semibold text-2xl mb-6">Sign in</h1>
      <form onSubmit={handleSubmit} className="card space-y-4">
        {error && <p className="text-rose-400 text-sm">{error}</p>}
        <div>
          <label className="block text-zinc-500 text-sm mb-1">Email</label>
          <input
            type="email"
            value={email}
            onChange={(e) => setEmail(e.target.value)}
            required
            autoComplete="email"
            className={`input ${fieldErrors.email ? 'border-rose-500' : ''}`}
          />
          {fieldErrors.email?.[0] && <p className="text-rose-400 text-xs mt-1">{fieldErrors.email[0]}</p>}
        </div>
        <div>
          <label className="block text-zinc-500 text-sm mb-1">Password</label>
          <input
            type="password"
            value={password}
            onChange={(e) => setPassword(e.target.value)}
            required
            autoComplete="current-password"
            className={`input ${fieldErrors.password ? 'border-rose-500' : ''}`}
          />
          {fieldErrors.password?.[0] && <p className="text-rose-400 text-xs mt-1">{fieldErrors.password[0]}</p>}
        </div>
        <button type="submit" disabled={loading} className="btn w-full">
          {loading ? 'Signing in…' : 'Sign in'}
        </button>
        <p className="text-center text-zinc-500 text-sm">Or sign in with:</p>
        <div className="flex gap-2">
          <button
            type="button"
            className="btn-secondary btn flex-1"
            onClick={() => handleSso('google')}
            disabled={!!ssoLoading}
          >
            {ssoLoading === 'google' ? 'Redirecting…' : 'Google'}
          </button>
          <button
            type="button"
            className="btn-secondary btn flex-1"
            onClick={() => handleSso('github')}
            disabled={!!ssoLoading}
          >
            {ssoLoading === 'github' ? 'Redirecting…' : 'GitHub'}
          </button>
        </div>
      </form>
      <p className="mt-4 text-zinc-500">
        No account? <Link to="/register" className="text-cyan-400 hover:underline">Register</Link>
      </p>
      <p className="mt-3 text-zinc-500 text-sm">
        Demo admin (add/edit/delete books):{' '}
        <button
          type="button"
          className="text-cyan-400 hover:underline"
          onClick={() => {
            setEmail('admin@example.com');
            setPassword('password');
          }}
        >
          Fill admin@example.com / password
        </button>
      </p>
    </div>
  );
}
