<?php

namespace App\Domain\Editor\Page;

use App\Domain\Generator\SiteGenerator;

class ChangePageCoverImage
{
    public function __construct(
        private PageRepository $repository,
        private SiteGenerator $siteGenerator,
    ) {}

    public function __invoke(string $path, string $coverImage): ContentPage
    {
        $page = $this->repository->findByPath($path);

        $page->changeCoverImage($coverImage);

        $this->repository->save($page);

        if ($page->isPublished()) {
            $this->siteGenerator->generatePage((string) $page->path);
            $this->siteGenerator->regenerateIndex();
        }

        return $page;
    }
}
