<?php

namespace App\Domain\Editor\Page;

class UpdatePageContent
{
    public function __construct(
        private PageRepository $repository,
        private SiteSynchronizer $site,
    ) {}

    public function __invoke(string $path, string $content): void
    {
        $page = $this->repository->findByPath($path);

        $page->setContent(new Markdown($content));

        $this->repository->save($page, $path);

        $this->site->refresh($page);
    }
}
