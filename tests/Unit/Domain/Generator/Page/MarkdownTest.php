<?php

use App\Domain\Generator\Page\Markdown;
use App\Domain\Generator\Page\YoutubeNocookieEmbedAdapter;
use League\CommonMark\Extension\Embed\EmbedAdapterInterface;

test('returns raw value as string', function () {
    $markdown = new Markdown('# Hello');

    expect((string) $markdown)->toBe('# Hello');
});

test('converts markdown to html', function () {
    $markdown = new Markdown('# Hello World');

    expect($markdown->html())->toContain('<h1>Hello World</h1>');
});

test('embeds youtube urls as iframe', function () {
    config(['pluma.embedding.enabled' => true]);

    $fakeAdapter = new class implements EmbedAdapterInterface
    {
        public function updateEmbeds(array $embeds): void
        {
            foreach ($embeds as $i => $embed) {
                if (str_contains($embed->getUrl(), 'youtube.com')) {
                    $embed->setEmbedCode(
                        '<iframe src="https://www.youtube.com/embed/W7yxJiPnxpA" frameborder="0" allowfullscreen></iframe>'
                    );
                }
            }
        }
    };

    app()->instance(YoutubeNocookieEmbedAdapter::class, new YoutubeNocookieEmbedAdapter($fakeAdapter));

    $markdown = new Markdown('https://www.youtube.com/watch?v=W7yxJiPnxpA');
    $result = $markdown->html();

    expect($result)->toContain('<iframe');
    expect($result)->toContain('youtube-nocookie.com/embed/');
});

test('does not embed content when embedded content is disabled', function () {
    config(['pluma.embedding.enabled' => false]);

    $markdown = new Markdown('https://www.youtube.com/watch?v=W7yxJiPnxpA');
    $result = $markdown->html();

    expect($result)->not->toContain('<iframe');
});
