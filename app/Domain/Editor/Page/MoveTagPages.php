<?php

namespace App\Domain\Editor\Page;

use App\Domain\Error;
use App\Domain\Generator\SiteGenerator;
use App\Domain\Ok;
use App\Domain\Result;
use Illuminate\Support\Str;

class MoveTagPages
{
    public function __construct(
        private PageRepository $repository,
        private DirectoryRepository $directories,
        private SiteGenerator $siteGenerator,
    ) {}

    /**
     * Move every tag page to a new directory, relative to the pages root.
     *
     * @return Result<null>
     */
    public function __invoke(string $currentPath, string $newPath): Result
    {
        $currentPath = trim($currentPath, '/');
        $newPath = trim($newPath, '/');

        if ($currentPath === $newPath) {
            return new Ok(null);
        }

        if ($this->directories->exists($newPath) || $this->repository->findByPath($newPath) !== null) {
            return new Error('A page or directory with this path already exists.');
        }

        $tagPages = $this->repository->searchByDirectory($currentPath)
            ->whereInstanceOf(TagPage::class)
            ->values();

        if ($tagPages->isEmpty()) {
            return new Ok(null);
        }

        $this->directories->create($newPath);

        foreach ($tagPages as $tagPage) {
            $oldPath = (string) $tagPage->path;

            $this->siteGenerator->removePage($oldPath);

            $tagPage->moveToPath(new PagePath($newPath.'/'.Str::afterLast($oldPath, '/')));

            $this->repository->save($tagPage, $oldPath);
        }

        if ($currentPath !== '' && $this->directories->deleteIfEmpty($currentPath)) {
            $this->siteGenerator->removePage($currentPath);
        }

        return new Ok(null);
    }
}
