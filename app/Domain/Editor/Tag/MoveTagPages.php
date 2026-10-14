<?php

namespace App\Domain\Editor\Tag;

use App\Domain\Editor\Page\DirectoryRepository;
use App\Domain\Editor\Page\PageRepository;
use App\Domain\Error;
use App\Domain\Generator\SiteGenerator;
use App\Domain\Ok;
use App\Domain\Result;

class MoveTagPages
{
    public function __construct(
        private TagRepository $tags,
        private PageRepository $pages,
        private DirectoryRepository $directories,
        private SiteGenerator $siteGenerator,
    ) {}

    /**
     * Move the generated pages of every tag to a new directory relative to the site root.
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

        if ($this->directories->exists($newPath) || $this->pages->findByPath($newPath) !== null) {
            return new Error('A page or directory with this path already exists.');
        }

        foreach ($this->tags->all() as $tag) {
            $this->siteGenerator->removePage("$currentPath/{$tag->slug}");
        }

        if ($currentPath !== '' && ! $this->directories->exists($currentPath)) {
            $this->siteGenerator->removePage($currentPath);
        }

        return new Ok(null);
    }
}
