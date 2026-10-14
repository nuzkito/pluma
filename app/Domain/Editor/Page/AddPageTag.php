<?php

namespace App\Domain\Editor\Page;

use App\Domain\Editor\Tag\CreateTag;

use function in_array;

class AddPageTag
{
    public function __construct(
        private PageRepository $repository,
        private CreateTag $createTag,
        private SiteSynchronizer $site,
    ) {}

    public function __invoke(string $path, string $tagName): Page
    {
        $page = $this->repository->findByPath($path);

        $tag = $this->createTag->__invoke($tagName);

        if (in_array($tag->id, $page->tags, true)) {
            return $page;
        }

        $page->withTags([...$page->tags, $tag->id]);

        $this->repository->save($page);

        $this->site->refresh($page, $tag->id);

        return $page;
    }
}
