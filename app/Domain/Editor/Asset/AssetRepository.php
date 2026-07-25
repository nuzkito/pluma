<?php

namespace App\Domain\Editor\Asset;

use App\Domain\Editor\Page\PagePath;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;

class AssetRepository
{
    private Filesystem $disk;

    public function __construct()
    {
        $this->disk = Storage::disk('current');
    }

    public function save(NewAsset $asset): void
    {
        $this->disk->putFileAs("assets/{$asset->pagePath}", $asset->file, $asset->name);
    }

    public function delete(Asset $asset): bool
    {
        $assetPath = "assets/{$asset->pagePath}/{$asset->name}";

        if (! $this->disk->exists($assetPath)) {
            return false;
        }

        $this->disk->delete($assetPath);

        return true;
    }

    public function pruneEmptyDirectory(PagePath $pagePath): void
    {
        $directory = "assets/{$pagePath}";

        if ($this->disk->exists($directory) && empty($this->disk->files($directory))) {
            $this->disk->deleteDirectory($directory);
        }
    }

    public function exists(PagePath $pagePath, string $filename): bool
    {
        return $this->disk->exists($this->assetPath($pagePath, $filename));
    }

    public function path(PagePath $pagePath, string $filename): string
    {
        return $this->disk->path($this->assetPath($pagePath, $filename));
    }

    /**
     * @return array<int, array{filename: string, url: string}>
     */
    public function all(PagePath $pagePath): array
    {
        $assetsPath = "assets/{$pagePath}";

        if (! $this->disk->exists($assetsPath)) {
            return [];
        }

        $assets = [];

        foreach ($this->disk->files($assetsPath) as $file) {
            $assets[] = [
                'filename' => basename($file),
                'url' => route('assets.show', [$pagePath->__toString(), basename($file)]),
            ];
        }

        return $assets;
    }

    private function assetPath(PagePath $pagePath, string $filename): string
    {
        return "assets/{$pagePath}/$filename";
    }
}
