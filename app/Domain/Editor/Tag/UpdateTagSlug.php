<?php

namespace App\Domain\Editor\Tag;

use App\Domain\Error;
use App\Domain\Ok;
use App\Domain\Result;

class UpdateTagSlug
{
    public function __construct(
        private TagRepository $repository,
        private TagSynchronizer $site,
    ) {}

    /**
     * @return Result<Tag>
     */
    public function __invoke(string $id, string $newSlug): Result
    {
        $newSlug = trim($newSlug);
        $tag = $this->repository->find($id);

        if (! Tag::isValidIdentifier($newSlug)) {
            return new Error('The slug may only contain letters, numbers and hyphens.');
        }

        if ($this->repository->findBySlug($newSlug, $id) !== null) {
            return new Error('Another tag already uses this slug.');
        }

        if ($newSlug === $tag->slug) {
            return new Ok($tag);
        }

        $oldPath = $tag->path;

        $tag->changeSlug($newSlug);

        $this->repository->save($tag);

        $this->site->move($tag, $oldPath);

        return new Ok($tag);
    }
}
