import { useCallback, useState } from 'react';
import { ai as aiApi } from '../api/client';

export interface UseAiAskResult {
  answer: string | null;
  loading: boolean;
  error: string | null;
  ask: (question: string) => Promise<void>;
}

export function useAiAsk(): UseAiAskResult {
  const [answer, setAnswer] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const ask = useCallback(async (question: string) => {
    const q = question.trim();
    if (!q) return;
    setLoading(true);
    setError(null);
    setAnswer(null);
    try {
      const res = await aiApi.ask(q);
      const data = res.data as { answer?: string } | undefined;
      setAnswer(data?.answer ?? '');
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Request failed');
    } finally {
      setLoading(false);
    }
  }, []);

  return { answer, loading, error, ask };
}

export interface UseSuggestMetadataResult {
  suggest: (title: string, author: string) => Promise<{ genre?: string; description?: string }>;
  loading: boolean;
  error: string | null;
}

export function useSuggestMetadata(): UseSuggestMetadataResult {
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const suggest = useCallback(async (title: string, author: string) => {
    setLoading(true);
    setError(null);
    try {
      const res = await aiApi.suggestMetadata(title.trim(), author.trim());
      const data = (res.data as { genre?: string; description?: string }) ?? {};
      return data;
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Suggestion failed');
      return {};
    } finally {
      setLoading(false);
    }
  }, []);

  return { suggest, loading, error };
}
