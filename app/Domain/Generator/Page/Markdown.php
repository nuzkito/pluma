<?php

namespace App\Domain\Generator\Page;

use Illuminate\Support\Str;
use League\CommonMark\Extension\Embed\EmbedExtension;
use Tempest\Highlight\CommonMark\HighlightExtension;

class Markdown
{
    private const array DISALLOWED_TAGS = [
        'title',
        'textarea',
        'style',
        'xmp',
        'iframe',
        'noembed',
        'noframes',
        'script',
        'plaintext',
    ];

    public function __construct(public readonly string $value) {}

    public function __toString(): string
    {
        return $this->value;
    }

    public function excerpt(int $length = 160): string
    {
        $html = Str::markdown($this->value, $this->rawHtmlOptions());
        $htmlWithoutStylesAndIframes = preg_replace('/<(style|iframe)\b[^>]*>.*?<\/\1>/is', '', $html);
        $text = html_entity_decode(strip_tags($htmlWithoutStylesAndIframes), ENT_QUOTES | ENT_HTML5);

        return Str::of($text)
            ->squish()
            ->limit($length)
            ->toString();
    }

    public function html(): string
    {
        $options = $this->rawHtmlOptions();

        if (! config('pluma.embedding.enabled')) {
            return Str::of($this->value)->markdown($options, $this->highlightExtensions());
        }

        return Str::of($this->value)->markdown([
            ...$options,
            'embed' => [
                'adapter' => app(YoutubeNocookieEmbedAdapter::class),
                'allowed_domains' => config('pluma.embedding.allowed_domains'),
            ],
        ], [
            new EmbedExtension,
            ...$this->highlightExtensions(),
        ]);
    }

    /**
     * @return array{disallowed_raw_html: array{disallowed_tags: list<string>}}
     */
    private function rawHtmlOptions(): array
    {
        return [
            'disallowed_raw_html' => [
                'disallowed_tags' => $this->disallowedTags(),
            ],
        ];
    }

    /**
     * @return list<string>
     */
    private function disallowedTags(): array
    {
        $allowedTags = array_keys(array_filter([
            'style' => config('pluma.html.allow_style_tags'),
            'iframe' => config('pluma.html.allow_iframes'),
        ]));

        return array_values(array_diff(self::DISALLOWED_TAGS, $allowedTags));
    }

    /**
     * @return array{HighlightExtension, SemanticCodeBlockExtension}
     */
    private function highlightExtensions(): array
    {
        $highlighter = HighlightTheme::highlighter((string) config('pluma.highlight.theme'));

        return [
            new HighlightExtension($highlighter),
            new SemanticCodeBlockExtension($highlighter),
        ];
    }
}
