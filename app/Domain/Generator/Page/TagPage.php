<?php

namespace App\Domain\Generator\Page;

use Carbon\Carbon;

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
        public string $title,
        public PagePath $path,
        public Markdown $content,
        public Carbon $created_at,
        public ?string $cover_image = null,
        public ?Carbon $revised_at = null,
    ) {}

    public function isPublished(): bool
    {
        return $this->published_at !== null;
    }
}
