<?php

use App\Domain\Page\PageRepository;
use App\SiteConfigLoader;
use Illuminate\Support\Facades\Storage;
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
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function initializeSite(): PageRepository
{
    Storage::fake('current');
    artisan('pluma:new');
    Storage::disk('current')->makeDirectory('pages');

    $resolver = new SiteConfigLoader(Storage::disk('current'));
    app()->instance(SiteConfigLoader::class, $resolver);

    $repository = new PageRepository;
    app()->instance(PageRepository::class, $repository);

    return $repository;
}
