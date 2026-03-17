<?php

use App\Domain\Page\Page;
use Illuminate\Support\Facades\Storage;

test('serves an attachment', function () {
    $repository = initializeSite();

    $page = Page::draft('Test Page');
    $repository->save($page);

    Storage::disk('current')->put("assets/{$page->path}/test.txt", 'hello');

    $this->get("/pages/{$page->path}/attachments/test.txt")
        ->assertSuccessful();
});
