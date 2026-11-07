<?php

namespace App\Domain\Generator;

class ImageDetails
{
    public function __construct(
        public readonly ?int $width = null,
        public readonly ?int $height = null,
        public readonly ?string $type = null,
    ) {}

    public function size(): ?string
    {
        return $this->width && $this->height ? "{$this->width}x{$this->height}" : null;
    }
}
