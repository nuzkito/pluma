<?php

namespace App\Domain\Editor\Tag;

use App\Domain\Editor\Page\Markdown;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use League\CommonMark\Extension\FrontMatter\Data\SymfonyYamlFrontMatterParser;
use League\CommonMark\Extension\FrontMatter\FrontMatterParser;
use Symfony\Component\Yaml\Yaml;

class TagRepository
{
    private const string DIRECTORY_WHERE_TAGS_ARE_STORED = 'tags';

    private Filesystem $disk;

    public function __construct(private readonly TagMapper $mapper = new TagMapper)
    {
        $this->disk = Storage::disk('current');
    }

    /**
     * @return Collection<int, Tag>
     */
    public function all(): Collection
    {
        if (! $this->disk->exists(self::DIRECTORY_WHERE_TAGS_ARE_STORED)) {
            return collect();
        }

        return collect($this->disk->files(self::DIRECTORY_WHERE_TAGS_ARE_STORED))
            ->filter(fn (string $file) => Str::of($file)->endsWith('.md'))
            ->map($this->fromFile(...))
            ->sortBy(fn (Tag $tag) => mb_strtolower($tag->name))
            ->values();
    }

    public function find(string $id): ?Tag
    {
        if (! Tag::isValidIdentifier($id)) {
            return null;
        }

        $filePath = self::DIRECTORY_WHERE_TAGS_ARE_STORED."/$id.md";

        if (! $this->disk->exists($filePath)) {
            return null;
        }

        return $this->fromFile($filePath);
    }

    public function findByName(string $name, ?string $excludeId = null): ?Tag
    {
        return $this->all()->first(
            fn (Tag $tag) => $tag->id !== $excludeId && $tag->hasName($name),
        );
    }

    public function findBySlug(string $slug, ?string $excludeId = null): ?Tag
    {
        return $this->all()->first(
            fn (Tag $tag) => $tag->id !== $excludeId && $tag->slug === $slug,
        );
    }

    public function nextId(string $name): string
    {
        $tags = $this->all();
        $base = Str::slug($name) ?: 'tag';
        $id = $base;
        $suffix = 2;

        while ($tags->contains(fn (Tag $tag) => $tag->id === $id || $tag->slug === $id)) {
            $id = "$base-$suffix";
            $suffix++;
        }

        return $id;
    }

    public function save(Tag $tag): void
    {
        $this->disk->makeDirectory(self::DIRECTORY_WHERE_TAGS_ARE_STORED);

        $yaml = Yaml::dump($this->mapper->toFrontMatter($tag));

        $this->disk->put(self::DIRECTORY_WHERE_TAGS_ARE_STORED."/{$tag->filename()}", "---\n$yaml---\n\n{$tag->content}");
    }

    public function delete(Tag $tag): void
    {
        $this->disk->delete(self::DIRECTORY_WHERE_TAGS_ARE_STORED."/{$tag->filename()}");
        $this->disk->deleteDirectory("assets/{$tag->assetsPath()}");
    }

    private function fromFile(string $filePath): Tag
    {
        $parser = new FrontMatterParser(new SymfonyYamlFrontMatterParser);
        $parsed = $parser->parse($this->disk->get($filePath));
        $metadata = $parsed->getFrontMatter();
        $id = basename($filePath, '.md');

        return $this->mapper->fromFrontMatter($id, $metadata, new Markdown($parsed->getContent()));
    }
}
