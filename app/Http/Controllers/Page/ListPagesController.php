<?php

namespace App\Http\Controllers\Page;

use App\Domain\Page\PageRepository;
use Illuminate\View\View;

class ListPagesController
{
    public function __invoke(PageRepository $repository): View
    {
        $pages = $repository->all();

        return view('page.index', compact('pages'));
    }
}
