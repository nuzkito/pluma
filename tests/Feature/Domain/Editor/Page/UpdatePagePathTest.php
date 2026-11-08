<?php

use App\Domain\Editor\Page\Page;
use App\Domain\Editor\Page\UpdatePagePath;
use App\Domain\Generator\SiteGenerator;

use function Pest\Laravel\mock;

test('returns Ok with page when path is updated successfully', function () {
    aPage('Path Update Test', 'path-update-test');

    $action = app(UpdatePagePath::class);

    $result = $action('path-update-test', 'new-path');

    expect($result)->toBeOk()
        ->and($result->unwrap())->toBeInstanceOf(Page::class);
});

test('returns Error when new path already exists for another page', function () {
    aPage('Existing Page', 'existing-page');

    aPage('Another Page', 'existing-page', content: '# Content');

    $action = app(UpdatePagePath::class);

    $result = $action('another-page', 'existing-page');

    expect($result)->toBeError('A page with this path already exists.');
});

test('moves a published page in the generated site', function () {
    aPublishedPage('Move In Site Test', 'move-in-site-test', content: '# Content');

    app(SiteGenerator::class)->generatePage('move-in-site-test');

    app(UpdatePagePath::class)('move-in-site-test', 'moved-page');

    expect('site/moved-page/index.html')->toExistOnDisk()
        ->and('site/move-in-site-test')->toBeMissingFromDisk()
        ->and(disk()->get('site/index.html'))->toContain('moved-page');
});

test('leaves the generated site alone when the page is a draft', function () {
    aPage('Draft Move Test', 'draft-move-test');

    mock(SiteGenerator::class, function ($mock) {
        $mock->shouldNotReceive('generatePage');
        $mock->shouldNotReceive('removePage');
        $mock->shouldNotReceive('regenerateIndex');
    });

    expect(app(UpdatePagePath::class)('draft-move-test', 'moved-draft'))->toBeOk();
});

test('returns Error and keeps the page when the new path has invalid segments', function (string $newPath) {
    aPage('Invalid Path Test', 'invalid-path-test');

    $result = app(UpdatePagePath::class)('invalid-path-test', $newPath);

    expect($result)->toBeError()
        ->and('pages/invalid-path-test.md')->toExistOnDisk();
})->with([
    'parent directory' => '../outside',
    'empty segment' => 'a//b',
    'current directory' => 'a/./b',
    'leading slash' => '/outside',
    'backslash' => 'a\\b',
    'control character' => "a\nb",
]);

test('accepts nested paths with characters other than a slug', function () {
    aPage('Nested Path Test', 'nested-path-test');

    expect(app(UpdatePagePath::class)('nested-path-test', 'blog/Mi Página_2'))->toBeOk()
        ->and('pages/blog/Mi Página_2.md')->toExistOnDisk();
});
