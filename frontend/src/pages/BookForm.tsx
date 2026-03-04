import { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { books } from '../api/client';
import { useSuggestMetadata } from '../queries';
import type { Book } from '../types';

export function BookForm() {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const isEdit = id !== undefined && id !== 'new';
  const bookId = isEdit && id ? parseInt(id, 10) : NaN;

  const [title, setTitle] = useState('');
  const [author, setAuthor] = useState('');
  const [description, setDescription] = useState('');
  const [genre, setGenre] = useState('');
  const [isbn, setIsbn] = useState('');
  const [published_year, setPublishedYear] = useState('');
  const [loading, setLoading] = useState(false);
  const [loadingBook, setLoadingBook] = useState(isEdit);
  const [error, setError] = useState('');
  const { suggest, loading: suggesting } = useSuggestMetadata();

  useEffect(() => {
    if (!isEdit || Number.isNaN(bookId)) return;
    let cancelled = false;
    (async () => {
      try {
        const res = await books.get(bookId);
        const b = (res.data as Book) ?? res.data;
        if (cancelled || !b) return;
        setTitle(b.title ?? '');
        setAuthor(b.author ?? '');
        setDescription(b.description ?? '');
        setGenre(b.genre ?? '');
        setIsbn(b.isbn ?? '');
        setPublishedYear(b.published_year ? String(b.published_year) : '');
      } catch {
        if (!cancelled) setError('Failed to load book');
      } finally {
        if (!cancelled) setLoadingBook(false);
      }
    })();
    return () => { cancelled = true; };
  }, [isEdit, bookId]);

  const handleSuggest = async () => {
    if (!title.trim() || !author.trim()) return;
    setError('');
    const data = await suggest(title.trim(), author.trim());
    if (data.genre) setGenre(data.genre);
    if (data.description) setDescription(data.description);
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');
    setLoading(true);
    try {
      const payload = {
        title: title.trim(),
        author: author.trim(),
        description: description.trim() || undefined,
        genre: genre.trim() || undefined,
        isbn: isbn.trim() || undefined,
        published_year: published_year ? parseInt(published_year, 10) : undefined,
      };
      if (isEdit) {
        await books.update(bookId, payload);
        navigate(`/books/${bookId}`);
      } else {
        const res = await books.create(payload);
        const created = (res.data as Book) ?? res.data;
        if (created?.id) navigate(`/books/${created.id}`);
        else navigate('/books');
      }
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Save failed');
    } finally {
      setLoading(false);
    }
  };

  if (loadingBook) {
    return <div className="max-w-4xl mx-auto px-4"><p className="text-zinc-500">Loading…</p></div>;
  }

  return (
    <div className="max-w-xl mx-auto px-4">
      <h1 className="font-display font-semibold text-2xl mb-2">{isEdit ? 'Edit book' : 'Add book'}</h1>
      <p className="text-zinc-500 text-sm mb-6">
        AI feature: enter title and author, then click <strong>Suggest genre & description (AI)</strong> to fill genre and description automatically.
      </p>
      <form onSubmit={handleSubmit} className="card space-y-4">
        {error && <p className="text-rose-400 text-sm">{error}</p>}
        <div>
          <label className="block text-zinc-500 text-sm mb-1">Title *</label>
          <input value={title} onChange={(e) => setTitle(e.target.value)} required className="input" />
        </div>
        <div>
          <label className="block text-zinc-500 text-sm mb-1">Author *</label>
          <input value={author} onChange={(e) => setAuthor(e.target.value)} required className="input" />
        </div>
        <div className="flex items-center gap-2 flex-wrap">
          <span className="text-zinc-500 text-sm">3rd AI feature:</span>
          <button type="button" className="btn" onClick={handleSuggest} disabled={suggesting || !title.trim() || !author.trim()}>
            {suggesting ? 'Suggesting…' : 'Suggest genre & description (AI)'}
          </button>
        </div>
        <div>
          <label className="block text-zinc-500 text-sm mb-1">Genre</label>
          <input value={genre} onChange={(e) => setGenre(e.target.value)} className="input" />
        </div>
        <div>
          <label className="block text-zinc-500 text-sm mb-1">Description</label>
          <textarea value={description} onChange={(e) => setDescription(e.target.value)} rows={4} className="input" />
        </div>
        <div>
          <label className="block text-zinc-500 text-sm mb-1">ISBN</label>
          <input value={isbn} onChange={(e) => setIsbn(e.target.value)} className="input" />
        </div>
        <div>
          <label className="block text-zinc-500 text-sm mb-1">Published year</label>
          <input type="number" min={1000} max={2100} value={published_year} onChange={(e) => setPublishedYear(e.target.value)} className="input" />
        </div>
        <div className="flex gap-2 pt-2">
          <button type="submit" disabled={loading} className="btn">{loading ? 'Saving…' : 'Save'}</button>
          <button type="button" className="btn-secondary btn" onClick={() => navigate(-1)}>Cancel</button>
        </div>
      </form>
    </div>
  );
}
