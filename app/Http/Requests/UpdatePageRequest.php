<?php

namespace App\Http\Requests;

use App\Domain\Page\PageRepository;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

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
            'tags' => ['sometimes', 'array'],
            'tags.*' => ['string', 'max:255'],
        ];
    }

    public function after(): array
    {
        if (isset($this->input()['tags']) && is_array($this->input()['tags'])) {
            $this->merge([
                'tags' => array_map(fn ($tag) => trim((string) $tag), $this->input()['tags']),
            ]);
        }

        return [];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
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
