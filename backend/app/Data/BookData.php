<?php

namespace App\Data;

final class BookData
{
    public function __construct(
        public string $title,
        public string $author,
        public ?string $description = null,
        public ?string $genre = null,
        public ?string $isbn = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            title: $data['title'],
            author: $data['author'],
            description: $data['description'] ?? null,
            genre: $data['genre'] ?? null,
            isbn: $data['isbn'] ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'title' => $this->title,
            'author' => $this->author,
            'description' => $this->description,
            'genre' => $this->genre,
            'isbn' => $this->isbn,
        ], fn ($v) => $v !== null);
    }
}
