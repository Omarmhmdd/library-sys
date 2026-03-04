<?php

namespace App\Services;

use App\Models\Book;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

final class BookService extends BaseService
{
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Book::query()->orderBy('title');

        if (! empty($filters['search'])) {
            $term = $filters['search'];
            $query->where(function (Builder $q) use ($term) {
                $q->where('title', 'like', "%{$term}%")
                    ->orWhere('author', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%")
                    ->orWhere('genre', 'like', "%{$term}%")
                    ->orWhere('isbn', 'like', "%{$term}%");
            });
        }
        if (! empty($filters['author'])) {
            $query->where('author', 'like', '%'.$filters['author'].'%');
        }
        if (! empty($filters['genre'])) {
            $query->where('genre', 'like', '%'.$filters['genre'].'%');
        }
        if (! empty($filters['title'])) {
            $query->where('title', 'like', '%'.$filters['title'].'%');
        }

        return $query->paginate($perPage);
    }

    public function find(int $id): ?Book
    {
        return Book::find($id);
    }

    public function store(array $data): Book
    {
        return Book::create($data);
    }

    public function update(Book $book, array $data): Book
    {
        $book->update($data);
        return $book->fresh();
    }

    public function delete(Book $book): void
    {
        $book->delete();
    }
}
