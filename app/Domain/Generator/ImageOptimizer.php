<?php

namespace App\Domain\Generator;

use Illuminate\Support\Facades\Image;

class ImageOptimizer
{
    private const int QUALITY = 75;

    private const int MAX_WIDTH = 1600;

    /** @var list<string> */
    private const array OPTIMIZABLE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];

    public function isOptimizable(string $path): bool
    {
        return in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), self::OPTIMIZABLE_EXTENSIONS, true);
    }

    /**
     * @return array{int, int} The width and the height.
     */
    public function optimizedSize(int $width, int $height): array
    {
        if ($width <= self::MAX_WIDTH) {
            return [$width, $height];
        }

        return [self::MAX_WIDTH, max(1, (int) round(self::MAX_WIDTH * $height / $width))];
    }

    public function optimize(string $source, string $destination): void
    {
        Image::fromStorage($source, 'current')
            ->scale(width: self::MAX_WIDTH)
            ->quality(self::QUALITY)
            ->storeAs($destination, disk: 'current');
    }
}
