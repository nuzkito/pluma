<?php

use App\Domain\Editor\Page\ContentPage;

test('shows the pages index', function () {
    $repository = initializeSite();

    $page = ContentPage::draft('Test Page');
    $repository->save($page);

    $this->get('/')
        ->assertSuccessful()
        ->assertSee('Test Page');
});
