<?php

namespace App\Http\Controllers\Asset;

use App\Domain\Editor\Asset\AssetRepository;
use App\Domain\Editor\Page\PageRepository;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ShowAssetController
{
    public function __invoke(
        PageRepository $repository,
        AssetRepository $assets,
        string $path,
        string $filename,
    ): BinaryFileResponse {
        $page = $repository->findByPath($path);

        if (! $page) {
            abort(404);
        }

        if (! $assets->exists($page->path, $filename)) {
            abort(404);
        }

        return response()->file($assets->path($page->path, $filename));
    }
}
