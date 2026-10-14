<?php

namespace App\Domain\TagMigration;

use App\Domain\Editor\Asset\AssetRepository;
use App\Domain\Editor\Page\Directory;
use App\Domain\Editor\Page\DirectoryRepository;
use App\Domain\Editor\Page\Page;
use App\Domain\Editor\Page\PagePath;
use App\Domain\Editor\Page\PageRepository;
use App\Domain\Editor\Tag\Tag;
use App\Domain\Editor\Tag\TagRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class MigrateTags
{
    public function __construct(
        private PageRepository $pages,
        private TagRepository $tags,
        private LegacyTagPageRepository $legacyTagPages,
        private AssetRepository $assets,
        private DirectoryRepository $directories,
    ) {}

    /**
     * @return array{tag_pages: int, pages: int, tags: int}
     */
    public function __invoke(): array
    {
        $convertedTagPages = $this->convertLegacyTagPages();
        $tags = $this->tags->all();
        $existingTags = $tags->count();
        $migratedPages = 0;

        foreach ($this->pagesIn('') as $page) {
            $ids = collect($page->tags)
                ->map(fn ($reference) => $this->resolveTag((string) $reference, $tags)->id)
                ->unique()
                ->values()
                ->all();

            if ($ids === $page->tags) {
                continue;
            }

            $page->withTags($ids);
            $this->pages->save($page);
            $migratedPages++;
        }

        return ['tag_pages' => $convertedTagPages, 'pages' => $migratedPages, 'tags' => $tags->count() - $existingTags];
    }

    /**
     * @param  Collection<int, Tag>  $tags
     */
    private function resolveTag(string $reference, Collection $tags): Tag
    {
        $tag = $tags->first(fn (Tag $tag) => $tag->id === $reference)
            ?? $tags->first(fn (Tag $tag) => $tag->hasName($reference));

        if ($tag !== null) {
            return $tag;
        }

        $tag = Tag::create($this->tags->nextId($reference), $reference);
        $this->tags->save($tag);
        $tags->push($tag);

        return $tag;
    }

    /**
     * @return Collection<int, Page>
     */
    private function pagesIn(string $directory): Collection
    {
        return $this->directories->searchByDirectory($directory)
            ->flatMap(fn (Directory $subdirectory) => $this->pagesIn($subdirectory->path))
            ->merge($this->pages->searchByDirectory($directory));
    }

    private function convertLegacyTagPages(): int
    {
        $legacyTagPages = $this->legacyTagPages->all();

        foreach ($legacyTagPages as $path => $legacyTag) {
            $tag = $this->tags->find($legacyTag->id);

            if ($tag === null) {
                $tag = $legacyTag;
            } else {
                if (trim((string) $tag->content) === '') {
                    $tag->setContent($legacyTag->content);
                }

                if ($tag->cover_image === null && $legacyTag->cover_image !== null) {
                    $tag->changeCoverImage($legacyTag->cover_image);
                }
            }

            $this->tags->save($tag);

            if ($path !== (string) $tag->assetsPath()) {
                $this->assets->moveDirectory(new PagePath($path), $tag->assetsPath());
            }

            $this->legacyTagPages->delete($path);

            if (Str::contains($path, '/')) {
                $this->directories->deleteIfEmpty(Str::beforeLast($path, '/'));
            }
        }

        return $legacyTagPages->count();
    }
}
