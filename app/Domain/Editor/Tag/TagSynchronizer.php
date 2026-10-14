<?php

namespace App\Domain\Editor\Tag;

use App\Domain\Generator\SiteGenerator;

class TagSynchronizer
{
    public function __construct(private SiteGenerator $generator) {}

    public function refresh(Tag $tag): void
    {
        $this->generator->generateTagPage($tag->id);
    }

    public function refreshWithPages(Tag $tag): void
    {
        $this->refresh($tag);

        $this->generator->generatePagesTaggedWith($tag->id);

        $this->generator->regenerateIndex();
    }

    public function move(Tag $tag, string $oldPath): void
    {
        $this->generator->removePage($oldPath);

        $this->refreshWithPages($tag);
    }
}
