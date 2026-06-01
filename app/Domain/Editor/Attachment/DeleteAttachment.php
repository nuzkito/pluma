<?php

namespace App\Domain\Editor\Attachment;

use App\Domain\Editor\Page\PagePath;
use App\Domain\Editor\Page\PageRepository;
use Illuminate\Support\Facades\Storage;

class DeleteAttachment
{
    public function __construct(
        private PageRepository $repository,
        private AttachmentRepository $attachmentRepo,
    ) {}

    public function __invoke(string $pagePath, string $filename): void
    {
        $page = $this->repository->findByPath($pagePath);

        if (! $page) {
            return;
        }

        $this->attachmentRepo->delete(new Attachment(
            pagePath: new PagePath($pagePath),
            name: $filename,
        ));

        $disk = Storage::disk('current');
        $assetsDir = "assets/{$page->path}";

        if ($disk->exists($assetsDir) && empty($disk->files($assetsDir))) {
            $disk->deleteDirectory($assetsDir);
        }

        if ($page->isPublished()) {
            $disk->delete("site/{$page->path}/$filename");
        }
    }
}
