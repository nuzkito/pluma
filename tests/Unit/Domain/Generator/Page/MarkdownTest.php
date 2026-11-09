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

test('escapes style tags by default', function () {
    $markdown = new Markdown("<style>\np { color: red; }\n</style>");

    expect($markdown->html())
        ->not->toContain('<style>')
        ->toContain('&lt;style>');
});

test('renders style tags as html when they are allowed', function (bool $embeddingEnabled) {
    config([
        'pluma.html.allow_style_tags' => true,
        'pluma.embedding.enabled' => $embeddingEnabled,
    ]);

    $markdown = new Markdown("<style>\np { color: red; }\n</style>\n\n<script>alert(1)</script>");

    expect($markdown->html())
        ->toContain("<style>\np { color: red; }\n</style>")
        ->not->toContain('<script>');
})->with([
    'without embedded content' => false,
    'with embedded content' => true,
]);

test('leaves style blocks out of the excerpt when style tags are allowed', function () {
    config(['pluma.html.allow_style_tags' => true]);

    $markdown = new Markdown("<style>\np { color: red; }\n</style>\n\nThe page text.");

    expect($markdown->excerpt())->toBe('The page text.');
});

test('escapes iframes by default', function () {
    $markdown = new Markdown('<iframe src="https://example.com"></iframe>');

    expect($markdown->html())
        ->not->toContain('<iframe')
        ->toContain('&lt;iframe');
});

test('renders iframes as html when they are allowed', function (bool $embeddingEnabled) {
    config([
        'pluma.html.allow_iframes' => true,
        'pluma.embedding.enabled' => $embeddingEnabled,
    ]);

    $markdown = new Markdown("<iframe src=\"https://example.com\"></iframe>\n\n<style>\np { color: red; }\n</style>");

    expect($markdown->html())
        ->toContain('<iframe src="https://example.com"></iframe>')
        ->not->toContain('<style>');
})->with([
    'without embedded content' => false,
    'with embedded content' => true,
]);

test('leaves iframes out of the excerpt when they are allowed', function () {
    config(['pluma.html.allow_iframes' => true]);

    $markdown = new Markdown("<iframe src=\"https://example.com\">Fallback text</iframe>\n\nThe page text.");

    expect($markdown->excerpt())->toBe('The page text.');
});
