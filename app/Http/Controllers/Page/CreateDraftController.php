<?php

namespace App\Http\Controllers\Page;

use App\Domain\Page\DraftNameGenerator;
use App\Domain\Page\Page;
use App\Domain\Page\PageRepository;
use Illuminate\Http\RedirectResponse;

class CreateDraftController
{
    public function __invoke(PageRepository $repository, DraftNameGenerator $nextDraftName): RedirectResponse
    {
        $title = $nextDraftName->__invoke();
        $page = Page::draft($title);
        $repository->save($page);

        return redirect()->route('pages.edit', $page->path);
    }
}
