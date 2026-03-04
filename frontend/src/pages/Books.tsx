import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { useBooksList } from '../queries';

export function Books() {
  const [search, setSearch] = useState('');
  const [author, setAuthor] = useState('');
  const [genre, setGenre] = useState('');
  const { data: list, meta, loading, error, refetch } = useBooksList();

  useEffect(() => {
    refetch();
  }, [refetch]);

  const handleSearch = (e: React.FormEvent) => {
    e.preventDefault();
    refetch({ search: search || undefined, author: author || undefined, genre: genre || undefined, per_page: 12 });
  };

  return (
    <div className="max-w-4xl mx-auto px-4">
      <h1 className="font-display font-semibold text-2xl mb-2">Books</h1>
      <p className="text-zinc-500 text-sm mb-6">Click a book to view details, borrow or return it. Staff can edit or delete from the book page.</p>
      <form onSubmit={handleSearch} className="flex flex-wrap gap-2 mb-6">
        <input
          type="text"
          placeholder="Search..."
          value={search}
          onChange={(e) => setSearch(e.target.value)}
          className="input max-w-[200px]"
        />
        <input
          type="text"
          placeholder="Author"
          value={author}
          onChange={(e) => setAuthor(e.target.value)}
          className="input max-w-[160px]"
        />
        <input
          type="text"
          placeholder="Genre"
          value={genre}
          onChange={(e) => setGenre(e.target.value)}
          className="input max-w-[120px]"
        />
        <button type="submit" className="btn">Search</button>
      </form>
      {error && <p className="text-rose-400 text-sm mb-4">{error}</p>}
      {loading ? (
        <p className="text-zinc-500">Loading...</p>
      ) : list.length === 0 ? (
        <p className="text-zinc-500">No books found.</p>
      ) : (
        <ul className="list-none p-0 grid gap-4 grid-cols-[repeat(auto-fill,minmax(280px,1fr))]">
          {list.map((book) => (
            <li key={book.id} className="card">
              <Link to={`/books/${book.id}`} className="text-inherit no-underline hover:text-cyan-400 transition-colors">
                <h3 className="font-display font-semibold text-lg mt-0 mb-1">{book.title}</h3>
                <p className="text-zinc-500 m-0">{book.author}</p>
                {book.genre && <span className="text-xs text-cyan-400">{book.genre}</span>}
              </Link>
            </li>
          ))}
        </ul>
      )}
      {meta && meta.total > list.length && (
        <p className="text-zinc-500 text-sm mt-4">
          Page {meta.current_page} of {meta.last_page} ({meta.total} total)
        </p>
      )}
    </div>
  );
}
