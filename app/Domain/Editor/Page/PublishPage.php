<?php

namespace App\Domain\Editor\Page;

use App\Domain\Generator\SiteGenerator;
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

        $this->siteGenerator->generatePage((string) $page->path);
        $this->siteGenerator->regenerateIndex();

        return $page;
    }
}
