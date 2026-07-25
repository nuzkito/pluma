<?php

use App\Domain\Editor\Page\ContentPage;
use Illuminate\Support\Facades\Storage;

test('serves an asset', function () {
    $repository = initializeSite();

    $page = ContentPage::draft('Test Page', 'test-page');
    $repository->save($page);

    Storage::disk('current')->put("assets/{$page->path}/test.txt", 'hello');

    $this->get("/pages/{$page->path}/assets/test.txt")
        ->assertSuccessful();
});
