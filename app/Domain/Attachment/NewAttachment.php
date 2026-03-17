<?php

namespace App\Domain\Attachment;

use App\Domain\Page\PagePath;
use Illuminate\Http\UploadedFile;

class NewAttachment
{
    public function __construct(
        public PagePath $pagePath,
        public string $name,
        public UploadedFile $file,
    ) {}
}
