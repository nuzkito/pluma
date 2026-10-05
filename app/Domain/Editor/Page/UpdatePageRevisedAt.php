<?php

namespace App\Domain\Editor\Page;

use Carbon\Carbon;

class UpdatePageRevisedAt
{
    public function __construct(
        private PageRepository $repository,
        private SiteSynchronizer $site,
    ) {}

    public function __invoke(string $path, ?string $revisedAt): Page
    {
        $page = $this->repository->findByPath($path);

        $page->changeRevisedAt(filled($revisedAt) ? Carbon::parse($revisedAt) : null);

        $this->repository->save($page, $path);

        $this->site->refresh($page);

        return $page;
    }
}
