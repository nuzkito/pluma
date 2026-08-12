<?php

namespace App\Domain\Editor\Page;

class DeletePage
{
    public function __construct(
        private PageRepository $repository,
        private SiteSynchronizer $site,
    ) {}

    public function __invoke(string $pagePath): void
    {
        $page = $this->repository->findByPath($pagePath);

        $this->repository->delete($pagePath);

        if ($page->isPublished()) {
            $this->site->withdraw($pagePath);
        }
    }
}
