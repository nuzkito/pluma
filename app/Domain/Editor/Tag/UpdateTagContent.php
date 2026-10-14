<?php

namespace App\Domain\Editor\Tag;

use App\Domain\Editor\Page\Markdown;

class UpdateTagContent
{
    public function __construct(
        private TagRepository $repository,
        private TagSynchronizer $site,
    ) {}

    public function __invoke(string $id, string $content): Tag
    {
        $tag = $this->repository->find($id);

        if ((string) $tag->content === $content) {
            return $tag;
        }

        $tag->setContent(new Markdown($content));

        $this->repository->save($tag);

        $this->site->refresh($tag);

        return $tag;
    }
}
