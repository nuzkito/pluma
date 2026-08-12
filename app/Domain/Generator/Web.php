<?php

namespace App\Domain\Generator;

use App\Domain\Generator\CoverImage\CoverImage;
use App\Domain\Generator\CoverImage\EmptyCoverImage;
use App\Domain\Generator\CoverImage\SiteCoverImage;

class Web
{
    public function __construct(
        public Url $url,
        public string $title,
        public string $description,
        public CoverImage $cover_image,
    ) {}

    public static function fromConfig(): self
    {
        $url = new Url((string) config('pluma.url'));
        $coverImage = (string) config('pluma.cover_image');

        return new self(
            url: $url,
            title: (string) config('pluma.title'),
            description: (string) config('pluma.description'),
            cover_image: $coverImage === ''
                ? new EmptyCoverImage
                : new SiteCoverImage($coverImage, $url),
        );
    }
}
