<?php

namespace App\Domain\Generator\Page;

use League\CommonMark\Environment\EnvironmentBuilderInterface;
use League\CommonMark\Extension\CommonMark\Node\Block\FencedCode;
use League\CommonMark\Extension\ExtensionInterface;
use Tempest\Highlight\Highlighter;

/**
 * Renders fenced code with SemanticCodeBlockRenderer instead of the renderer that
 * tempest/highlight's HighlightExtension registers, by taking a higher priority.
 */
class SemanticCodeBlockExtension implements ExtensionInterface
{
    private const int PRIORITY_ABOVE_TEMPEST_RENDERER = 20;

    public function __construct(private readonly Highlighter $highlighter) {}

    public function register(EnvironmentBuilderInterface $environment): void
    {
        $environment->addRenderer(FencedCode::class, new SemanticCodeBlockRenderer($this->highlighter), self::PRIORITY_ABOVE_TEMPEST_RENDERER);
    }
}
