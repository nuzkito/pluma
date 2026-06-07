<?php

namespace App\Domain\Generator\Page;

use Carbon\Carbon;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use League\CommonMark\Extension\FrontMatter\Data\SymfonyYamlFrontMatterParser;
use League\CommonMark\Extension\FrontMatter\FrontMatterParser;

class PageRepository
{
    private Filesystem $disk;

    public function __construct()
    {
        $this->disk = Storage::disk('current');
    }

    /**
     * @return Collection<int, Page>
     */
    public function all(): Collection
    {
        if (! $this->disk->exists('pages')) {
            return collect();
        }

        return collect($this->disk->allFiles('pages'))
            ->filter(fn (string $file) => Str::of($file)->endsWith('.md'))
            ->reject(fn (string $file) => Str::of($file)->endsWith('.tag.md'))
            ->map($this->fromFile(...))
            ->sortByDesc('created_at')
            ->values();
    }

    /**
     * @return Collection<int, TagPage>
     */
    public function tags(): Collection
    {
        $tagsDirectory = 'pages/'.config('pluma.tag_pages_path');

        if (! $this->disk->exists($tagsDirectory)) {
            return collect();
        }

        return collect($this->disk->files($tagsDirectory))
            ->filter(fn (string $file) => Str::of($file)->endsWith('.tag.md'))
            ->map($this->tagPageFromFile(...))
            ->sortBy('title')
            ->values();
    }

    /**
     * @return Collection<int, Page>
     */
    public function published(): Collection
    {
        return $this->all()->filter->isPublished()->values();
    }

    public function findByPath(string $path): ?Page
    {
        $filePath = "pages/$path.md";

        if (! $this->disk->exists($filePath)) {
            return null;
        }

        return $this->fromFile($filePath);
    }

    public function findTagByPath(string $path): ?TagPage
    {
        $filePath = "pages/$path.tag.md";

        if (! $this->disk->exists($filePath)) {
            return null;
        }

        return $this->tagPageFromFile($filePath);
    }

    private function fromFile(string $filePath): Page
    {
        $raw = $this->disk->get($filePath);
        $parser = new FrontMatterParser(new SymfonyYamlFrontMatterParser);
        $parsed = $parser->parse($raw);
        $metadata = $parsed->getFrontMatter();
        $content = $parsed->getContent();

        return new Page(
            title: $metadata['title'],
            path: new PagePath($metadata['path']),
            content: new Markdown($content),
            created_at: Carbon::parse($metadata['created_at']),
            published_at: isset($metadata['published_at']) ? Carbon::parse($metadata['published_at']) : null,
            rss: $metadata['rss'] ?? false,
            tags: $metadata['tags'] ?? [],
            cover_image: $metadata['cover_image'] ?? null,
        );
    }

    private function tagPageFromFile(string $filePath): TagPage
    {
        $raw = $this->disk->get($filePath);
        $parser = new FrontMatterParser(new SymfonyYamlFrontMatterParser);
        $parsed = $parser->parse($raw);
        $metadata = $parsed->getFrontMatter();
        $content = $parsed->getContent();

        return new TagPage(
            title: $metadata['title'],
            path: new PagePath($metadata['path']),
            content: new Markdown($content),
            created_at: Carbon::parse($metadata['created_at']),
        );
    }
}
