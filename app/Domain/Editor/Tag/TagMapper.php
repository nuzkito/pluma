<?php

namespace App\Domain\Editor\Tag;

use App\Domain\Editor\Page\Markdown;
use Carbon\Carbon;

class TagMapper
{
    /** @return array<string, mixed> */
    public function toFrontMatter(Tag $tag): array
    {
        return [
            'name' => $tag->name,
            'slug' => $tag->slug,
            'cover_image' => $tag->cover_image,
            'created_at' => $tag->created_at->toIso8601String(),
        ];
    }

    /** @param array<string, mixed> $metadata */
    public function fromFrontMatter(string $id, array $metadata, Markdown $content): Tag
    {
        return new Tag(
            id: $id,
            name: $metadata['name'] ?? $id,
            slug: $metadata['slug'] ?? $id,
            content: $content,
            created_at: Carbon::parse($metadata['created_at']),
            cover_image: $metadata['cover_image'] ?? null,
        );
    }
}
