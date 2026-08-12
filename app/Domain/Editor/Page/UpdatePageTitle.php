<?php

namespace App\Domain\Editor\Page;

use App\Domain\Error;
use App\Domain\Ok;
use App\Domain\Result;

class UpdatePageTitle
{
    public function __construct(
        private PageRepository $repository,
        private SiteSynchronizer $site,
    ) {}

    public function __invoke(string $oldPath, string $newTitle): Result
    {
        $page = $this->repository->findByPath($oldPath);

        $page->rename($newTitle);

        $newPath = (string) $page->path;

        if ($newPath !== $oldPath && $this->repository->pathExists($newPath, $oldPath)) {
            return new Error('This title generates the same slug as another page that already exists.');
        }

        $this->repository->save($page, $oldPath);

        $this->site->refreshOrWithdraw($page);

        return new Ok($page);
    }
}
