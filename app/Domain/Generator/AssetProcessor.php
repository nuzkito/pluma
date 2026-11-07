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

    public function imageDetails(string $source): ?ImageDetails
    {
        if (! $this->disk->exists($source)) {
            return null;
        }

        $type = $this->disk->mimeType($source) ?: null;

        if ($type === null || ! str_starts_with($type, 'image/') || str_starts_with($type, 'image/svg')) {
            return new ImageDetails(type: $type);
        }

        $size = @getimagesize($this->disk->path($source));

        if ($size === false) {
            return new ImageDetails(type: $type);
        }

        [$width, $height] = $this->imageOptimizer->isOptimizable($source)
            ? $this->imageOptimizer->optimizedSize($size[0], $size[1])
            : [$size[0], $size[1]];

        return new ImageDetails($width, $height, $type);
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
