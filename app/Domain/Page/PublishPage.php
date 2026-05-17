<?php

namespace App\Domain\Page;

use Carbon\Carbon;

class PublishPage
{
    public function __construct(
        private PageRepository $repository,
        private SiteGenerator $siteGenerator,
    ) {}

    public function __invoke(string $pagePath): Page
    {
        $page = $this->repository->findByPath($pagePath);

        $page->publish(Carbon::now());

        $this->repository->save($page);

        $this->siteGenerator->generatePage($page);
        $this->siteGenerator->regenerateIndex();

        return $page;
    }
}
