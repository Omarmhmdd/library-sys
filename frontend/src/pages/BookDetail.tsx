import { useParams, useNavigate, Link } from 'react-router-dom';
import { useBookDetail } from '../queries';
import { useAuth } from '../hooks';
import { books } from '../api/client';

export function BookDetail() {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const { canManageBooks } = useAuth();
  const bookId = id ? parseInt(id, 10) : NaN;
  const { book, similar, loading, error, borrow, returnBook, borrowLoading, returnLoading } = useBookDetail(Number.isNaN(bookId) ? null : bookId);

  const handleDelete = async () => {
    if (!book || !confirm('Delete this book?')) return;
    try {
      await books.delete(book.id);
      navigate('/books');
    } catch {}
  };

  if (loading && !book) {
    return (
      <div className="max-w-4xl mx-auto px-4">
        <p className="text-zinc-500">{error || 'Loading…'}</p>
      </div>
    );
  }
  if (!book) {
    return (
      <div className="max-w-4xl mx-auto px-4">
        <p className="text-zinc-500">Book not found.</p>
        <Link to="/books" className="text-cyan-400 hover:underline">Back to books</Link>
      </div>
    );
  }

  return (
    <div className="max-w-4xl mx-auto px-4">
      <Link to="/books" className="text-zinc-500 hover:text-cyan-400 inline-block mb-4">← Back to books</Link>
      {error && <p className="text-rose-400 text-sm mb-4">{error}</p>}
      <div className="card mb-6">
        <h1 className="font-display font-semibold text-2xl mt-0 mb-1">{book.title}</h1>
        <p className="text-zinc-500 mb-2">{book.author}</p>
        {book.genre && <span className="text-cyan-400 text-sm">{book.genre}</span>}
        {book.description && <p className="mt-4">{book.description}</p>}
        {book.isbn && <p className="text-sm text-zinc-500 mt-2">ISBN: {book.isbn}</p>}
        {book.published_year && <p className="text-sm text-zinc-500">Year: {book.published_year}</p>}
        <div className="flex flex-wrap gap-2 mt-4">
          <button onClick={() => borrow()} disabled={borrowLoading} className="btn">
            {borrowLoading ? 'Borrowing…' : 'Borrow'}
          </button>
          <button onClick={() => returnBook()} disabled={returnLoading} className="btn-secondary btn">
            {returnLoading ? 'Returning…' : 'Return'}
          </button>
          {canManageBooks && (
            <>
              <Link to={`/books/${book.id}/edit`}><button className="btn-secondary btn">Edit</button></Link>
              <button onClick={handleDelete} className="btn-danger btn">Delete</button>
            </>
          )}
        </div>
      </div>
      {similar.length > 0 && (
        <section>
          <h2 className="font-display font-semibold text-xl mb-2">Similar books</h2>
          <ul className="list-none p-0 flex flex-col gap-2">
            {similar.map((b) => (
              <li key={b.id}>
                <Link to={`/books/${b.id}`} className="text-cyan-400 hover:underline">{b.title}</Link>
                <span className="text-zinc-500"> — {b.author}</span>
              </li>
            ))}
          </ul>
        </section>
      )}
    </div>
  );
}
