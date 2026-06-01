<?php

namespace App\Domain\Editor\Page;

use Illuminate\Support\Str;

class DraftNameGenerator
{
    public function __construct(private PageRepository $repository) {}

    public function __invoke(): string
    {
        $existingDrafts = $this->repository->all()
            ->filter(fn (Page $page) => Str::of($page->title)->startsWith('Draft'))
            ->map(fn (Page $page) => $page->title);

        if ($existingDrafts->isEmpty()) {
            return 'Draft';
        }

        $maxNumber = $existingDrafts
            ->map(function (string $title) {
                if ($title === 'Draft') {
                    return 1;
                }
                if (preg_match('/^Draft (\d+)$/', $title, $matches)) {
                    return (int) $matches[1];
                }

                return 0;
            })
            ->max();

        return 'Draft '.($maxNumber + 1);
    }
}
