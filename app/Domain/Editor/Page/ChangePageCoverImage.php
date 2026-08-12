<?php

namespace App\Domain\Editor\Page;

class ChangePageCoverImage
{
    public function __construct(
        private PageRepository $repository,
        private SiteSynchronizer $site,
    ) {}

    public function __invoke(string $path, string $coverImage): Page
    {
        $page = $this->repository->findByPath($path);

        $page->changeCoverImage($coverImage);

        $this->repository->save($page);

        $this->site->refresh($page);

        return $page;
    }
}
