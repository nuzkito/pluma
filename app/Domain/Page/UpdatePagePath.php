<?php

namespace App\Domain\Page;

use App\Domain\Error;
use App\Domain\Ok;
use App\Domain\Result;

class UpdatePagePath
{
    public function __construct(
        private PageRepository $repository,
        private SiteGenerator $siteGenerator,
    ) {}

    public function __invoke(string $path, string $newPath): Result
    {
        if ($this->repository->pathExists($newPath, $path)) {
            return new Error('A page with this path already exists.');
        }

        $page = $this->repository->findByPath($path);

        if ($page->isPublished()) {
            $this->siteGenerator->removePage($page);
        }

        $page->moveToPath(new PagePath($newPath));

        $this->repository->save($page, $path);

        if ($page->isPublished()) {
            $this->siteGenerator->generatePage($page);
            $this->siteGenerator->regenerateIndex();
        }

        return new Ok($page);
    }
}
