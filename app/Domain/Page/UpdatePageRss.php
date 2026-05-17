<?php

namespace App\Domain\Page;

class UpdatePageRss
{
    public function __construct(
        private PageRepository $repository,
        private SiteGenerator $siteGenerator,
    ) {}

    public function __invoke(string $path, bool $enabled): void
    {
        $page = $this->repository->findByPath($path);

        $page->toggleRss($enabled);

        $this->repository->save($page, $path);

        $this->siteGenerator->regenerateIndex();
    }
}
