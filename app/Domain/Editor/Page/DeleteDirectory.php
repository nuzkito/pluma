<?php

namespace App\Domain\Editor\Page;

use App\Domain\Generator\SiteGenerator;

class DeleteDirectory
{
    public function __construct(
        private DirectoryRepository $directories,
        private SiteGenerator $siteGenerator,
    ) {}

    /**
     * @return bool Whether the directory was deleted.
     */
    public function __invoke(string $directory): bool
    {
        $path = $this->normalize($directory);

        if ($path === null || ! $this->directories->deleteIfEmpty($path)) {
            return false;
        }

        $this->siteGenerator->removePage($path);

        return true;
    }

    /**
     * Reject the root directory and any path that does not name a real subdirectory.
     */
    private function normalize(string $directory): ?string
    {
        $segments = explode('/', trim($directory, '/'));

        foreach ($segments as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..') {
                return null;
            }
        }

        return implode('/', $segments);
    }
}
