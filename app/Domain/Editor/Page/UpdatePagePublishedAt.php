<?php

namespace App\Domain\Editor\Page;

use Carbon\Carbon;

class UpdatePagePublishedAt
{
    public function __construct(
        private PageRepository $repository,
        private SiteSynchronizer $site,
    ) {}

    public function __invoke(string $path, ?string $publishedAt): ContentPage
    {
        $page = $this->repository->findByPath($path);

        $page->changePublishedAt(filled($publishedAt) ? Carbon::parse($publishedAt) : null);

        $this->repository->save($page, $path);

        $this->site->refresh($page);

        return $page;
    }
}
