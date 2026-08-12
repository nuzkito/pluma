<?php

namespace App\Domain\Editor\Page;

class CreateTagPage
{
    public function __construct(
        private PageRepository $repository,
        private SiteSynchronizer $site,
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

        $this->site->refresh($tagPage);
    }
}
