<?php

namespace App\Domain\Page;

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

        $this->siteGenerator->removePage($page);
        $this->siteGenerator->regenerateIndex();
    }
}
