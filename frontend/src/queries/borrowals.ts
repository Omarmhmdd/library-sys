import { useCallback, useEffect, useState } from 'react';
import { borrowals as borrowalsApi } from '../api/client';
import type { Borrowal } from '../types';

export interface UseBorrowalsResult {
  data: Borrowal[];
  loading: boolean;
  error: string | null;
  refetch: () => Promise<void>;
}

export function useBorrowals(): UseBorrowalsResult {
  const [data, setData] = useState<Borrowal[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const refetch = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const res = await borrowalsApi.my({ per_page: 50 });
      const payload = res.data as { data?: Borrowal[] } | undefined;
      setData(payload && Array.isArray(payload.data) ? payload.data : []);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to load');
      setData([]);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    refetch();
  }, [refetch]);

  return { data, loading, error, refetch };
}
