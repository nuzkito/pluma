<?php

namespace App\Domain\Editor\Page;

use App\Domain\Generator\SiteGenerator;

class UpdatePageContent
{
    public function __construct(
        private PageRepository $repository,
        private SiteGenerator $siteGenerator,
    ) {}

    public function __invoke(string $path, string $content): void
    {
        $page = $this->repository->findByPath($path);

        $page->setContent(new Markdown($content));

        $this->repository->save($page, $path);

        if ($page->isPublished()) {
            $this->siteGenerator->generatePage((string) $page->path);
            $this->siteGenerator->regenerateIndex();
        }
    }
}
