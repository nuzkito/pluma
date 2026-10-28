<?php

namespace App\Domain\Editor\Type;

use App\Domain\Editor\Page\PageRepository;
use App\Domain\Generator\SiteGenerator;

class DeleteType
{
    public function __construct(
        private TypeRepository $repository,
        private PageRepository $pages,
        private SiteGenerator $siteGenerator,
    ) {}

    public function __invoke(string $id): void
    {
        $type = $this->repository->find($id);

        if ($type === null) {
            return;
        }

        $typedPages = $this->pages->ofType($id);

        foreach ($typedPages as $page) {
            $page->changeType(null);
            $this->pages->save($page);
        }

        $this->repository->delete($type);

        if ($typedPages->isEmpty()) {
            return;
        }

        foreach ($typedPages->filter->isPublished() as $page) {
            $this->siteGenerator->generatePage((string) $page->path);
        }

        $this->siteGenerator->regenerateIndex();
    }
}
