<?php

namespace App\Domain\Generator\Page;

use InvalidArgumentException;
use League\CommonMark\Extension\CommonMark\Node\Block\FencedCode;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use Tempest\Highlight\Highlighter;
use Tempest\Highlight\WebTheme;

/**
 * Replaces tempest/highlight's code block renderer to wrap the highlighted code
 * in a `<code class="language-*">` element inside the `<pre>`.
 */
class SemanticCodeBlockRenderer implements NodeRendererInterface
{
    public function __construct(private readonly Highlighter $highlighter) {}

    public function render(Node $node, ChildNodeRendererInterface $childRenderer): string
    {
        if (! $node instanceof FencedCode) {
            throw new InvalidArgumentException('Block must be instance of '.FencedCode::class);
        }

        preg_match('/^(?<language>\w+)(\{(?<startAt>\d+)\})?/', $node->getInfoWords()[0] ?? 'txt', $matches);

        $language = $matches['language'] ?? null;
        $highlighter = $this->highlighter;

        if (($matches['startAt'] ?? '') !== '') {
            $highlighter = $highlighter->withGutter((int) $matches['startAt']);
        }

        $highlightedCode = $highlighter->parse($node->getLiteral(), $language ?? 'txt');
        $code = $language === null
            ? '<code>'.$highlightedCode.'</code>'
            : '<code class="language-'.$language.'">'.$highlightedCode.'</code>';

        /** @var WebTheme $theme */
        $theme = $highlighter->getTheme();

        return $theme->preBefore($highlighter).$code.$theme->preAfter($highlighter);
    }
}
