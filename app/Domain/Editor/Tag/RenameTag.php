<?php

namespace App\Domain\Editor\Tag;

use App\Domain\Error;
use App\Domain\Ok;
use App\Domain\Result;

class RenameTag
{
    public function __construct(
        private TagRepository $repository,
        private TagSynchronizer $site,
    ) {}

    /**
     * @return Result<Tag>
     */
    public function __invoke(string $id, string $newName): Result
    {
        $newName = trim($newName);
        $tag = $this->repository->find($id);

        if ($newName === '') {
            return new Error('The name of a tag cannot be empty.');
        }

        if ($this->repository->findByName($newName, $id) !== null) {
            return new Error('Another tag already has this name.');
        }

        $newSlug = $tag->slugAfterRenaming($newName);

        if ($newSlug === '' || $this->repository->findBySlug($newSlug, $id) !== null) {
            return new Error('This name generates the same slug as another tag that already exists.');
        }

        $oldPath = $tag->path;

        $tag->rename($newName);

        $this->repository->save($tag);

        $oldPath === $tag->path
            ? $this->site->refreshWithPages($tag)
            : $this->site->move($tag, $oldPath);

        return new Ok($tag);
    }
}
