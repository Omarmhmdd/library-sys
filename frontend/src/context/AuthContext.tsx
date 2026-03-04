import {
  createContext,
  useCallback,
  useContext,
  useEffect,
  useState,
  type ReactNode,
} from 'react';
import { auth } from '../api/client';
import type { User } from '../types';

interface AuthContextValue {
  user: User | null;
  loading: boolean;
  login: (email: string, password: string) => Promise<void>;
  register: (name: string, email: string, password: string, password_confirmation: string) => Promise<void>;
  logout: () => Promise<void>;
  refresh: () => Promise<void>;
  canManageBooks: boolean;
}

const AuthContext = createContext<AuthContextValue | null>(null);

export function AuthProvider({ children }: { children: ReactNode }) {
  const [user, setUser] = useState<User | null>(null);
  const [loading, setLoading] = useState(true);

  const refresh = useCallback(async () => {
    const token = localStorage.getItem('token');
    if (!token) {
      setUser(null);
      setLoading(false);
      return;
    }
    try {
      const data = await auth.me();
      setUser(data ?? null);
    } catch {
      localStorage.removeItem('token');
      setUser(null);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    refresh();
  }, [refresh]);

  const login = useCallback(async (email: string, password: string) => {
    const data = await auth.login(email, password);
    setUser(data?.user ?? null);
  }, []);

  const register = useCallback(
    async (name: string, email: string, password: string, password_confirmation: string) => {
      const data = await auth.register(name, email, password, password_confirmation);
      setUser(data?.user ?? null);
    },
    []
  );

  const logout = useCallback(async () => {
    await auth.logout();
    setUser(null);
  }, []);

  const canManageBooks = user?.role === 'admin' || user?.role === 'librarian' || false;

  return (
    <AuthContext.Provider
      value={{
        user,
        loading,
        login,
        register,
        logout,
        refresh,
        canManageBooks,
      }}
    >
      {children}
    </AuthContext.Provider>
  );
}

export function useAuth() {
  const ctx = useContext(AuthContext);
  if (!ctx) throw new Error('useAuth must be used within AuthProvider');
  return ctx;
}
