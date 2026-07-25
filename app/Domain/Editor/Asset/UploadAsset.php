<?php

namespace App\Domain\Editor\Asset;

use App\Domain\Editor\Page\PagePath;
use Illuminate\Http\UploadedFile;

class UploadAsset
{
    public function __construct(
        private AssetRepository $assetRepo,
    ) {}

    /**
     * @param  UploadedFile[]  $files
     * @return array<int, array{filename: string, url: string}>
     */
    public function __invoke(string $pagePath, array $files): array
    {
        return array_values(array_map(function (UploadedFile $file) use ($pagePath) {
            $filename = $file->getClientOriginalName();

            $this->assetRepo->save(new NewAsset(
                pagePath: new PagePath($pagePath),
                name: $filename,
                file: $file,
            ));

            return [
                'filename' => $filename,
                'url' => route('assets.show', [$pagePath, $filename]),
            ];
        }, $files));
    }
}
