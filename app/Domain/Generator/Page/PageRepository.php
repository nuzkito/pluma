<?php

namespace App\Domain\Generator\Page;

use App\Domain\Generator\Tag\Tag;
use App\Domain\Generator\Tag\TagRepository;
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

    public function __construct(private TagRepository $tags = new TagRepository)
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

        $tags = $this->tags->all();

        return collect($this->disk->allFiles('pages'))
            ->filter(fn (string $file) => Str::of($file)->endsWith('.md'))
            ->map(fn (string $file) => $this->fromFile($file, $tags))
            ->sortByDesc('created_at')
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
        $pagePath = "pages/$path.md";

        if (! $this->disk->exists($pagePath)) {
            return null;
        }

        return $this->fromFile($pagePath, $this->tags->all());
    }

    /**
     * @param  Collection<string, Tag>  $tags
     */
    private function fromFile(string $filePath, Collection $tags): Page
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
            draft: $metadata['draft'] ?? false,
            revised_at: isset($metadata['revised_at']) ? Carbon::parse($metadata['revised_at']) : null,
            rss: $metadata['rss'] ?? false,
            tags: array_map(
                fn (string $id) => $tags->get($id) ?? Tag::undefined($id),
                $metadata['tags'] ?? [],
            ),
            cover_image: $metadata['cover_image'] ?? null,
        );
    }
}
