<?php

namespace App\Domain\Editor\Page;

use Carbon\Carbon;
use Illuminate\Support\Str;

class TagPage implements Page
{
    public function __construct(
        public PagePath $path,
        public string $title,
        public Markdown $content,
        public Carbon $created_at,
    ) {}

    public static function create(string $title): self
    {
        return new TagPage(
            path: new PagePath(config('pluma.tags.pages_path').'/'.Str::slug($title)),
            title: $title,
            content: new Markdown(''),
            created_at: Carbon::now(),
        );
    }

    public function filename(): string
    {
        return "{$this->path}.tag.md";
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'path' => (string) $this->path,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
