<?php

namespace App\Http\Controllers\Asset;

use App\Domain\Editor\Asset\AssetRepository;
use App\Domain\Editor\Tag\TagRepository;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ShowTagAssetController
{
    public function __invoke(
        TagRepository $repository,
        AssetRepository $assets,
        string $id,
        string $filename,
    ): BinaryFileResponse {
        $tag = $repository->find($id);

        abort_unless($tag && $assets->exists($tag->assetsPath(), $filename), 404);

        return response()->file($assets->path($tag->assetsPath(), $filename));
    }
}
