<?php

namespace App\Domain\Settings;

use Composer\InstalledVersions;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class SettingsSchema
{
    private const array EXCLUDED_HIGHLIGHT_THEMES = ['ellison'];

    /**
     * Every possible setting, in display order.
     *
     * @return list<SettingDefinition>
     */
    public static function definitions(): array
    {
        $highlightThemes = self::highlightThemes();

        return [
            new SettingDefinition(
                key: 'editor_url',
                label: 'Editor URL',
                description: 'The URL where this editor is served.',
                type: SettingType::String,
                group: 'General',
                rules: ['required', 'url'],
            ),
            new SettingDefinition(
                key: 'url',
                label: 'Site URL',
                description: 'The public URL to preview the generated site.',
                type: SettingType::String,
                group: 'General',
                rules: ['required', 'url'],
            ),
            new SettingDefinition(
                key: 'title',
                label: 'Title',
                description: 'The site title, used in page titles and the RSS feed.',
                type: SettingType::String,
                group: 'General',
                rules: ['nullable', 'string'],
            ),
            new SettingDefinition(
                key: 'description',
                label: 'Description',
                description: 'A short summary of the site, used in metadata and the RSS feed.',
                type: SettingType::String,
                group: 'General',
                rules: ['nullable', 'string'],
            ),
            new SettingDefinition(
                key: 'cover_image',
                label: 'Cover image',
                description: 'The image shown in the site index.',
                type: SettingType::Image,
                group: 'General',
            ),

            new SettingDefinition(
                key: 'tags.create_pages',
                label: 'Create tag pages',
                description: 'Generate a page for each tag listing the pages tagged with it.',
                type: SettingType::Boolean,
                group: 'Tags',
            ),
            new SettingDefinition(
                key: 'tags.pages_path',
                label: 'Tag pages path',
                description: 'The path where tag pages are generated, relative to the site root.',
                type: SettingType::String,
                group: 'Tags',
                rules: ['required', 'string'],
            ),

            new SettingDefinition(
                key: 'rss.enabled',
                label: 'Enable RSS feed',
                description: 'Generate an RSS feed for the site.',
                type: SettingType::Boolean,
                group: 'RSS',
            ),

            new SettingDefinition(
                key: 'embedding.enabled',
                label: 'Enable embedded content',
                description: 'Allow embedding external content, such as videos, in pages.',
                type: SettingType::Boolean,
                group: 'Embedding',
            ),
            new SettingDefinition(
                key: 'embedding.allowed_domains',
                label: 'Allowed domains',
                description: 'Domains that embedded content may be loaded from, one per line.',
                type: SettingType::List,
                group: 'Embedding',
            ),

            new SettingDefinition(
                key: 'highlight.theme',
                label: 'Theme',
                description: 'The colors used to highlight the syntax of code blocks.',
                type: SettingType::Select,
                group: 'Syntax highlighting',
                rules: ['required', 'in:'.implode(',', array_keys($highlightThemes))],
                options: $highlightThemes,
            ),
        ];
    }

    public static function find(string $key): ?SettingDefinition
    {
        return collect(self::definitions())
            ->first(fn (SettingDefinition $definition): bool => $definition->key === $key);
    }

    /**
     * @return list<SettingDefinition>
     */
    public static function ofType(SettingType $type): array
    {
        return collect(self::definitions())
            ->filter(fn (SettingDefinition $definition): bool => $definition->type === $type)
            ->values()
            ->all();
    }

    /**
     * Definitions keyed by their group label, preserving declaration order.
     *
     * @return array<string, list<SettingDefinition>>
     */
    public static function grouped(): array
    {
        return collect(self::definitions())
            ->groupBy(fn (SettingDefinition $definition): string => $definition->group)
            ->map(fn (Collection $definitions): array => $definitions->values()->all())
            ->all();
    }

    /** @return array<string, string> */
    private static function highlightThemes(): array
    {
        $directory = InstalledVersions::getInstallPath('tempest/highlight').'/src/Themes/Css';

        return collect(glob($directory.'/*.css'))
            ->map(fn (string $path): string => basename($path, '.css'))
            ->reject(fn (string $name): bool => in_array($name, self::EXCLUDED_HIGHLIGHT_THEMES, true))
            ->sort()
            ->mapWithKeys(fn (string $name): array => [$name => Str::headline($name)])
            ->all();
    }
}
