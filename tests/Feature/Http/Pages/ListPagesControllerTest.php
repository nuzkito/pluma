<?php

use App\Domain\Editor\Page\Page;

test('shows the pages index', function () {
    $repository = initializeSite();

    $page = Page::draft('Test Page');
    $repository->save($page);

    $this->get('/')
        ->assertSuccessful()
        ->assertSee('Test Page');
});
