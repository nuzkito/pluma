<?php

namespace App\Domain\Editor\Page;

use App\Domain\Generator\SiteGenerator;
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
            $this->siteGenerator->generatePage((string) $page->path);
            $this->siteGenerator->regenerateIndex();
        }

        return $page;
    }
}
