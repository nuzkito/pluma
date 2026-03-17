<?php

namespace App\Http\Controllers\Attachment;

use App\Domain\Attachment\AttachmentRepository;
use App\Domain\Attachment\NewAttachment;
use App\Domain\Page\PageRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class UploadAttachmentController
{
    public function __invoke(
        Request $request,
        PageRepository $repository,
        AttachmentRepository $attachments,
        string $path,
    ): JsonResponse {
        $page = $repository->findByPath($path);

        if (! $page) {
            return response()->json(['error' => 'Page not found'], 404);
        }

        $request->validate([
            'file' => ['required', 'file', 'max:20480'],
        ]);

        $file = $request->file('file');
        $name = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $extension = $file->getClientOriginalExtension();
        $filename = Str::slug($name).($extension ? ".$extension" : '');

        $attachment = new NewAttachment(pagePath: $page->path, name: $filename, file: $file);
        $attachments->save($attachment);

        return response()->json([
            'success' => true,
            'filename' => $filename,
            'url' => route('attachments.show', [$path, $filename]),
        ]);
    }
}
