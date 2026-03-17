<?php

namespace App\Http\Requests;

use App\Domain\Page\PageRepository;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'path' => ['sometimes', 'string', 'max:255'],
            'content' => ['sometimes', 'string'],
            'rss' => ['sometimes', 'boolean'],
            'published_at' => ['sometimes', 'nullable', 'date'],
        ];
    }

    public function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        $validator->after(function (\Illuminate\Validation\Validator $validator) {
            if ($this->has('path') && $this->input('path') !== '') {
                $currentPath = $this->route('path');
                $repository = app(PageRepository::class);

                if ($repository->pathExists($this->input('path'), $currentPath)) {
                    $validator->errors()->add('path', 'A page with this path already exists.');
                }
            }
        });
    }
}
