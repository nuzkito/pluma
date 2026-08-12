<?php

namespace App\Domain\Generator\CoverImage;

use App\Domain\Generator\Url;

class SiteCoverImage implements CoverImage
{
    public function __construct(
        public readonly string $value,
        private readonly Url $baseUrl,
    ) {}

    public function __toString(): string
    {
        return $this->value;
    }

    public function isDefined(): bool
    {
        return true;
    }

    /**
     * Build the full url of the image on the site.
     */
    public function url(): Url
    {
        return $this->baseUrl->append(rawurlencode($this->value));
    }
}
