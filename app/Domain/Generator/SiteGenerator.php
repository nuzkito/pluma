<?php

namespace App\Domain\Generator;

use App\Domain\Generator\Page\Page;
use App\Domain\Generator\Page\PageRepository;
use App\Domain\Generator\Page\TagPage;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\Factory;
use Illuminate\View\FileViewFinder;

class SiteGenerator
{
    public function __construct(
        private PageRepository $pageRepository,
    ) {}

    public function generateAll(): void
    {
        $this->ensureSiteDirectory();
        $this->registerViewPaths();

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

        foreach (Storage::disk('current')->allFiles('resources') as $file) {
            $destination = 'site/'.substr($file, strlen('resources/'));
            Storage::disk('current')->copy($file, $destination);
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
        $this->ensureSiteDirectory();
        $this->registerViewPaths();

        $disk = Storage::disk('current');
        $pagePath = "site/{$page->path}";

        $disk->makeDirectory($pagePath);

        $html = $this->renderView('page', [
            'page' => $page,
            'baseUrl' => $this->baseUrl(),
        ]);

        $disk->put("$pagePath/index.html", $html);

        $attachmentsPath = "assets/{$page->path}";
        if ($disk->exists($attachmentsPath)) {
            foreach ($disk->files($attachmentsPath) as $file) {
                $filename = basename($file);
                $disk->copy($file, "$pagePath/$filename");
            }
        }
    }

    /**
     * @param  Collection<int, Page>  $publishedPages
     */
    private function writeTagPage(TagPage $tag, Collection $publishedPages): void
    {
        $this->ensureSiteDirectory();
        $this->registerViewPaths();

        $posts = $publishedPages
            ->filter(fn (Page $page) => in_array($tag->title, $page->tags, true))
            ->values();

        $disk = Storage::disk('current');
        $tagPath = "site/{$tag->path}";

        $disk->makeDirectory($tagPath);

        $html = $this->renderView('tag', [
            'tag' => $tag,
            'pages' => $posts,
            'baseUrl' => $this->baseUrl(),
        ]);

        $disk->put("$tagPath/index.html", $html);
    }

    /**
     * @param  Collection<int, Page>  $pages
     */
    public function generateIndex(Collection $pages): void
    {
        $this->ensureSiteDirectory();
        $this->registerViewPaths();

        $html = $this->renderView('index', [
            'pages' => $pages,
            'baseUrl' => $this->baseUrl(),
            'title' => $this->siteTitle(),
            'description' => $this->siteDescription(),
        ]);

        Storage::disk('current')->put('site/index.html', $html);
    }

    /**
     * @param  Collection<int, Page>  $pages
     */
    public function generate404(Collection $pages): void
    {
        $this->ensureSiteDirectory();
        $this->registerViewPaths();

        $html = $this->renderView('404', [
            'pages' => $pages,
            'baseUrl' => $this->baseUrl(),
            'title' => $this->siteTitle(),
            'description' => $this->siteDescription(),
        ]);

        Storage::disk('current')->put('site/404.html', $html);
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

        Storage::disk('current')->put('site/feed.xml', $xml);
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
            Storage::disk('current')->delete('site/feed.xml');
        }
    }

    public function removePage(string $path): void
    {
        $pagePath = "site/{$path}";

        if (Storage::disk('current')->exists($pagePath)) {
            Storage::disk('current')->deleteDirectory($pagePath);
        }
    }

    private function ensureSiteDirectory(): void
    {
        Storage::disk('current')->makeDirectory('site');
    }

    private function registerViewPaths(): void
    {
        $viewsPath = Storage::disk('current')->path('views');

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
