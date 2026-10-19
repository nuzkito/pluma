<?php

namespace App\Domain\Editor\Page;

use App\Domain\Generator\SiteGenerator;

class DeleteType
{
    public function __construct(
        private PageRepository $pages,
        private SiteGenerator $siteGenerator,
    ) {}

    public function __invoke(string $type): void
    {
        $typedPages = $this->pages->ofType($type);

        if ($typedPages->isEmpty()) {
            return;
        }

        foreach ($typedPages as $page) {
            $page->changeType(null);
            $this->pages->save($page);
        }

        foreach ($typedPages->filter->isPublished() as $page) {
            $this->siteGenerator->generatePage((string) $page->path);
        }

        $this->siteGenerator->regenerateIndex();
    }
}
