<?php

namespace App\Domain\Editor\Page;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DirectoryRepository
{
    private const string BASE_DIRECTORY = 'pages';

    private Filesystem $disk;

    public function __construct()
    {
        $this->disk = Storage::disk('current');
    }

    /**
     * @return Collection<int, Directory>
     */
    public function searchByDirectory(string $directory): Collection
    {
        $path = $directory === '' ? self::BASE_DIRECTORY : self::BASE_DIRECTORY."/$directory";

        if (! $this->disk->exists($path)) {
            return collect();
        }

        return collect($this->disk->directories($path))
            ->map(fn (string $path) => new Directory(Str::after($path, self::BASE_DIRECTORY.'/')))
            ->sortBy('path')
            ->values();
    }

    public function create(string $directory): void
    {
        $this->disk->makeDirectory(self::BASE_DIRECTORY."/$directory");
    }

    public function exists(string $directory): bool
    {
        return $this->disk->exists(self::BASE_DIRECTORY."/$directory");
    }

    /**
     * Delete a directory only when it holds no files or subdirectories.
     *
     * @return bool Whether the directory was deleted.
     */
    public function deleteIfEmpty(string $directory): bool
    {
        $path = self::BASE_DIRECTORY."/$directory";

        if (! $this->disk->exists($path)) {
            return false;
        }

        if ($this->disk->files($path) !== [] || $this->disk->directories($path) !== []) {
            return false;
        }

        return $this->disk->deleteDirectory($path);
    }
}
