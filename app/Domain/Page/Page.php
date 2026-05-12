<?php

namespace App\Domain\Page;

use Carbon\Carbon;
use Illuminate\Support\Str;

class Page
{
    public function __construct(
        public string $title,
        public PagePath $path,
        public Markdown $content,
        public Carbon $created_at,
        public ?Carbon $published_at = null,
        public bool $rss = false,
        public array $tags = [],
    ) {}

    public static function draft(string $title): self
    {
        return new Page(
            title: $title,
            path: new PagePath(Str::slug($title)),
            content: new Markdown(''),
            created_at: Carbon::now(),
            rss: true,
        );
    }

    public function rename(string $newTitle): void
    {
        $this->title = $newTitle;
    }

    public function moveToPath(PagePath $newPath): void
    {
        $this->path = $newPath;
    }

    public function setContent(Markdown $newContent): void
    {
        $this->content = $newContent;
    }

    public function toggleRss(bool $enabled): void
    {
        $this->rss = $enabled;
    }

    public function withTags(array $tags): void
    {
        $this->tags = array_values($tags);
    }

    public function publish(Carbon $publishedAt): void
    {
        $this->published_at = $publishedAt;
    }

    public function unpublish(): void
    {
        $this->published_at = null;
    }

    public function isPublished(): bool
    {
        return $this->published_at !== null;
    }

    public function isDraft(): bool
    {
        return ! $this->isPublished();
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $metadata = [
            'title' => $this->title,
            'path' => (string) $this->path,
            'created_at' => $this->created_at->toIso8601String(),
            'rss' => $this->rss,
        ];

        if ($this->published_at) {
            $metadata['published_at'] = $this->published_at->toIso8601String();
        }

        if (! empty($this->tags)) {
            $metadata['tags'] = $this->tags;
        }

        return $metadata;
    }
}
