<?php

namespace App\Domain\Generator;

use App\Domain\Generator\CoverImage\CoverImage;
use App\Domain\Generator\Page\Page;
use App\Domain\Generator\Tag\Tag;
use Illuminate\Support\Collection;
use Laravel\Head\Enums\OgType;
use Laravel\Head\Enums\RobotsRule;
use Laravel\Head\Enums\TwitterCard;
use Laravel\Head\HeadManager;
use Laravel\Head\Schema\Breadcrumbs;
use Laravel\Head\Schema\Person;
use Laravel\Head\Schema\SchemaFactory;
use Laravel\Head\Schema\SchemaObject;

class HeadTags
{
    public function __construct(
        private HeadManager $head,
        private SchemaFactory $schemas,
        private AssetProcessor $assetProcessor,
    ) {}

    /**
     * @param  Collection<int, Page>  $publishedPages
     */
    public function forIndex(Web $web, Collection $publishedPages): void
    {
        $image = $this->siteCoverImage($web);

        $this->forSite($web, $publishedPages)
            ->title($web->title)
            ->description($web->description)
            ->url($web->url)
            ->image($image, $web->title);

        $this->head->schema($this->filled($this->schemas->webSite(), [
            'name' => $web->title,
            'url' => "{$web->url}/",
            'description' => $web->description,
            'inLanguage' => $web->language,
            'image' => $image?->url,
            'author' => $this->author($web),
        ]));
    }

    /**
     * @param  Collection<int, Page>  $publishedPages
     */
    public function forPage(Web $web, Page $page, Collection $publishedPages): void
    {
        $url = $web->url->append($page->path);
        $description = $page->content->excerpt() ?: $web->description;
        $image = $page->cover_image
            ? new HeadImage(
                $url->append(rawurlencode($page->cover_image)),
                SiteGenerator::ASSETS_DIRECTORY."/{$page->path}/{$page->cover_image}",
            )
            : $this->siteCoverImage($web);

        $this->forSite($web, $publishedPages)
            ->title($page->title)
            ->description($description)
            ->url($url)
            ->image($image, $page->title);

        $this->head->og(type: OgType::Article);

        if ($page->published_at) {
            $this->head->meta('article:published_time', $page->published_at->toAtomString());
        }

        if ($page->revised_at) {
            $this->head->meta('article:modified_time', $page->revised_at->toAtomString());
        }

        $this->head->schema($this->filled($this->schemas->article(), [
            'headline' => $page->title,
            'description' => $description,
            'url' => "{$url}/",
            'mainEntityOfPage' => "{$url}/",
            'image' => $image?->url,
            'datePublished' => $page->published_at,
            'dateModified' => $page->revised_at,
            'inLanguage' => $web->language,
            'author' => $this->author($web),
        ]));

        $this->head->schema($this->breadcrumbs($web, $this->ancestors($page, $publishedPages), $page->title, $url));
    }

    /**
     * @param  Collection<int, Page>  $publishedPages
     */
    public function forTag(Web $web, Tag $tag, Collection $publishedPages): void
    {
        $url = $web->url->append($tag->path);
        $description = $tag->content->excerpt() ?: $web->description;
        $image = $tag->cover_image
            ? new HeadImage(
                $url->append(rawurlencode($tag->cover_image)),
                SiteGenerator::ASSETS_DIRECTORY."/{$tag->assetsPath()}/{$tag->cover_image}",
            )
            : $this->siteCoverImage($web);

        $this->forSite($web, $publishedPages)
            ->title($tag->name)
            ->description($description)
            ->url($url)
            ->image($image, $tag->name);

        $this->head->schema($this->filled($this->schemas->collectionPage(), [
            'name' => $tag->name,
            'description' => $description,
            'url' => "{$url}/",
            'image' => $image?->url,
            'inLanguage' => $web->language,
        ]));

        $this->head->schema($this->breadcrumbs($web, [], $tag->name, $url));
    }

    /**
     * @param  Collection<int, Page>  $publishedPages
     */
    public function forNotFound(Web $web, Collection $publishedPages): void
    {
        $this->forSite($web, $publishedPages)
            ->title('Page not found')
            ->description($web->description)
            ->image($this->siteCoverImage($web), $web->title);

        $this->head->robots(RobotsRule::NoIndex);
    }

    /**
     * @param  Collection<int, Page>  $publishedPages
     */
    private function forSite(Web $web, Collection $publishedPages): self
    {
        $this->head->flush();

        $this->head
            ->og(
                type: OgType::Website,
                siteName: $web->title ?: null,
                locale: str_replace('-', '_', $web->language),
            )
            ->twitter(
                site: $web->twitter_site ?: null,
                creator: $web->twitter_creator ?: null,
            );

        if ($web->author !== '') {
            $this->head->meta('author', $web->author);
        }

        if ($this->hasFeed($publishedPages)) {
            $this->head->feed((string) $web->url->append('feed.xml'), title: $web->title ?: 'RSS Feed');
        }

        $this->icons($web);

        return $this;
    }

    private function icons(Web $web): void
    {
        if ($favicon = $this->siteImage($web->favicon)) {
            $details = $this->assetProcessor->imageDetails($favicon->source);

            $this->head->icon(
                (string) $favicon->url,
                type: $details?->type,
                sizes: $details?->type === 'image/png' ? $details->size() : null,
            );
        }

        if ($appleTouchIcon = $this->siteImage($web->apple_touch_icon)) {
            $this->head->appleTouchIcon(
                (string) $appleTouchIcon->url,
                sizes: $this->assetProcessor->imageDetails($appleTouchIcon->source)?->size(),
            );
        }
    }

    private function title(string $title): self
    {
        if ($title !== '') {
            $this->head->title($title);
        }

        return $this;
    }

    private function description(string $description): self
    {
        if ($description !== '') {
            $this->head->description($description);
        }

        return $this;
    }

    private function url(Url $url): self
    {
        $documentUrl = "{$url}/";

        $this->head
            ->canonical($documentUrl, forceHttps: false, trailingSlash: true)
            ->og(url: $documentUrl);

        return $this;
    }

    private function image(?HeadImage $image, string $alt): self
    {
        if ($image === null) {
            $this->head->twitter(card: TwitterCard::Summary);

            return $this;
        }

        $details = $this->assetProcessor->imageDetails($image->source);

        $this->head
            ->ogImage(
                (string) $image->url,
                alt: $alt !== '' ? $alt : null,
                width: $details?->width,
                height: $details?->height,
                type: $details?->type,
            )
            ->twitter(card: TwitterCard::SummaryWithLargeImage);

        return $this;
    }

    private function siteCoverImage(Web $web): ?HeadImage
    {
        return $this->siteImage($web->cover_image);
    }

    private function siteImage(CoverImage $image): ?HeadImage
    {
        if (! $image->isDefined()) {
            return null;
        }

        return new HeadImage($image->url(), SiteGenerator::ASSETS_DIRECTORY."/{$image}");
    }

    private function author(Web $web): ?Person
    {
        return $web->author === '' ? null : $this->schemas->person()->name($web->author);
    }

    /**
     * @param  Collection<int, Page>  $publishedPages
     * @return list<Page>
     */
    private function ancestors(Page $page, Collection $publishedPages): array
    {
        $segments = explode('/', trim((string) $page->path, '/'));
        $ancestors = [];

        for ($length = 1; $length < count($segments); $length++) {
            $path = implode('/', array_slice($segments, 0, $length));
            $ancestor = $publishedPages->first(fn (Page $publishedPage) => trim((string) $publishedPage->path, '/') === $path);

            if ($ancestor !== null && $ancestor->title !== '') {
                $ancestors[] = $ancestor;
            }
        }

        return $ancestors;
    }

    /**
     * @param  list<Page>  $ancestors
     */
    private function breadcrumbs(Web $web, array $ancestors, string $name, Url $url): Breadcrumbs
    {
        $breadcrumbs = $this->schemas->breadcrumbs()->item($web->title ?: 'Home', "{$web->url}/");

        foreach ($ancestors as $ancestor) {
            $breadcrumbs->item($ancestor->title, "{$web->url->append($ancestor->path)}/");
        }

        return $breadcrumbs->item($name !== '' ? $name : (string) $url, "{$url}/");
    }

    /**
     * @template TSchema of SchemaObject
     *
     * @param  TSchema  $schema
     * @param  array<string, mixed>  $properties
     * @return TSchema
     */
    private function filled(SchemaObject $schema, array $properties): SchemaObject
    {
        foreach ($properties as $property => $value) {
            if ($value !== null && $value !== '') {
                $schema->set($property, $value instanceof Url ? "{$value}" : $value);
            }
        }

        return $schema;
    }

    /**
     * @param  Collection<int, Page>  $publishedPages
     */
    private function hasFeed(Collection $publishedPages): bool
    {
        return config('pluma.rss.enabled') && $publishedPages->contains(fn (Page $page) => $page->rss);
    }
}
