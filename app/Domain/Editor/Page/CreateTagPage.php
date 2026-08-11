<?php

namespace App\Domain\Editor\Page;

use App\Domain\Generator\SiteGenerator;

class CreateTagPage
{
    public function __construct(
        private PageRepository $repository,
        private SiteGenerator $siteGenerator,
    ) {}

    public function __invoke(string $tagName): void
    {
        if (! config('pluma.tags.create_pages')) {
            return;
        }

        $tagPage = TagPage::create($tagName);

        if ($this->repository->tagExists((string) $tagPage->path)) {
            return;
        }

        $this->repository->save($tagPage);

        $this->siteGenerator->generatePage((string) $tagPage->path);
    }
}
