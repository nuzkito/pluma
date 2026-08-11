<?php

namespace App\Domain\Editor\Page;

use Carbon\Carbon;
use Illuminate\Support\Str;

class TagPage implements Page
{
    public bool $rss {
        get => false;
    }

    /** @var array<int, string> */
    public array $tags {
        get => [];
    }

    public ?Carbon $published_at {
        get => $this->created_at;
    }

    public function __construct(
        public PagePath $path,
        public string $title,
        public Markdown $content,
        public Carbon $created_at,
        public ?string $cover_image = null,
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

    public function setContent(Markdown $newContent): void
    {
        $this->content = $newContent;
    }

    public function changeCoverImage(string $coverImage): void
    {
        $this->cover_image = $coverImage;
    }

    public function removeCoverImage(): void
    {
        $this->cover_image = null;
    }

    public function isPublished(): bool
    {
        return $this->published_at !== null;
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
            'cover_image' => $this->cover_image,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
