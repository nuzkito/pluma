<?php

namespace App\Domain\Generator;

use App\Domain\Generator\Page\Page;
use App\Domain\Generator\Page\PageRepository;
use App\Domain\Generator\Tag\Tag;
use App\Domain\Generator\Tag\TagRepository;
use App\Domain\Settings\SettingsSchema;
use App\Domain\Settings\SettingType;
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
        private TagRepository $tagRepository,
        private AssetProcessor $assetProcessor,
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
            $this->writePage($page, $pages);
        }

        if (config('pluma.tags.create_pages')) {
            foreach ($this->tagRepository->all() as $tag) {
                $this->writeTagPage($tag, $pages);
            }
        }

        if ($pages->contains(fn ($page) => $page->rss)) {
            $this->generateRss($pages->filter(fn ($page) => $page->rss));
        }

        foreach ($this->disk->allFiles('resources') as $file) {
            $destination = self::SITE_DIRECTORY.'/'.substr($file, strlen('resources/'));
            $this->disk->copy($file, $destination);
        }

        $this->copySiteImages();
    }

    public function generatePage(string $path): void
    {
        $page = $this->pageRepository->findByPath($path);

        if ($page === null) {
            return;
        }

        $this->writePage($page, $this->pageRepository->published());
    }

    public function generateTagPage(string $id): void
    {
        if (! config('pluma.tags.create_pages')) {
            return;
        }

        $tag = $this->tagRepository->find($id);

        if ($tag === null) {
            return;
        }

        $this->writeTagPage($tag, $this->pageRepository->published());
    }

    public function generatePagesTaggedWith(string $id): void
    {
        $publishedPages = $this->pageRepository->published();

        foreach ($publishedPages->filter(fn (Page $page) => $page->hasTag($id)) as $page) {
            $this->writePage($page, $publishedPages);
        }
    }

    public function removeTagPages(): void
    {
        foreach ($this->tagRepository->all() as $tag) {
            $this->removePage((string) $tag->path);
        }
    }

    /**
     * @param  Collection<int, Page>  $publishedPages
     */
    private function writePage(Page $page, Collection $publishedPages): void
    {
        $this->prepare();

        $pagePath = self::SITE_DIRECTORY."/{$page->path}";

        $this->disk->makeDirectory($pagePath);

        $html = $this->renderView('page', [
            'web' => $this->web(),
            'page' => $page,
            'pages' => $publishedPages,
        ]);

        $this->disk->put("$pagePath/index.html", $html);

        $this->copyPageAssets((string) $page->path, $pagePath);
    }

    private function copyPageAssets(string $path, string $destination): void
    {
        $assetsPath = self::ASSETS_DIRECTORY."/$path";

        if (! $this->disk->exists($assetsPath)) {
            return;
        }

        foreach ($this->disk->files($assetsPath) as $file) {
            $filename = basename($file);
            $this->assetProcessor->copy($file, "$destination/$filename");
        }
    }

    /**
     * @param  Collection<int, Page>  $publishedPages
     */
    private function writeTagPage(Tag $tag, Collection $publishedPages): void
    {
        $this->prepare();

        $posts = $publishedPages
            ->filter(fn (Page $page) => $page->hasTag($tag->id))
            ->values();

        $tagPath = self::SITE_DIRECTORY."/{$tag->path}";

        $this->disk->makeDirectory($tagPath);

        $html = $this->renderView('tag', [
            'web' => $this->web(),
            'tag' => $tag,
            'pages' => $posts,
        ]);

        $this->disk->put("$tagPath/index.html", $html);

        $this->copyPageAssets($tag->assetsPath(), $tagPath);
    }

    /**
     * @param  Collection<int, Page>  $pages
     */
    public function generateIndex(Collection $pages): void
    {
        $this->prepare();

        $html = $this->renderView('index', [
            'web' => $this->web(),
            'pages' => $pages,
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
            'web' => $this->web(),
            'pages' => $pages,
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
            'web' => $this->web(),
            'pages' => $pages,
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

    public function copyPageFile(string $path, string $filename): void
    {
        $source = self::ASSETS_DIRECTORY."/$path/$filename";

        if (! $this->disk->exists($source)) {
            return;
        }

        $this->assetProcessor->copy($source, self::SITE_DIRECTORY."/$path/$filename");
    }

    public function removePageFile(string $path, string $filename): void
    {
        $this->disk->delete(self::SITE_DIRECTORY."/$path/$filename");
    }

    public function copySiteImages(): void
    {
        foreach (SettingsSchema::ofType(SettingType::Image) as $definition) {
            $this->copySiteFile((string) config('pluma.'.$definition->key));
        }
    }

    public function copySiteFile(string $filename): void
    {
        if ($filename === '') {
            return;
        }

        $source = self::ASSETS_DIRECTORY."/$filename";

        if (! $this->disk->exists($source)) {
            return;
        }

        $this->ensureSiteDirectory();

        $this->assetProcessor->copy($source, self::SITE_DIRECTORY."/$filename");
    }

    public function removeSiteFile(string $filename): void
    {
        $this->disk->delete(self::SITE_DIRECTORY."/$filename");
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

    private function web(): Web
    {
        return Web::fromConfig();
    }
}
