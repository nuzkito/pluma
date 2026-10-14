<?php

namespace App\Domain\Generator\Tag;

use App\Domain\Generator\Page\Markdown;
use App\Domain\Generator\Page\PagePath;
use Illuminate\Support\Str;
use Stringable;

class Tag implements Stringable
{
    public PagePath $path {
        get => new PagePath(config('pluma.tags.pages_path').'/'.$this->slug);
    }

    public function __construct(
        public string $id,
        public string $name,
        public string $slug,
        public Markdown $content,
        public ?string $cover_image = null,
        private bool $isDefined = true,
    ) {}

    public static function undefined(string $id): self
    {
        return new self(
            id: $id,
            name: $id,
            slug: Str::slug($id),
            content: new Markdown(''),
            isDefined: false,
        );
    }

    public function hasPage(): bool
    {
        return $this->isDefined && (bool) config('pluma.tags.create_pages');
    }

    public function assetsPath(): string
    {
        return "tags/{$this->id}";
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
