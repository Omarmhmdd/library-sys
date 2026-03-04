<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Book extends Model
{
    protected $fillable = [
        'title',
        'author',
        'description',
        'genre',
        'isbn',
        'published_year',
    ];

    protected function casts(): array
    {
        return [
            'published_year' => 'integer',
        ];
    }

    public function borrowals(): HasMany
    {
        return $this->hasMany(Borrowal::class);
    }

    public function isBorrowed(): bool
    {
        return $this->borrowals()->whereNull('returned_at')->exists();
    }
}
