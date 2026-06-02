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

        return collect($this->disk->files('pages'))
            ->filter(fn (string $file) => Str::of($file)->endsWith('.md') && ! Str::of($file)->endsWith('.tag.md'))
            ->map($this->fromFile(...))
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
        $filePath = "pages/$path.md";

        if (! $this->disk->exists($filePath)) {
            return null;
        }

        return $this->fromFile($filePath);
    }

    public function save(Page $page, ?string $oldPath = null): void
    {
        $this->disk->makeDirectory('pages');
        $this->disk->makeDirectory('assets');

        $newFilePath = "pages/{$page->filename()}";

        if ($oldPath !== null && $oldPath !== $page->path->__toString()) {
            $oldFilePath = "pages/$oldPath.md";

            if ($this->disk->exists($oldFilePath)) {
                $this->disk->move($oldFilePath, $newFilePath);
            }

            $this->moveAssetsDirectory($oldPath, $page->path);
        }

        $this->disk->put($newFilePath, $this->toMarkdownFile($page));
    }

    public function delete(string $path): void
    {
        $this->disk->delete("pages/$path.md");
        $this->disk->deleteDirectory("assets/$path");
    }

    public function pathExists(string $path, ?string $excludePath = null): bool
    {
        return $this->all()
            ->filter(fn (Page $page) => (string) $page->path === $path && (string) $page->path !== $excludePath)
            ->isNotEmpty();
    }

    public function tagExists(string $slug): bool
    {
        return $this->disk->exists("pages/$slug.tag.md");
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

        return new ContentPage(
            title: $metadata['title'],
            path: new PagePath($metadata['path']),
            content: new Markdown($content),
            created_at: Carbon::parse($metadata['created_at']),
            published_at: isset($metadata['published_at']) ? Carbon::parse($metadata['published_at']) : null,
            rss: $metadata['rss'] ?? false,
            tags: $metadata['tags'] ?? [],
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
