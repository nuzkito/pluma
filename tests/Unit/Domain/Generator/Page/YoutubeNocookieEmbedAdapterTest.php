<?php

use App\Domain\Generator\Page\YoutubeNocookieEmbedAdapter;
use League\CommonMark\Extension\Embed\Embed;
use League\CommonMark\Extension\Embed\EmbedAdapterInterface;

test('rewrites youtube embed codes to the nocookie domain', function () {
    $innerAdapter = new class implements EmbedAdapterInterface
    {
        public function updateEmbeds(array $embeds): void
        {
            foreach ($embeds as $embed) {
                $embed->setEmbedCode('<iframe src="https://www.youtube.com/embed/abc123"></iframe>');
            }
        }
    };

    $embed = new Embed('https://www.youtube.com/watch?v=abc123');

    new YoutubeNocookieEmbedAdapter($innerAdapter)->updateEmbeds([$embed]);

    expect($embed->getEmbedCode())->toBe('<iframe src="https://www.youtube-nocookie.com/embed/abc123"></iframe>');
});

test('leaves non youtube embed codes untouched', function () {
    $vimeoEmbedCode = '<iframe src="https://player.vimeo.com/video/123456"></iframe>';

    $innerAdapter = new class($vimeoEmbedCode) implements EmbedAdapterInterface
    {
        public function __construct(private string $embedCode) {}

        public function updateEmbeds(array $embeds): void
        {
            foreach ($embeds as $embed) {
                $embed->setEmbedCode($this->embedCode);
            }
        }
    };

    $embed = new Embed('https://vimeo.com/123456');

    new YoutubeNocookieEmbedAdapter($innerAdapter)->updateEmbeds([$embed]);

    expect($embed->getEmbedCode())->toBe($vimeoEmbedCode);
});

test('ignores embeds the inner adapter could not resolve', function () {
    $innerAdapter = new class implements EmbedAdapterInterface
    {
        public function updateEmbeds(array $embeds): void {}
    };

    $embed = new Embed('https://www.youtube.com/watch?v=abc123');

    new YoutubeNocookieEmbedAdapter($innerAdapter)->updateEmbeds([$embed]);

    expect($embed->getEmbedCode())->toBeNull();
});
