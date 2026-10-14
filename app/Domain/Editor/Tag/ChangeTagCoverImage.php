<?php

namespace App\Domain\Editor\Tag;

class ChangeTagCoverImage
{
    public function __construct(
        private TagRepository $repository,
        private TagSynchronizer $site,
    ) {}

    public function __invoke(string $id, string $coverImage): Tag
    {
        $tag = $this->repository->find($id);

        $tag->changeCoverImage($coverImage);

        $this->repository->save($tag);

        $this->site->refresh($tag);

        return $tag;
    }
}
