<?php

use App\Domain\Page\Markdown;

test('returns raw value as string', function () {
    $markdown = new Markdown('# Hello');

    expect((string) $markdown)->toBe('# Hello');
});

test('converts markdown to html', function () {
    $markdown = new Markdown('# Hello World');

    expect($markdown->html())->toContain('<h1>Hello World</h1>');
});
