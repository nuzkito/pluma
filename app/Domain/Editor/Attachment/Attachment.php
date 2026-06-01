<?php

namespace App\Domain\Editor\Attachment;

use App\Domain\Editor\Page\PagePath;

class Attachment
{
    public function __construct(
        public PagePath $pagePath,
        public string $name,
    ) {}
}
