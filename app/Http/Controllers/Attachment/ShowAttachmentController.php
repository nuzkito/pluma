<?php

namespace App\Http\Controllers\Attachment;

use App\Domain\Editor\Attachment\AttachmentRepository;
use App\Domain\Editor\Page\PageRepository;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ShowAttachmentController
{
    public function __invoke(
        PageRepository $repository,
        AttachmentRepository $attachments,
        string $path,
        string $filename,
    ): BinaryFileResponse {
        $page = $repository->findByPath($path);

        if (! $page) {
            abort(404);
        }

        if (! $attachments->exists($page->path, $filename)) {
            abort(404);
        }

        return response()->file($attachments->path($page->path, $filename));
    }
}
