<?php

namespace App\Domain\Attachment;

use App\Domain\Page\PagePath;

class Attachment
{
    public function __construct(
        public PagePath $pagePath,
        public string $name,
    ) {}
}
