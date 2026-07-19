<?php

namespace App\Domain\Editor\Attachment;

use App\Domain\Editor\Page\PagePath;
use App\Domain\Editor\Page\PageRepository;
use App\Domain\Generator\SiteGenerator;

class DeleteAttachment
{
    public function __construct(
        private PageRepository $pageRepository,
        private AttachmentRepository $attachmentRepository,
        private SiteGenerator $siteGenerator,
    ) {}

    public function __invoke(string $pagePath, string $filename): void
    {
        $page = $this->pageRepository->findByPath($pagePath);

        if (! $page) {
            return;
        }

        $this->attachmentRepository->delete(new Attachment(
            pagePath: new PagePath($pagePath),
            name: $filename,
        ));

        if ($page->cover_image === $filename) {
            $page->removeCoverImage();
            $this->pageRepository->save($page);

            if ($page->isPublished()) {
                $this->siteGenerator->generatePage((string) $page->path);
            }
        }

        $this->attachmentRepository->pruneEmptyDirectory($page->path);

        if ($page->isPublished()) {
            $this->siteGenerator->removePageFile((string) $page->path, $filename);
        }
    }
}
