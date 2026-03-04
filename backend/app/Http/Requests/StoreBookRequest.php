<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class StoreBookRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'author' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'genre' => ['nullable', 'string', 'max:100'],
            'isbn' => ['nullable', 'string', 'max:20', 'unique:books,isbn'],
            'published_year' => ['nullable', 'integer', 'min:1000', 'max:2100'],
        ];
    }
}
