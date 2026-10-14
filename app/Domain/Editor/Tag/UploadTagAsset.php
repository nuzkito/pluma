<?php

namespace App\Domain\Editor\Tag;

use App\Domain\Editor\Asset\AssetRepository;
use App\Domain\Editor\Asset\NewAsset;
use Illuminate\Http\UploadedFile;

class UploadTagAsset
{
    public function __construct(
        private TagRepository $repository,
        private AssetRepository $assets,
        private TagSynchronizer $site,
    ) {}

    /**
     * @param  UploadedFile[]  $files
     * @return array<int, array{filename: string, url: string}>
     */
    public function __invoke(string $id, array $files): array
    {
        $tag = $this->repository->find($id);

        $uploaded = array_values(array_map(function (UploadedFile $file) use ($tag) {
            $filename = $file->getClientOriginalName();

            $this->assets->save(new NewAsset(
                pagePath: $tag->assetsPath(),
                name: $filename,
                file: $file,
            ));

            return [
                'filename' => $filename,
                'url' => route('tags.assets.show', [$tag->id, $filename]),
            ];
        }, $files));

        $this->site->refresh($tag);

        return $uploaded;
    }
}
