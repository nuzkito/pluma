<?php

namespace App\Domain\Editor\Page;

use App\Domain\Error;
use App\Domain\Ok;
use App\Domain\Result;

class UpdatePagePath
{
    public function __construct(
        private PageRepository $repository,
        private SiteSynchronizer $site,
    ) {}

    public function __invoke(string $path, string $newPath): Result
    {
        if ($this->repository->pathExists($newPath, $path)) {
            return new Error('A page with this path already exists.');
        }

        $page = $this->repository->findByPath($path);

        $page->moveToPath(new PagePath($newPath));

        $this->repository->save($page, $path);

        $this->site->move($page, $path);

        return new Ok($page);
    }
}
