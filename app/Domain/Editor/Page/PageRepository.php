<?php

namespace App\Domain\Editor\Page;

use Carbon\Carbon;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use League\CommonMark\Extension\FrontMatter\Data\SymfonyYamlFrontMatterParser;
use League\CommonMark\Extension\FrontMatter\FrontMatterParser;
use Symfony\Component\Yaml\Yaml;

class PageRepository
{
    private const string BASE_DIRECTORY = 'pages';

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
        return $this->searchByDirectory('');
    }

    /**
     * @return Collection<int, Page>
     */
    public function searchByDirectory(string $directory): Collection
    {
        $path = $directory === '' ? self::BASE_DIRECTORY : self::BASE_DIRECTORY."/$directory";

        if (! $this->disk->exists($path)) {
            return collect();
        }

        return collect($this->disk->files($path))
            ->filter(fn (string $file) => Str::of($file)->endsWith('.md'))
            ->map($this->fromFile(...))
            ->sortByDesc('created_at')
            ->values();
    }

    /**
     * @return Collection<int, Page>
     */
    public function published(): Collection
    {
        return $this->all()
            ->filter->isPublished()
            ->values();
    }

    /**
     * @return Collection<int, Page>
     */
    public function taggedWith(string $id): Collection
    {
        return $this->everyPage()
            ->filter(fn (Page $page) => $page->hasTag($id))
            ->values();
    }

    /**
     * @return Collection<int, Page>
     */
    public function ofType(string $type): Collection
    {
        return $this->everyPage()
            ->filter(fn (Page $page) => $page->type === $type)
            ->values();
    }

    /**
     * @return Collection<int, string>
     */
    public function types(?string $excludePath = null): Collection
    {
        return $this->everyPage()
            ->reject(fn (Page $page) => (string) $page->path === $excludePath)
            ->pluck('type')
            ->filter()
            ->unique()
            ->sort(SORT_NATURAL | SORT_FLAG_CASE)
            ->values();
    }

    /**
     * @return Collection<string, int>
     */
    public function typeCounts(): Collection
    {
        return $this->everyPage()
            ->pluck('type')
            ->filter()
            ->countBy()
            ->sortKeys(SORT_NATURAL | SORT_FLAG_CASE);
    }

    public function findByPath(string $path): ?Page
    {
        $contentPagePath = self::BASE_DIRECTORY."/$path.md";

        if ($this->disk->exists($contentPagePath)) {
            return $this->fromFile($contentPagePath);
        }

        return null;
    }

    public function save(Page $page, ?string $oldPath = null): void
    {
        $this->disk->makeDirectory(self::BASE_DIRECTORY);
        $this->disk->makeDirectory('assets');

        $newFilePath = self::BASE_DIRECTORY."/{$page->filename()}";

        if ($oldPath !== null && $oldPath !== $page->path->__toString()) {
            $oldFilePath = self::BASE_DIRECTORY."/$oldPath.md";

            if ($this->disk->exists($oldFilePath)) {
                $this->disk->move($oldFilePath, $newFilePath);
            }

            $this->moveAssetsDirectory($oldPath, $page->path);
        }

        $this->disk->put($newFilePath, $this->toMarkdownFile($page));
    }

    public function delete(string $path): void
    {
        $this->disk->delete(self::BASE_DIRECTORY."/$path.md");
        $this->disk->deleteDirectory("assets/$path");
    }

    public function pathExists(string $path, ?string $excludePath = null): bool
    {
        $directory = Str::contains($path, '/') ? Str::beforeLast($path, '/') : '';

        return $this->searchByDirectory($directory)
            ->filter(fn (Page $page) => (string) $page->path === $path && (string) $page->path !== $excludePath)
            ->isNotEmpty();
    }

    /**
     * @return Collection<int, Page>
     */
    private function everyPage(): Collection
    {
        if (! $this->disk->exists(self::BASE_DIRECTORY)) {
            return collect();
        }

        return collect($this->disk->allFiles(self::BASE_DIRECTORY))
            ->filter(fn (string $file) => Str::of($file)->endsWith('.md'))
            ->map($this->fromFile(...))
            ->values();
    }

    private function toMarkdownFile(Page $page): string
    {
        $yaml = Yaml::dump($page->toArray());

        return "---\n$yaml---\n\n{$page->content}";
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
            draft: $metadata['draft'] ?? false,
            revised_at: isset($metadata['revised_at']) ? Carbon::parse($metadata['revised_at']) : null,
            rss: $metadata['rss'] ?? false,
            tags: $metadata['tags'] ?? [],
            cover_image: $metadata['cover_image'] ?? null,
            type: $metadata['type'] ?? null,
        );
    }

    private function moveAssetsDirectory(string $oldPath, PagePath $newPath): void
    {
        $oldAssetsDir = "assets/$oldPath";
        $newAssetsDir = "assets/$newPath";

        if (! $this->disk->exists($oldAssetsDir)) {
            return;
        }

        $this->disk->makeDirectory($newAssetsDir);

        foreach ($this->disk->files($oldAssetsDir) as $file) {
            $filename = basename($file);
            $this->disk->move($file, "$newAssetsDir/$filename");
        }

        $this->disk->deleteDirectory($oldAssetsDir);
    }
}
