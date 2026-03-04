import { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useAuth } from '../hooks';

/**
 * SSO callback: backend redirects here with token in hash (#token=...).
 * We store the token and redirect to the app.
 */
export function AuthCallback() {
  const navigate = useNavigate();
  const { refresh } = useAuth();
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    const hash = window.location.hash.slice(1);
    const params = new URLSearchParams(hash);
    const token = params.get('token');
    if (token) {
      localStorage.setItem('token', token);
      refresh().then(() => navigate('/books', { replace: true }));
    } else {
      setError('No token received');
    }
  }, [navigate, refresh]);

  if (error) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-[#0f0f12]">
        <div className="card max-w-md text-center">
          <p className="text-rose-400">{error}</p>
          <a href="/login" className="text-cyan-400 hover:underline mt-4 inline-block">Back to login</a>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen flex items-center justify-center bg-[#0f0f12]">
      <p className="text-zinc-400">Signing you in…</p>
    </div>
  );
}
