import { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useAuth } from '../hooks';

export function Register() {
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [password_confirmation, setPasswordConfirmation] = useState('');
  const [error, setError] = useState('');
  const [fieldErrors, setFieldErrors] = useState<Record<string, string[]>>({});
  const [loading, setLoading] = useState(false);
  const { register } = useAuth();
  const navigate = useNavigate();

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');
    setFieldErrors({});
    if (password !== password_confirmation) {
      setError('Passwords do not match');
      return;
    }
    setLoading(true);
    try {
      await register(name, email, password, password_confirmation);
      navigate('/books');
    } catch (err) {
      const e = err as Error & { errors?: Record<string, string[]> };
      setError(e.message ?? 'Registration failed');
      setFieldErrors(e.errors ?? {});
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="max-w-md mx-auto mt-12 px-4">
      <h1 className="font-display font-semibold text-2xl mb-6">Register</h1>
      <form onSubmit={handleSubmit} className="card space-y-4">
        {error && <p className="text-rose-400 text-sm">{error}</p>}
        <div>
          <label className="block text-zinc-500 text-sm mb-1">Name</label>
          <input type="text" value={name} onChange={(e) => setName(e.target.value)} required autoComplete="name" className={`input ${fieldErrors.name ? 'border-rose-500' : ''}`} />
          {fieldErrors.name?.[0] && <p className="text-rose-400 text-xs mt-1">{fieldErrors.name[0]}</p>}
        </div>
        <div>
          <label className="block text-zinc-500 text-sm mb-1">Email</label>
          <input type="email" value={email} onChange={(e) => setEmail(e.target.value)} required autoComplete="email" className={`input ${fieldErrors.email ? 'border-rose-500' : ''}`} />
          {fieldErrors.email?.[0] && <p className="text-rose-400 text-xs mt-1">{fieldErrors.email[0]}</p>}
        </div>
        <div>
          <label className="block text-zinc-500 text-sm mb-1">Password</label>
          <input type="password" value={password} onChange={(e) => setPassword(e.target.value)} required minLength={8} autoComplete="new-password" className={`input ${fieldErrors.password ? 'border-rose-500' : ''}`} />
          {fieldErrors.password?.[0] && <p className="text-rose-400 text-xs mt-1">{fieldErrors.password[0]}</p>}
        </div>
        <div>
          <label className="block text-zinc-500 text-sm mb-1">Confirm password</label>
          <input type="password" value={password_confirmation} onChange={(e) => setPasswordConfirmation(e.target.value)} required autoComplete="new-password" className="input" />
        </div>
        <button type="submit" disabled={loading} className="btn w-full">
          {loading ? 'Creating account…' : 'Register'}
        </button>
      </form>
      <p className="mt-4 text-zinc-500">
        Already have an account? <Link to="/login" className="text-cyan-400 hover:underline">Sign in</Link>
      </p>
    </div>
  );
}
