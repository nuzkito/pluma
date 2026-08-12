<?php

use App\Domain\Editor\Page\ContentPage;
use App\Domain\Editor\Page\Markdown;
use App\Domain\Editor\Page\PagePath;
use App\Domain\Editor\Page\PageRepository as EditorPageRepository;
use App\Domain\Editor\Page\TagPage;
use App\Domain\Error;
use App\Domain\Generator\Page\PageRepository as GeneratorPageRepository;
use App\Domain\Ok;
use App\Domain\Settings\SiteConfigLoader;
use Carbon\Carbon;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

use function Pest\Laravel\artisan;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(TestCase::class)->in('Feature');
pest()->extend(TestCase::class)->in('Unit');

/*
|--------------------------------------------------------------------------
| Site Scaffold
|--------------------------------------------------------------------------
|
| Every test under these directories works against a freshly scaffolded site on a faked disk.
| Tests that need a bare disk instead scaffold it themselves and live outside of them.
|
*/

pest()->beforeEach(fn () => initializeSite())->in(
    'Feature/Domain/Editor',
    'Feature/Domain/Generator',
    'Feature/Domain/Settings',
    'Feature/Http',
    'Feature/Livewire',
);

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| Expectations that describe the two things nearly every test asserts on: the outcome of an
| action, and the files it left on the site disk.
|
*/

expect()->extend('toBeOk', function () {
    expect($this->value)->toBeInstanceOf(Ok::class);

    return $this;
});

expect()->extend('toBeError', function (?string $message = null) {
    expect($this->value)->toBeInstanceOf(Error::class);

    if ($message !== null) {
        expect($this->value->unwrapError())->toBe($message);
    }

    return $this;
});

expect()->extend('toExistOnDisk', function () {
    disk()->assertExists($this->value);

    return $this;
});

expect()->extend('toBeMissingFromDisk', function () {
    disk()->assertMissing($this->value);

    return $this;
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Scaffold a new site on a faked disk, and rebind the repositories that hold on to the old one.
 */
function initializeSite(): void
{
    Storage::fake('current');
    artisan('pluma:new');
    Storage::disk('current')->makeDirectory('pages');

    app()->instance(SiteConfigLoader::class, new SiteConfigLoader(Storage::disk('current')));
    app()->instance(EditorPageRepository::class, new EditorPageRepository);
    app()->instance(GeneratorPageRepository::class, new GeneratorPageRepository);
}

function disk(): Filesystem
{
    return Storage::disk('current');
}

function repository(): EditorPageRepository
{
    return app(EditorPageRepository::class);
}

/**
 * Build a draft page and save it to the repository.
 *
 * @param  array<int, string>  $tags
 */
function aPage(
    string $title = 'Test Page',
    ?string $path = null,
    string $content = '',
    ?Carbon $created_at = null,
    ?Carbon $published_at = null,
    bool $rss = false,
    array $tags = [],
    ?string $cover_image = null,
): ContentPage {
    $page = new ContentPage(
        title: $title,
        path: new PagePath($path ?? Str::slug($title)),
        content: new Markdown($content),
        created_at: $created_at ?? Carbon::now(),
        published_at: $published_at,
        rss: $rss,
        tags: $tags,
        cover_image: $cover_image,
    );

    repository()->save($page);

    return $page;
}

/**
 * Build a published page and save it to the repository.
 *
 * @param  array<int, string>  $tags
 */
function aPublishedPage(
    string $title = 'Test Page',
    ?string $path = null,
    string $content = '',
    ?Carbon $created_at = null,
    ?Carbon $published_at = null,
    bool $rss = false,
    array $tags = [],
    ?string $cover_image = null,
): ContentPage {
    return aPage(
        title: $title,
        path: $path,
        content: $content,
        created_at: $created_at,
        published_at: $published_at ?? Carbon::now(),
        rss: $rss,
        tags: $tags,
        cover_image: $cover_image,
    );
}

/**
 * Build a tag page and save it to the repository.
 */
function aTagPage(
    string $title,
    ?string $path = null,
    string $content = '',
    ?Carbon $created_at = null,
    ?string $cover_image = null,
): TagPage {
    $tagPage = new TagPage(
        path: new PagePath($path ?? config('pluma.tags.pages_path').'/'.Str::slug($title)),
        title: $title,
        content: new Markdown($content),
        created_at: $created_at ?? Carbon::now(),
        cover_image: $cover_image,
    );

    repository()->save($tagPage);

    return $tagPage;
}
