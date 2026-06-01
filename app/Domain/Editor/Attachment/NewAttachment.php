<?php

namespace App\Domain\Editor\Attachment;

use App\Domain\Editor\Page\PagePath;
use Illuminate\Http\UploadedFile;

class NewAttachment
{
    public function __construct(
        public PagePath $pagePath,
        public string $name,
        public UploadedFile $file,
    ) {}
}
