<?php

namespace App\Domain\Editor\Page;

use Carbon\Carbon;

class PublishPage
{
    public function __construct(
        private PageRepository $repository,
        private SiteSynchronizer $site,
    ) {}

    public function __invoke(string $pagePath): ContentPage
    {
        $page = $this->repository->findByPath($pagePath);

        $page->publish(Carbon::now());

        $this->repository->save($page);

        $this->site->refresh($page);

        return $page;
    }
}
