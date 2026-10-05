<?php

use App\Domain\Generator\Page\HighlightTheme;

test('highlights with the colors of the given theme', function () {
    expect(HighlightTheme::highlighter('dracula')->parse('new Page();', 'php'))
        ->toContain('<span style="color: #FF79C6;">new</span>');
});

test('falls back to the default theme when the given one does not exist', function (string $theme) {
    expect(HighlightTheme::highlighter($theme)->parse('new Page();', 'php'))
        ->toContain('<span style="color: #4285F4;">new</span>');
})->with([
    'unknown name' => ['unknown'],
    'path outside the themes directory' => ['../../../../composer'],
]);
