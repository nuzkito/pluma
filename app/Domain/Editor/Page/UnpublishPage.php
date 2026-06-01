<?php

namespace App\Domain\Editor\Page;

use App\Domain\Generator\SiteGenerator;

class UnpublishPage
{
    public function __construct(
        private PageRepository $repository,
        private SiteGenerator $siteGenerator,
    ) {}

    public function __invoke(string $pagePath): void
    {
        $page = $this->repository->findByPath($pagePath);

        $page->unpublish();

        $this->repository->save($page);

        $this->siteGenerator->removePage($pagePath);
        $this->siteGenerator->regenerateIndex();
    }
}
