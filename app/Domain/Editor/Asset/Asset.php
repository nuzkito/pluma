<?php

namespace App\Domain\Editor\Asset;

use App\Domain\Editor\Page\PagePath;

class Asset
{
    public function __construct(
        public PagePath $pagePath,
        public string $name,
    ) {}
}
