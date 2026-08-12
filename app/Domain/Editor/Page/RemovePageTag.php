<?php

namespace App\Domain\Editor\Page;

use Illuminate\Support\Arr;

class RemovePageTag
{
    public function __construct(
        private PageRepository $repository,
        private SiteSynchronizer $site,
    ) {}

    public function __invoke(string $path, int $index): ContentPage
    {
        $page = $this->repository->findByPath($path);

        $removedTag = $page->tags[$index] ?? null;

        $tags = Arr::except($page->tags, $index);

        $page->withTags($tags);

        $this->repository->save($page);

        $this->site->refresh($page, ...array_filter([$removedTag]));

        return $page;
    }
}
