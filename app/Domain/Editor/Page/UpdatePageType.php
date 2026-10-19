<?php

namespace App\Domain\Editor\Page;

class UpdatePageType
{
    public function __construct(
        private PageRepository $repository,
        private SiteSynchronizer $site,
    ) {}

    public function __invoke(string $path, ?string $type): Page
    {
        $page = $this->repository->findByPath($path);

        $page->changeType($type);

        $this->repository->save($page);

        $this->site->refresh($page);

        return $page;
    }
}
