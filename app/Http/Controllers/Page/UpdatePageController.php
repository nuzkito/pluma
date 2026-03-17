<?php

namespace App\Http\Controllers\Page;

use App\Domain\Page\Markdown;
use App\Domain\Page\PagePath;
use App\Domain\Page\PageRepository;
use App\Domain\Page\SiteGenerator;
use App\Http\Requests\UpdatePageRequest;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

use function array_key_exists;

class UpdatePageController
{
    public function __invoke(
        UpdatePageRequest $request,
        PageRepository $repository,
        SiteGenerator $generator,
        string $path,
    ): JsonResponse {
        $page = $repository->findByPath($path);

        if (! $page) {
            return response()->json(['error' => 'Page not found'], 404);
        }

        $validated = $request->validated();

        if (isset($validated['title'])) {
            $page->rename($validated['title']);
        }

        if (isset($validated['path'])) {
            $page->moveToPath(new PagePath($validated['path']));
        }

        if (array_key_exists('content', $validated)) {
            $page->setContent(new Markdown($validated['content']));
        }

        if (isset($validated['rss'])) {
            $page->toggleRss($validated['rss']);
        }

        if (array_key_exists('published_at', $validated)) {
            if ($validated['published_at']) {
                $page->publish(Carbon::parse($validated['published_at']));
            } else {
                $page->unpublish();
            }
        }

        $repository->save($page, $path);

        if ($page->isPublished()) {
            $generator->generatePage($page);
            $generator->regenerateIndex();
        }

        return response()->json([
            'success' => true,
            'page' => $page->toArray(),
        ]);
    }
}
