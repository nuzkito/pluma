<?php

namespace App\Domain\Generator\Page;

use Carbon\Carbon;

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
        public ?string $cover_image = null,
    ) {}

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
