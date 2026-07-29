<?php

namespace App\Http\Controllers\Asset;

use App\Domain\Settings\SiteAssetRepository;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ShowSiteAssetController
{
    public function __invoke(
        SiteAssetRepository $assets,
        string $filename,
    ): BinaryFileResponse {
        if (! $assets->exists($filename)) {
            abort(404);
        }

        return response()->file($assets->path($filename));
    }
}
