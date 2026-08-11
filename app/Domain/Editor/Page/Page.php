<?php

namespace App\Domain\Editor\Page;

use Carbon\Carbon;

interface Page
{
    public string $title { get; }

    public PagePath $path { get; }

    public Markdown $content { get; }

    public Carbon $created_at { get; }

    public ?Carbon $published_at { get; }

    public bool $rss { get; }

    /** @var array<int, string> */
    public array $tags { get; }

    public ?string $cover_image { get; }

    public function isPublished(): bool;

    public function filename(): string;

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
