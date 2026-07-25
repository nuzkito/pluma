<?php

namespace App\Domain\Editor\Asset;

use App\Domain\Editor\Page\PagePath;
use Illuminate\Http\UploadedFile;

class NewAsset
{
    public function __construct(
        public PagePath $pagePath,
        public string $name,
        public UploadedFile $file,
    ) {}
}
