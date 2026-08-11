<?php

namespace App\Domain\Generator\Page;

use Carbon\Carbon;

class ContentPage implements Page
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
}
