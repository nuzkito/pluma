<?php

namespace App\Domain\Page;

use Illuminate\Support\Arr;

class RemovePageTag
{
    public function __construct(
        private PageRepository $repository,
        private SiteGenerator $siteGenerator,
    ) {}

    public function __invoke(string $path, int $index): Page
    {
        $page = $this->repository->findByPath($path);

        $tags = Arr::except($page->tags, $index);

        $page->withTags($tags);

        $this->repository->save($page);

        if ($page->isPublished()) {
            $this->siteGenerator->generatePage($page);
            $this->siteGenerator->regenerateIndex();
        }

        return $page;
    }
}
