<?php

namespace App\Domain\Editor\Attachment;

use App\Domain\Editor\Page\PagePath;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;

class AttachmentRepository
{
    private Filesystem $disk;

    public function __construct()
    {
        $this->disk = Storage::disk('current');
    }

    public function save(NewAttachment $attachment): void
    {
        $this->disk->putFileAs("assets/{$attachment->pagePath}", $attachment->file, $attachment->name);
    }

    public function delete(Attachment $attachment): bool
    {
        $assetPath = "assets/{$attachment->pagePath}/{$attachment->name}";

        if (! $this->disk->exists($assetPath)) {
            return false;
        }

        $this->disk->delete($assetPath);

        return true;
    }

    public function exists(PagePath $pagePath, string $filename): bool
    {
        return $this->disk->exists($this->assetPath($pagePath, $filename));
    }

    public function path(PagePath $pagePath, string $filename): string
    {
        return $this->disk->path($this->assetPath($pagePath, $filename));
    }

    /**
     * @return array<int, array{filename: string, url: string}>
     */
    public function all(PagePath $pagePath): array
    {
        $attachmentsPath = "assets/{$pagePath}";

        if (! $this->disk->exists($attachmentsPath)) {
            return [];
        }

        $attachments = [];

        foreach ($this->disk->files($attachmentsPath) as $file) {
            $attachments[] = [
                'filename' => basename($file),
                'url' => route('attachments.show', [$pagePath->__toString(), basename($file)]),
            ];
        }

        return $attachments;
    }

    private function assetPath(PagePath $pagePath, string $filename): string
    {
        return "assets/{$pagePath}/$filename";
    }
}
