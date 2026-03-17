<?php

namespace App\Http\Controllers\Page;

use App\Domain\Page\PageRepository;
use App\Domain\Page\SiteGenerator;
use Illuminate\Http\JsonResponse;

class UnpublishPageController
{
    public function __invoke(
        PageRepository $repository,
        SiteGenerator $generator,
        string $path,
    ): JsonResponse {
        $page = $repository->findByPath($path);

        if (! $page) {
            return response()->json(['error' => 'Page not found'], 404);
        }

        $generator->removePage($page);

        $page->unpublish();
        $repository->save($page);

        $generator->regenerateIndex();

        return response()->json([
            'success' => true,
            'page' => $page->toArray(),
        ]);
    }
}
