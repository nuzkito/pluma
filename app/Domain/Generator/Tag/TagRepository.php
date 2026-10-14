<?php

namespace App\Domain\Generator\Tag;

use App\Domain\Generator\Page\Markdown;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use League\CommonMark\Extension\FrontMatter\Data\SymfonyYamlFrontMatterParser;
use League\CommonMark\Extension\FrontMatter\FrontMatterParser;

class TagRepository
{
    private const string BASE_DIRECTORY = 'tags';

    private Filesystem $disk;

    public function __construct()
    {
        $this->disk = Storage::disk('current');
    }

    /**
     * @return Collection<string, Tag>
     */
    public function all(): Collection
    {
        if (! $this->disk->exists(self::BASE_DIRECTORY)) {
            return collect();
        }

        return collect($this->disk->files(self::BASE_DIRECTORY))
            ->filter(fn (string $file) => Str::of($file)->endsWith('.md'))
            ->map($this->fromFile(...))
            ->sortBy(fn (Tag $tag) => mb_strtolower($tag->name))
            ->keyBy('id');
    }

    public function find(string $id): ?Tag
    {
        $filePath = self::BASE_DIRECTORY."/$id.md";

        if (str_contains($id, '/') || ! $this->disk->exists($filePath)) {
            return null;
        }

        return $this->fromFile($filePath);
    }

    private function fromFile(string $filePath): Tag
    {
        $parser = new FrontMatterParser(new SymfonyYamlFrontMatterParser);
        $parsed = $parser->parse($this->disk->get($filePath));
        $metadata = $parsed->getFrontMatter();
        $id = basename($filePath, '.md');

        return new Tag(
            id: $id,
            name: $metadata['name'] ?? $id,
            slug: $metadata['slug'] ?? $id,
            content: new Markdown($parsed->getContent()),
            cover_image: $metadata['cover_image'] ?? null,
        );
    }
}
