<?php

namespace App\Domain\Editor\Page;

class UpdatePageRss
{
    public function __construct(
        private PageRepository $repository,
        private SiteSynchronizer $site,
    ) {}

    public function __invoke(string $path, bool $enabled): void
    {
        $page = $this->repository->findByPath($path);

        $page->toggleRss($enabled);

        $this->repository->save($page, $path);

        $this->site->refreshIndex();
    }
}
