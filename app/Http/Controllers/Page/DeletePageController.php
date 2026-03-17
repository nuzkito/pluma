<?php

namespace App\Http\Controllers\Page;

use App\Domain\Page\PageRepository;
use App\Domain\Page\SiteGenerator;
use Illuminate\Http\RedirectResponse;

class DeletePageController
{
    public function __invoke(
        PageRepository $repository,
        SiteGenerator $generator,
        string $path,
    ): RedirectResponse {
        $page = $repository->findByPath($path);

        if (! $page) {
            abort(404);
        }

        $isPublished = $page->isPublished();

        $generator->removePage($page);
        $repository->delete($path);

        if ($isPublished) {
            $generator->regenerateIndex();
        }

        return redirect()->route('pages.index');
    }
}
