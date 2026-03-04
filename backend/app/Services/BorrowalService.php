<?php

namespace App\Services;

use App\Models\Book;
use App\Models\Borrowal;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class BorrowalService extends BaseService
{
    public function borrow(User $user, int $bookId): Borrowal
    {
        $book = Book::find($bookId);
        if (! $book) {
            throw new \InvalidArgumentException('Book not found.');
        }
        if ($book->isBorrowed()) {
            throw new \InvalidArgumentException('Book is already borrowed.');
        }
        return Borrowal::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'borrowed_at' => now(),
        ]);
    }

    public function returnBook(User $user, int $bookId): Borrowal
    {
        $borrowal = Borrowal::where('user_id', $user->id)
            ->where('book_id', $bookId)
            ->whereNull('returned_at')
            ->first();
        if (! $borrowal) {
            throw new \InvalidArgumentException('No active borrowal found for this book.');
        }
        $borrowal->update(['returned_at' => now()]);
        return $borrowal->fresh();
    }

    /** @return LengthAwarePaginator<Borrowal> */
    public function myBorrowals(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return Borrowal::where('user_id', $user->id)
            ->with('book')
            ->orderByDesc('borrowed_at')
            ->paginate($perPage);
    }
}
