export type Role = 'admin' | 'librarian' | 'member';

export interface User {
  id: number;
  name: string;
  email: string;
  role: Role;
}

export interface Book {
  id: number;
  title: string;
  author: string;
  description?: string;
  genre?: string;
  isbn?: string;
  published_year?: number;
}

export interface Borrowal {
  id: number;
  book: Book;
  borrowed_at: string;
  returned_at: string | null;
  is_active: boolean;
}

export interface Paginated<T> {
  data: T[];
  meta: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
}

export interface ApiResponse<T = unknown> {
  message: string;
  data?: T;
  errors?: Record<string, string[]>;
}
