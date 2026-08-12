<?php

namespace App\Domain\Editor\Page;

use Carbon\Carbon;

class UpdatePagePublishedAt
{
    public function __construct(
        private PageRepository $repository,
        private SiteSynchronizer $site,
    ) {}

    public function __invoke(string $path, ?string $publishedAt): void
    {
        $page = $this->repository->findByPath($path);

        isset($publishedAt)
            ? $page->publish(Carbon::parse($publishedAt))
            : $page->unpublish();

        $this->repository->save($page, $path);

        $this->site->refreshOrWithdraw($page);
    }
}
