<?php

namespace App\Domain\Editor\Page;

use Carbon\Carbon;
use Illuminate\Support\Str;

class ContentPage implements Page
{
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

    public static function draft(string $title, string $path): self
    {
        return new ContentPage(
            title: $title,
            path: new PagePath($path),
            content: new Markdown(''),
            created_at: Carbon::now(),
            rss: true,
        );
    }

    public function rename(string $newTitle): void
    {
        $path = (string) $this->path;

        if (Str::slug($this->title) === Str::afterLast($path, '/')) {
            $newSlug = Str::slug($newTitle);
            $newPath = Str::contains($path, '/')
                ? Str::beforeLast($path, '/').'/'.$newSlug
                : $newSlug;

            $this->moveToPath(new PagePath($newPath));
        }

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

    public function changeCoverImage(string $coverImage): void
    {
        $this->cover_image = $coverImage;
    }

    public function removeCoverImage(): void
    {
        $this->cover_image = null;
    }

    public function publish(Carbon $publishedAt): void
    {
        $this->published_at ??= $publishedAt;
        $this->draft = false;
    }

    public function unpublish(): void
    {
        $this->draft = true;
    }

    public function changePublishedAt(?Carbon $publishedAt): void
    {
        if ($publishedAt === null && $this->isPublished()) {
            return;
        }

        $this->published_at = $publishedAt;
    }

    public function revise(Carbon $revisedAt): void
    {
        if ($this->published_at?->isSameDay($revisedAt)) {
            return;
        }

        $this->changeRevisedAt($revisedAt);
    }

    public function changeRevisedAt(?Carbon $revisedAt): void
    {
        if ($this->isDraft()) {
            return;
        }

        $this->revised_at = $revisedAt;
    }

    public function isPublished(): bool
    {
        return ! $this->draft;
    }

    public function isDraft(): bool
    {
        return $this->draft;
    }

    public function filename(): string
    {
        return "{$this->path}.md";
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $metadata = [
            'title' => $this->title,
            'path' => (string) $this->path,
            'cover_image' => $this->cover_image,
            'created_at' => $this->created_at->toIso8601String(),
            'draft' => $this->draft,
            'rss' => $this->rss,
        ];

        if ($this->published_at) {
            $metadata['published_at'] = $this->published_at->toIso8601String();
        }

        if ($this->revised_at) {
            $metadata['revised_at'] = $this->revised_at->toIso8601String();
        }

        if (! empty($this->tags)) {
            $metadata['tags'] = $this->tags;
        }

        return $metadata;
    }
}
