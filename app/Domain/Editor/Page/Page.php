<?php

namespace App\Domain\Editor\Page;

interface Page
{
    public PagePath $path { get; }

    public Markdown $content { get; }

    public function filename(): string;

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
