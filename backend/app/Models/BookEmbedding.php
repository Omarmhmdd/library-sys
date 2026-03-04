<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookEmbedding extends Model
{
    protected $table = 'book_embeddings';

    protected $fillable = ['book_id', 'embedding'];

    protected function casts(): array
    {
        return [
            'embedding' => 'array',
        ];
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }
}
