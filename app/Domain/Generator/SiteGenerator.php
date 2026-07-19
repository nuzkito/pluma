<?php

namespace App\Domain\Generator;

use App\Domain\Generator\Page\Page;
use App\Domain\Generator\Page\PageRepository;
use App\Domain\Generator\Page\TagPage;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\Factory;
use Illuminate\View\FileViewFinder;

class SiteGenerator
{
    private const string SITE_DIRECTORY = 'site';

    private const string ASSETS_DIRECTORY = 'assets';

    private Filesystem $disk;

    public function __construct(
        private PageRepository $pageRepository,
    ) {
        $this->disk = Storage::disk('current');
    }

    public function generateAll(): void
    {
        $this->prepare();

        $pages = $this->pageRepository->published();

        $this->generateIndex($pages);
        $this->generate404($pages);

        foreach ($pages as $page) {
            $this->writePage($page);
        }

        foreach ($this->pageRepository->tags() as $tag) {
            $this->writeTagPage($tag, $pages);
        }

        if ($pages->contains(fn ($page) => $page->rss)) {
            $this->generateRss($pages->filter(fn ($page) => $page->rss));
        }

        foreach ($this->disk->allFiles('resources') as $file) {
            $destination = self::SITE_DIRECTORY.'/'.substr($file, strlen('resources/'));
            $this->disk->copy($file, $destination);
        }
    }

    public function generatePage(string $path): void
    {
        $page = $this->pageRepository->findByPath($path);

        if ($page === null) {
            return;
        }

        $this->writePage($page);
    }

    public function generateTagPage(string $path): void
    {
        $tag = $this->pageRepository->findTagByPath($path);

        if ($tag === null) {
            return;
        }

        $this->writeTagPage($tag, $this->pageRepository->published());
    }

    private function writePage(Page $page): void
    {
        $this->prepare();

        $pagePath = self::SITE_DIRECTORY."/{$page->path}";

        $this->disk->makeDirectory($pagePath);

        $html = $this->renderView('page', [
            'page' => $page,
            'baseUrl' => $this->baseUrl(),
        ]);

        $this->disk->put("$pagePath/index.html", $html);

        $attachmentsPath = self::ASSETS_DIRECTORY."/{$page->path}";
        if ($this->disk->exists($attachmentsPath)) {
            foreach ($this->disk->files($attachmentsPath) as $file) {
                $filename = basename($file);
                $this->disk->copy($file, "$pagePath/$filename");
            }
        }
    }

    /**
     * @param  Collection<int, Page>  $publishedPages
     */
    private function writeTagPage(TagPage $tag, Collection $publishedPages): void
    {
        $this->prepare();

        $posts = $publishedPages
            ->filter(fn (Page $page) => in_array($tag->title, $page->tags, true))
            ->values();

        $tagPath = self::SITE_DIRECTORY."/{$tag->path}";

        $this->disk->makeDirectory($tagPath);

        $html = $this->renderView('tag', [
            'tag' => $tag,
            'pages' => $posts,
            'baseUrl' => $this->baseUrl(),
        ]);

        $this->disk->put("$tagPath/index.html", $html);
    }

    /**
     * @param  Collection<int, Page>  $pages
     */
    public function generateIndex(Collection $pages): void
    {
        $this->prepare();

        $html = $this->renderView('index', [
            'pages' => $pages,
            'baseUrl' => $this->baseUrl(),
            'title' => $this->siteTitle(),
            'description' => $this->siteDescription(),
        ]);

        $this->disk->put(self::SITE_DIRECTORY.'/index.html', $html);
    }

    /**
     * @param  Collection<int, Page>  $pages
     */
    public function generate404(Collection $pages): void
    {
        $this->prepare();

        $html = $this->renderView('404', [
            'pages' => $pages,
            'baseUrl' => $this->baseUrl(),
            'title' => $this->siteTitle(),
            'description' => $this->siteDescription(),
        ]);

        $this->disk->put(self::SITE_DIRECTORY.'/404.html', $html);
    }

    /**
     * @param  Collection<int, Page>  $pages
     */
    public function generateRss(Collection $pages): void
    {
        if (! config('pluma.rss.enabled')) {
            return;
        }

        $xml = $this->renderView('feed', [
            'pages' => $pages,
            'baseUrl' => $this->baseUrl(),
            'title' => $this->siteTitle(),
            'description' => $this->siteDescription(),
        ]);

        $this->disk->put(self::SITE_DIRECTORY.'/feed.xml', $xml);
    }

    public function regenerateIndex(): void
    {
        $pages = $this->pageRepository->published();
        $this->generateIndex($pages);
        $this->generate404($pages);

        $rssPages = $pages->filter(fn ($page) => $page->rss);
        if ($rssPages->isNotEmpty()) {
            $this->generateRss($rssPages);
        } else {
            $this->disk->delete(self::SITE_DIRECTORY.'/feed.xml');
        }
    }

    public function removePage(string $path): void
    {
        $pagePath = self::SITE_DIRECTORY."/{$path}";

        if ($this->disk->exists($pagePath)) {
            $this->disk->deleteDirectory($pagePath);
        }
    }

    public function removePageFile(string $path, string $filename): void
    {
        $this->disk->delete(self::SITE_DIRECTORY."/$path/$filename");
    }

    private function prepare(): void
    {
        $this->ensureSiteDirectory();
        $this->registerViewPaths();
    }

    private function ensureSiteDirectory(): void
    {
        $this->disk->makeDirectory(self::SITE_DIRECTORY);
    }

    private function registerViewPaths(): void
    {
        $viewsPath = $this->disk->path('views');

        /** @var Factory $viewFactory */
        $viewFactory = app('view');
        /** @var FileViewFinder $finder */
        $finder = $viewFactory->getFinder();

        $finder->prependLocation($viewsPath);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function renderView(string $view, array $data = []): string
    {
        return view($view, $data)->render();
    }

    private function baseUrl(): string
    {
        $url = config('pluma.url');

        return rtrim($url, '/');
    }

    private function siteTitle(): string
    {
        return config('pluma.title');
    }

    private function siteDescription(): string
    {
        return config('pluma.description');
    }
}
