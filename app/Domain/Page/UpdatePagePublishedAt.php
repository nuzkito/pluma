<?php

namespace App\Domain\Page;

use Carbon\Carbon;

class UpdatePagePublishedAt
{
    public function __construct(
        private PageRepository $repository,
        private SiteGenerator $siteGenerator,
    ) {}

    public function __invoke(string $path, ?string $publishedAt): void
    {
        $page = $this->repository->findByPath($path);

        isset($publishedAt)
            ? $page->publish(Carbon::parse($publishedAt))
            : $page->unpublish();

        $this->repository->save($page, $path);

        if ($page->isPublished()) {
            $this->siteGenerator->generatePage($page);
        } else {
            $this->siteGenerator->removePage($page);
        }

        $this->siteGenerator->regenerateIndex();
    }
}
