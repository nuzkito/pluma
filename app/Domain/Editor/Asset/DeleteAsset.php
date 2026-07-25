<?php

namespace App\Domain\Editor\Asset;

use App\Domain\Editor\Page\PagePath;
use App\Domain\Editor\Page\PageRepository;
use App\Domain\Generator\SiteGenerator;

class DeleteAsset
{
    public function __construct(
        private PageRepository $pageRepository,
        private AssetRepository $assetRepository,
        private SiteGenerator $siteGenerator,
    ) {}

    public function __invoke(string $pagePath, string $filename): void
    {
        $page = $this->pageRepository->findByPath($pagePath);

        if (! $page) {
            return;
        }

        $this->assetRepository->delete(new Asset(
            pagePath: new PagePath($pagePath),
            name: $filename,
        ));

        if ($page->cover_image === $filename) {
            $page->removeCoverImage();
            $this->pageRepository->save($page);

            if ($page->isPublished()) {
                $this->siteGenerator->generatePage((string) $page->path);
            }
        }

        $this->assetRepository->pruneEmptyDirectory($page->path);

        if ($page->isPublished()) {
            $this->siteGenerator->removePageFile((string) $page->path, $filename);
        }
    }
}
