<?php

namespace App\Http\Controllers\Attachment;

use App\Domain\Attachment\Attachment;
use App\Domain\Attachment\AttachmentRepository;
use App\Domain\Page\PageRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class DeleteAttachmentController
{
    public function __invoke(
        PageRepository $pages,
        AttachmentRepository $attachments,
        string $path,
        string $filename,
    ): JsonResponse {
        $page = $pages->findByPath($path);

        if (! $page) {
            return response()->json(['error' => 'Page not found'], 404);
        }

        $attachment = new Attachment(pagePath: $page->path, name: $filename);

        if (! $attachments->delete($attachment)) {
            return response()->json(['error' => 'Attachment not found'], 404);
        }

        $disk = Storage::disk('current');

        $assetsDir = "assets/{$page->path}";
        if ($disk->exists($assetsDir) && empty($disk->files($assetsDir))) {
            $disk->deleteDirectory($assetsDir);
        }

        if ($page->isPublished()) {
            $disk->delete("site/{$page->path}/$filename");
        }

        return response()->json(['success' => true]);
    }
}
