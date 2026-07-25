<?php

namespace App\Domain\Generator;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Throwable;

class AssetProcessor
{
    private Filesystem $disk;

    public function __construct(
        private ImageOptimizer $imageOptimizer,
    ) {
        $this->disk = Storage::disk('current');
    }

    public function copy(string $source, string $destination): void
    {
        if (! $this->imageOptimizer->isOptimizable($source)) {
            $this->disk->copy($source, $destination);

            return;
        }

        try {
            $this->imageOptimizer->optimize($source, $destination);
        } catch (Throwable) {
            $this->disk->copy($source, $destination);
        }
    }
}
