<?php

namespace App\Domain\Editor\Tag;

use App\Domain\Editor\Page\Markdown;
use App\Domain\Editor\Page\PagePath;
use Carbon\Carbon;
use Illuminate\Support\Str;

class Tag
{
    private const string IDENTIFIER_PATTERN = '/^[A-Za-z0-9-]+$/';

    public string $path {
        get => config('pluma.tags.pages_path').'/'.$this->slug;
    }

    public function __construct(
        public readonly string $id,
        public string $name,
        public string $slug,
        public Markdown $content,
        public Carbon $created_at,
        public ?string $cover_image = null,
    ) {}

    public static function create(string $id, string $name): self
    {
        return new Tag(
            id: $id,
            name: $name,
            slug: $id,
            content: new Markdown(''),
            created_at: Carbon::now(),
        );
    }

    public static function isValidIdentifier(string $value): bool
    {
        return preg_match(self::IDENTIFIER_PATTERN, $value) === 1;
    }

    public function rename(string $newName): void
    {
        $this->slug = $this->slugAfterRenaming($newName);
        $this->name = $newName;
    }

    public function slugAfterRenaming(string $newName): string
    {
        return $this->slug === Str::slug($this->name) ? Str::slug($newName) : $this->slug;
    }

    public function hasName(string $name): bool
    {
        return mb_strtolower($this->name) === mb_strtolower($name);
    }

    public function changeSlug(string $newSlug): void
    {
        $this->slug = $newSlug;
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

    public function assetsPath(): PagePath
    {
        return new PagePath("tags/{$this->id}");
    }

    public function filename(): string
    {
        return "{$this->id}.md";
    }
}
