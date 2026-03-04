import { Navigate, useLocation } from 'react-router-dom';
import { BrowserRouter, Routes, Route } from 'react-router-dom';
import { AuthProvider } from './context/AuthContext';
import { useAuth } from './hooks';
import { Layout } from './components/Layout';
import { Login } from './pages/Login';
import { Register } from './pages/Register';
import { AuthCallback } from './pages/AuthCallback';
import { Books } from './pages/Books';
import { BookDetail } from './pages/BookDetail';
import { BookForm } from './pages/BookForm';
import { Borrowals } from './pages/Borrowals';
import { AiAsk } from './pages/AiAsk';

function ProtectedRoute({ children }: { children: React.ReactNode }) {
  const { user, loading } = useAuth();
  const location = useLocation();
  if (loading) return <div className="container" style={{ padding: '2rem' }}>Loading…</div>;
  if (!user) return <Navigate to="/login" state={{ from: location }} replace />;
  return <>{children}</>;
}

function AppRoutes() {
  return (
    <Routes>
      <Route path="/login" element={<Login />} />
      <Route path="/register" element={<Register />} />
      <Route path="/auth/callback" element={<AuthCallback />} />
      <Route
        path="/"
        element={
          <ProtectedRoute>
            <Layout />
          </ProtectedRoute>
        }
      >
        <Route index element={<Navigate to="/books" replace />} />
        <Route path="books" element={<Books />} />
        <Route path="books/new" element={<BookForm />} />
        <Route path="books/:id" element={<BookDetail />} />
        <Route path="books/:id/edit" element={<BookForm />} />
        <Route path="borrowals" element={<Borrowals />} />
        <Route path="ai" element={<AiAsk />} />
      </Route>
      <Route path="*" element={<Navigate to="/" replace />} />
    </Routes>
  );
}

export default function App() {
  return (
    <BrowserRouter future={{ v7_startTransition: true, v7_relativeSplatPath: true }}>
      <AuthProvider>
        <AppRoutes />
      </AuthProvider>
    </BrowserRouter>
  );
}
