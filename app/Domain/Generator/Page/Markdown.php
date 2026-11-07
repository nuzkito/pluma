<?php

namespace App\Domain\Generator\Page;

use Illuminate\Support\Str;
use League\CommonMark\Extension\Embed\EmbedExtension;
use Tempest\Highlight\CommonMark\HighlightExtension;

class Markdown
{
    public function __construct(public readonly string $value) {}

    public function __toString(): string
    {
        return $this->value;
    }

    public function excerpt(int $length = 160): string
    {
        $text = html_entity_decode(strip_tags(Str::markdown($this->value)), ENT_QUOTES | ENT_HTML5);

        return Str::of($text)
            ->squish()
            ->limit($length)
            ->toString();
    }

    public function html(): string
    {
        if (! config('pluma.embedding.enabled')) {
            return Str::of($this->value)->markdown(extensions: $this->highlightExtensions());
        }

        return Str::of($this->value)->markdown([
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
