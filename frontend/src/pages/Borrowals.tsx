import { Link } from 'react-router-dom';
import { useBorrowals } from '../queries';

export function Borrowals() {
  const { data: list, loading, error } = useBorrowals();

  if (loading) {
    return (
      <div className="max-w-4xl mx-auto px-4">
        <p className="text-zinc-500">Loading…</p>
      </div>
    );
  }
  if (error) {
    return (
      <div className="max-w-4xl mx-auto px-4">
        <p className="text-rose-400">{error}</p>
      </div>
    );
  }

  const active = list.filter((b) => b.is_active);
  const past = list.filter((b) => !b.is_active);

  return (
    <div className="max-w-4xl mx-auto px-4">
      <h1 className="font-display font-semibold text-2xl mb-6">My borrowals</h1>
      {active.length > 0 && (
        <section className="mb-8">
          <h2 className="font-display font-semibold text-lg mb-3">Currently borrowed</h2>
          <ul className="list-none p-0 space-y-3">
            {active.map((b) => (
              <li key={b.id} className="card">
                <Link to={`/books/${b.book.id}`} className="font-medium text-cyan-400 hover:underline">{b.book.title}</Link>
                <span className="text-zinc-500"> — {b.book.author}</span>
                <p className="text-sm text-zinc-500 mt-1 mb-0">
                  Borrowed {new Date(b.borrowed_at).toLocaleDateString()}
                </p>
                <Link to={`/books/${b.book.id}`}><button className="btn-secondary btn mt-2">Return book</button></Link>
              </li>
            ))}
          </ul>
        </section>
      )}
      {past.length > 0 && (
        <section>
          <h2 className="font-display font-semibold text-lg mb-3">Past borrowals</h2>
          <ul className="list-none p-0 space-y-3">
            {past.map((b) => (
              <li key={b.id} className="card opacity-90">
                <Link to={`/books/${b.book.id}`} className="text-cyan-400 hover:underline">{b.book.title}</Link>
                <span className="text-zinc-500"> — {b.book.author}</span>
                <p className="text-sm text-zinc-500 mt-1 mb-0">
                  Returned {b.returned_at ? new Date(b.returned_at).toLocaleDateString() : '—'}
                </p>
              </li>
            ))}
          </ul>
        </section>
      )}
      {list.length === 0 && (
        <p className="text-zinc-500">
          You have no borrowals yet. <Link to="/books" className="text-cyan-400 hover:underline">Browse books</Link>.
        </p>
      )}
    </div>
  );
}
