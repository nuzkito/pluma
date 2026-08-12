<?php

use App\Domain\Generator\Page\PagePath;
use App\Domain\Generator\Url;

test('is converted to its value as a string', function () {
    expect((string) new Url('https://example.com'))->toBe('https://example.com');
});

test('removes the trailing slash from the value', function () {
    expect((string) new Url('https://example.com/'))->toBe('https://example.com');
});

test('appends a segment into a new url', function () {
    $url = new Url('https://example.com');

    $appended = $url->append('styles.css');

    expect((string) $appended)->toBe('https://example.com/styles.css')
        ->and((string) $url)->toBe('https://example.com');
});

test('appends segments one after the other', function () {
    $url = (new Url('https://example.com'))->append('tags')->append('laravel');

    expect((string) $url)->toBe('https://example.com/tags/laravel');
});

test('does not duplicate the slashes around an appended segment', function () {
    $url = (new Url('https://example.com'))->append('/hello-world/');

    expect((string) $url)->toBe('https://example.com/hello-world');
});

test('appends anything that can be converted to a string', function () {
    $url = (new Url('https://example.com'))->append(new PagePath('hello-world'));

    expect((string) $url)->toBe('https://example.com/hello-world');
});
