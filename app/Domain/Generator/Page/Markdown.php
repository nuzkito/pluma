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
        return Str::of($this->value)->markdown([
            'embed' => [
                'adapter' => app(YoutubeNocookieEmbedAdapter::class),
            ],
        ], [
            new EmbedExtension,
        ]);
    }
}
