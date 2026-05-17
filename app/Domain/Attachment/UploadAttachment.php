<?php

namespace App\Domain\Attachment;

use App\Domain\Page\PagePath;
use Illuminate\Http\UploadedFile;

class UploadAttachment
{
    public function __construct(
        private AttachmentRepository $attachmentRepo,
    ) {}

    /**
     * @param  UploadedFile[]  $files
     * @return array<int, array{filename: string, url: string}>
     */
    public function __invoke(string $pagePath, array $files): array
    {
        return array_values(array_map(function (UploadedFile $file) use ($pagePath) {
            $filename = $file->getClientOriginalName();

            $this->attachmentRepo->save(new NewAttachment(
                pagePath: new PagePath($pagePath),
                name: $filename,
                file: $file,
            ));

            return [
                'filename' => $filename,
                'url' => route('attachments.show', [$pagePath, $filename]),
            ];
        }, $files));
    }
}
