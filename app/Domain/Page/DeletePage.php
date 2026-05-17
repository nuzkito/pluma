<?php

namespace App\Domain\Page;

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
            $this->siteGenerator->removePage($page);
            $this->siteGenerator->regenerateIndex();
        }
    }
}
