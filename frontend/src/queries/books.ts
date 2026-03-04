import { useCallback, useEffect, useState } from 'react';
import { books as booksApi } from '../api/client';
import type { Book } from '../types';

export interface BooksListParams {
  search?: string;
  author?: string;
  genre?: string;
  title?: string;
  per_page?: number;
}

export interface BooksListResult {
  data: Book[];
  meta: { current_page: number; last_page: number; per_page: number; total: number } | null;
  loading: boolean;
  error: string | null;
  refetch: (params?: BooksListParams) => Promise<void>;
}

export function useBooksList(initialParams: BooksListParams = {}): BooksListResult {
  const [data, setData] = useState<Book[]>([]);
  const [meta, setMeta] = useState<BooksListResult['meta']>(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const refetch = useCallback(async (params: BooksListParams = {}) => {
    setLoading(true);
    setError(null);
    try {
      const res = await booksApi.list({ per_page: 12, ...initialParams, ...params });
      const payload = res.data as { data?: Book[]; meta?: BooksListResult['meta'] } | undefined;
      setData(payload && Array.isArray(payload.data) ? payload.data : []);
      setMeta(payload?.meta ?? null);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to load books');
      setData([]);
    } finally {
      setLoading(false);
    }
  }, []);

  return { data, meta, loading, error, refetch };
}

export interface BookDetailResult {
  book: Book | null;
  similar: Book[];
  loading: boolean;
  error: string | null;
  refetch: () => Promise<void>;
  borrow: () => Promise<void>;
  returnBook: () => Promise<void>;
  borrowLoading: boolean;
  returnLoading: boolean;
}

export function useBookDetail(id: number | null): BookDetailResult {
  const [book, setBook] = useState<Book | null>(null);
  const [similar, setSimilar] = useState<Book[]>([]);
  const [loading, setLoading] = useState(!!id);
  const [error, setError] = useState<string | null>(null);
  const [borrowLoading, setBorrowLoading] = useState(false);
  const [returnLoading, setReturnLoading] = useState(false);

  const refetch = useCallback(async () => {
    if (id == null) return;
    setLoading(true);
    setError(null);
    try {
      const [resBook, resSimilar] = await Promise.all([
        booksApi.get(id),
        booksApi.similar(id).catch(() => ({ data: { data: [] } })),
      ]);
      setBook((resBook.data as Book) ?? null);
      const sim = (resSimilar.data as { data?: Book[] })?.data ?? [];
      setSimilar(Array.isArray(sim) ? sim : []);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to load');
    } finally {
      setLoading(false);
    }
  }, [id]);

  const borrow = useCallback(async () => {
    if (!book) return;
    setBorrowLoading(true);
    try {
      await booksApi.borrow(book.id);
      await refetch();
    } finally {
      setBorrowLoading(false);
    }
  }, [book, refetch]);

  const returnBook = useCallback(async () => {
    if (!book) return;
    setReturnLoading(true);
    try {
      await booksApi.return(book.id);
      await refetch();
    } finally {
      setReturnLoading(false);
    }
  }, [book, refetch]);

  useEffect(() => {
    if (id != null) refetch();
  }, [id, refetch]);

  return {
    book,
    similar,
    loading,
    error,
    refetch,
    borrow,
    returnBook,
    borrowLoading,
    returnLoading,
  };
}
