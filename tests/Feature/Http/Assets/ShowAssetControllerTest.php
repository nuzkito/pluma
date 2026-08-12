<?php

test('serves an asset', function () {
    $page = aPage('Test Page', 'test-page');

    disk()->put("assets/{$page->path}/test.txt", 'hello');

    $this->get("/pages/{$page->path}/assets/test.txt")
        ->assertSuccessful();
});
