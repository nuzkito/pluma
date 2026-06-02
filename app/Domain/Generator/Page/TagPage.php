<?php

namespace App\Domain\Generator\Page;

use Carbon\Carbon;

class TagPage
{
    public function __construct(
        public string $title,
        public PagePath $path,
        public Markdown $content,
        public Carbon $created_at,
    ) {}
}
