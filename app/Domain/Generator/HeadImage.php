<?php

namespace App\Domain\Generator;

class HeadImage
{
    public function __construct(
        public readonly Url $url,
        public readonly string $source,
    ) {}
}
