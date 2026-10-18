<?php

namespace App\Domain\Editor\Tag;

use App\Domain\Editor\Page\PageRepository;
use App\Domain\Generator\SiteGenerator;

class DeleteTag
{
    public function __construct(
        private TagRepository $repository,
        private PageRepository $pages,
        private SiteGenerator $siteGenerator,
    ) {}

    public function __invoke(string $id): void
    {
        $tag = $this->repository->find($id);

        if ($tag === null) {
            return;
        }

        $taggedPages = $this->pages->taggedWith($id);

        foreach ($taggedPages as $page) {
            $page->removeTag($id);
            $this->pages->save($page);
        }

        $this->repository->delete($tag);

        $this->siteGenerator->removePage($tag->path);

        foreach ($taggedPages->filter->isPublished() as $page) {
            $this->siteGenerator->generatePage((string) $page->path);
        }

        $this->siteGenerator->regenerateIndex();
    }
}
