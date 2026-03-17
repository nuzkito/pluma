<?php

test('creates a new draft and redirects to edit', function () {
    $repository = initializeSite();

    $response = $this->post('/pages');

    $page = $repository->all()->first();

    $response->assertRedirect(route('pages.edit', $page->path));
    expect($page)->not->toBeNull()
        ->and($page->title)->toBe('Draft');
});

test('creates sequential drafts', function () {
    $repository = initializeSite();

    $this->post('/pages');
    $this->post('/pages');

    $pages = $repository->all();

    expect($pages)->toHaveCount(2);

    $titles = $pages->pluck('title')->sort()->values();
    expect($titles[0])->toBe('Draft')
        ->and($titles[1])->toBe('Draft 2');
});
