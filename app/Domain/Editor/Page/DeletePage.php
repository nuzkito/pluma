<?php

namespace App\Domain\Editor\Page;

use App\Domain\Generator\SiteGenerator;

class DeletePage
{
    public function __construct(
        private PageRepository $repository,
        private SiteGenerator $siteGenerator,
    ) {}

    public function __invoke(string $pagePath): void
    {
        $page = $this->repository->findByPath($pagePath);

        $this->repository->delete($pagePath);

        if ($page->isPublished()) {
            $this->siteGenerator->removePage($pagePath);
            $this->siteGenerator->regenerateIndex();
        }
    }
}
