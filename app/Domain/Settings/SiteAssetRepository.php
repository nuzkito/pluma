<?php

namespace App\Domain\Settings;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class SiteAssetRepository
{
    private const string DIRECTORY = 'assets';

    private Filesystem $disk;

    public function __construct()
    {
        $this->disk = Storage::disk('current');
    }

    public function save(UploadedFile $file): string
    {
        $filename = $file->getClientOriginalName();

        $this->disk->putFileAs(self::DIRECTORY, $file, $filename);

        return $filename;
    }

    public function delete(string $filename): bool
    {
        if (! $this->exists($filename)) {
            return false;
        }

        $this->disk->delete($this->assetPath($filename));

        return true;
    }

    public function exists(string $filename): bool
    {
        return $this->disk->exists($this->assetPath($filename));
    }

    public function path(string $filename): string
    {
        return $this->disk->path($this->assetPath($filename));
    }

    private function assetPath(string $filename): string
    {
        return self::DIRECTORY."/$filename";
    }
}
