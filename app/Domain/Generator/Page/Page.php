<?php

namespace App\Domain\Generator\Page;

use App\Domain\Generator\Tag\Tag;
use Carbon\Carbon;

class Page
{
    /**
     * @param  array<int, Tag>  $tags
     */
    public function __construct(
        public string $title,
        public PagePath $path,
        public Markdown $content,
        public Carbon $created_at,
        public ?Carbon $published_at = null,
        public bool $draft = true,
        public ?Carbon $revised_at = null,
        public bool $rss = false,
        public array $tags = [],
        public ?string $cover_image = null,
    ) {}

    public function isPublished(): bool
    {
        return ! $this->draft;
    }

    public function isDraft(): bool
    {
        return $this->draft;
    }

    public function hasTag(string $id): bool
    {
        return collect($this->tags)->contains(fn (Tag $tag) => $tag->id === $id);
    }
}
