<?php

namespace App\Domain\Editor\Tag;

class CreateTag
{
    public function __construct(
        private TagRepository $repository,
        private TagSynchronizer $site,
    ) {}

    public function __invoke(string $name): Tag
    {
        $name = trim($name);

        $existingTag = $this->repository->findByName($name);

        if ($existingTag !== null) {
            return $existingTag;
        }

        $tag = Tag::create($this->repository->nextId($name), $name);

        $this->repository->save($tag);

        $this->site->refresh($tag);

        return $tag;
    }
}
