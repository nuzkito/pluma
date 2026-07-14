<?php

namespace App\Domain\Generator\Page;

use Illuminate\Support\Str;
use League\CommonMark\Extension\Embed\EmbedExtension;

class Markdown
{
    public function __construct(public readonly string $value) {}

    public function __toString(): string
    {
        return $this->value;
    }

    public function html(): string
    {
        if (! config('pluma.embedding.enabled')) {
            return Str::of($this->value)->markdown();
        }

        return Str::of($this->value)->markdown([
            'embed' => [
                'adapter' => app(YoutubeNocookieEmbedAdapter::class),
                'allowed_domains' => config('pluma.embedding.allowed_domains'),
            ],
        ], [
            new EmbedExtension,
        ]);
    }
}
