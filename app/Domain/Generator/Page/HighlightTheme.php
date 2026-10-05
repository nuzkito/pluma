<?php

namespace App\Domain\Generator\Page;

use Composer\InstalledVersions;
use Tempest\Highlight\Highlighter;
use Tempest\Highlight\Themes\InlineTheme;

class HighlightTheme
{
    public const string DEFAULT = 'highlight-light-lite';

    public static function highlighter(string $theme): Highlighter
    {
        return new Highlighter(new InlineTheme(self::path($theme)));
    }

    private static function path(string $theme): string
    {
        $directory = InstalledVersions::getInstallPath('tempest/highlight').'/src/Themes/Css';

        if (preg_match('/^[\w-]+$/', $theme) !== 1 || ! is_file("{$directory}/{$theme}.css")) {
            $theme = self::DEFAULT;
        }

        return "{$directory}/{$theme}.css";
    }
}
