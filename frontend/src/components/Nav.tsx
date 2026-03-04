import { Link, useNavigate } from 'react-router-dom';
import { useAuth } from '../hooks';

export function Nav() {
  const { user, logout } = useAuth();
  const navigate = useNavigate();

  const handleLogout = async () => {
    await logout();
    navigate('/login');
  };

  return (
    <nav className="border-b border-zinc-700 px-4 py-3 flex items-center gap-6 flex-wrap">
      <Link to="/" className="font-display font-bold text-xl text-zinc-100 hover:text-cyan-400 transition-colors">
        Library
      </Link>
      <Link to="/books" className="text-zinc-300 hover:text-cyan-400 transition-colors">Books</Link>
      <Link to="/borrowals" className="text-zinc-300 hover:text-cyan-400 transition-colors">My borrowals</Link>
      <Link to="/ai" className="text-zinc-300 hover:text-cyan-400 transition-colors">Ask AI</Link>
      <Link to="/books/new" className="text-zinc-300 hover:text-cyan-400 transition-colors">Add book</Link>
      <span className="ml-auto text-zinc-500 text-sm">{user?.name} ({user?.role})</span>
      <button type="button" className="btn-secondary btn" onClick={handleLogout}>Logout</button>
    </nav>
  );
}
