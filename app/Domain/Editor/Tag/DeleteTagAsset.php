<?php

namespace App\Domain\Editor\Tag;

use App\Domain\Editor\Asset\Asset;
use App\Domain\Editor\Asset\AssetRepository;
use App\Domain\Generator\SiteGenerator;

class DeleteTagAsset
{
    public function __construct(
        private TagRepository $repository,
        private AssetRepository $assets,
        private SiteGenerator $siteGenerator,
        private TagSynchronizer $site,
    ) {}

    public function __invoke(string $id, string $filename): void
    {
        $tag = $this->repository->find($id);

        if (! $tag) {
            return;
        }

        $this->assets->delete(new Asset(
            pagePath: $tag->assetsPath(),
            name: $filename,
        ));

        $this->assets->pruneEmptyDirectory($tag->assetsPath());

        $this->siteGenerator->removePageFile($tag->path, $filename);

        if ($tag->cover_image === $filename) {
            $tag->removeCoverImage();
            $this->repository->save($tag);
            $this->site->refresh($tag);
        }
    }
}
