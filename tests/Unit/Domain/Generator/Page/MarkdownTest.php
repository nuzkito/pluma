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

test('highlights the syntax of fenced code blocks', function () {
    $markdown = new Markdown("```php\n\$page = new Page();\n```");

    expect($markdown->html())
        ->toContain('<pre data-lang="php"')
        ->toContain('<span style="color: #4285F4;">new</span>');
});

test('wraps fenced code in a code element inside the pre', function () {
    $markdown = new Markdown("```php\n\$page = new Page();\n```");

    expect($markdown->html())
        ->toMatch('/<pre [^>]*><code class="language-php">.*<\/code><\/pre>/s');
});

test('does not add a language class to fenced code without a language', function () {
    $markdown = new Markdown("```\nplain text\n```");

    expect($markdown->html())
        ->toMatch('/<pre [^>]*><code>.*<\/code><\/pre>/s')
        ->not->toContain('language-');
});

test('highlights code blocks when embedded content is enabled', function () {
    config(['pluma.embedding.enabled' => true]);

    $markdown = new Markdown("```php\n\$page = new Page();\n```");

    expect($markdown->html())->toContain('<span style="color: #4285F4;">new</span>');
});

test('escapes html inside code', function () {
    $markdown = new Markdown("Inline `<script>` code\n\n```html\n<script>alert(1)</script>\n```");

    expect($markdown->html())->not->toContain('<script>');
});

test('highlights code blocks with the configured theme', function () {
    config(['pluma.highlight.theme' => 'dracula']);

    $markdown = new Markdown("```php\n\$page = new Page();\n```");

    expect($markdown->html())
        ->toContain('background-color: #282A36;')
        ->toContain('<span style="color: #FF79C6;">new</span>');
});
