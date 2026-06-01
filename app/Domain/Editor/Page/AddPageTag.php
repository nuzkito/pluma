<?php

namespace App\Domain\Editor\Page;

use App\Domain\Generator\SiteGenerator;

use function in_array;

class AddPageTag
{
    public function __construct(
        private PageRepository $repository,
        private SiteGenerator $siteGenerator,
    ) {}

    public function __invoke(string $path, string $tag): Page
    {
        $page = $this->repository->findByPath($path);

        if (in_array($tag, $page->tags, true)) {
            return $page;
        }

        $page->withTags([...$page->tags, $tag]);

        $this->repository->save($page);

        if ($page->isPublished()) {
            $this->siteGenerator->generatePage((string) $page->path);
            $this->siteGenerator->regenerateIndex();
        }

        return $page;
    }
}
