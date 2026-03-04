<?php

namespace Database\Seeders;

use App\Models\Book;
use Illuminate\Database\Seeder;

class BookSeeder extends Seeder
{
    public function run(): void
    {
        $books = [
            [
                'title' => '1984',
                'author' => 'George Orwell',
                'description' => 'A dystopian social science fiction novel about totalitarianism and surveillance.',
                'genre' => 'Fiction',
                'isbn' => '978-0451524935',
                'published_year' => 1949,
            ],
            [
                'title' => 'To Kill a Mockingbird',
                'author' => 'Harper Lee',
                'description' => 'A novel about racial injustice and moral growth in the American South.',
                'genre' => 'Fiction',
                'isbn' => '978-0061120084',
                'published_year' => 1960,
            ],
            [
                'title' => 'The Great Gatsby',
                'author' => 'F. Scott Fitzgerald',
                'description' => 'A story of decadence and the American Dream in the Jazz Age.',
                'genre' => 'Fiction',
                'isbn' => '978-0743273565',
                'published_year' => 1925,
            ],
            [
                'title' => 'Clean Code',
                'author' => 'Robert C. Martin',
                'description' => 'A handbook of agile software craftsmanship.',
                'genre' => 'Technology',
                'isbn' => '978-0132350884',
                'published_year' => 2008,
            ],
            [
                'title' => 'The Pragmatic Programmer',
                'author' => 'David Thomas, Andrew Hunt',
                'description' => 'Your journey to mastery in software development.',
                'genre' => 'Technology',
                'isbn' => '978-0135957059',
                'published_year' => 2019,
            ],
        ];

        foreach ($books as $book) {
            Book::firstOrCreate(
                ['isbn' => $book['isbn']],
                $book
            );
        }
    }
}
