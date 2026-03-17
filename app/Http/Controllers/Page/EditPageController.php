<?php

namespace App\Http\Controllers\Page;

use App\Domain\Attachment\AttachmentRepository;
use App\Domain\Page\PageRepository;
use Illuminate\View\View;

class EditPageController
{
    public function __invoke(
        PageRepository $repository,
        AttachmentRepository $attachments,
        string $path,
    ): View {
        $page = $repository->findByPath($path);

        if (! $page) {
            abort(404);
        }

        $attachments = $attachments->all($page->path);

        return view('page.edit', compact('page', 'attachments'));
    }
}
