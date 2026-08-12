<?php

namespace App\Domain\Editor\Page;

class UnpublishPage
{
    public function __construct(
        private PageRepository $repository,
        private SiteSynchronizer $site,
    ) {}

    public function __invoke(string $pagePath): void
    {
        $page = $this->repository->findByPath($pagePath);

        $page->unpublish();

        $this->repository->save($page);

        $this->site->withdraw($pagePath);
    }
}
