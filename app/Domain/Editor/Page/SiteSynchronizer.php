<?php

namespace App\Domain\Editor\Page;

use App\Domain\Generator\SiteGenerator;

/**
 * Reflects the changes an action makes to a page in the generated site.
 */
class SiteSynchronizer
{
    public function __construct(private SiteGenerator $generator) {}

    /**
     * Regenerate a published page, the pages of the given tags, and the index.
     *
     * Drafts are not part of the generated site, so nothing is written for them.
     */
    public function refresh(Page $page, string ...$tags): void
    {
        if (! $page->isPublished()) {
            return;
        }

        $this->generator->generatePage((string) $page->path);

        foreach ($tags as $tag) {
            $this->generator->generatePage((string) TagPage::create($tag)->path);
        }

        $this->refreshIndex();
    }

    /**
     * Regenerate the page when it is published, remove it when it is not, then refresh the index.
     */
    public function refreshOrWithdraw(Page $page): void
    {
        $page->isPublished()
            ? $this->generator->generatePage((string) $page->path)
            : $this->generator->removePage((string) $page->path);

        $this->refreshIndex();
    }

    /**
     * Move a published page away from its old path in the generated site, then refresh the index.
     */
    public function move(Page $page, string $oldPath): void
    {
        if (! $page->isPublished()) {
            return;
        }

        $this->generator->removePage($oldPath);

        $this->refresh($page);
    }

    /**
     * Remove a page from the generated site and refresh the index.
     */
    public function withdraw(string $path): void
    {
        $this->generator->removePage($path);

        $this->refreshIndex();
    }

    /**
     * Rebuild the index, the 404 page and the feed.
     */
    public function refreshIndex(): void
    {
        $this->generator->regenerateIndex();
    }
}
