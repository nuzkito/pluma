<?php

namespace App\Providers;

use App\Domain\Page\PageRepository;
use App\Domain\Page\SiteGenerator;
use App\SiteConfigLoader;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singletonIf(
            PageRepository::class,
            fn () => new PageRepository,
        );

        $this->app->singletonIf(
            SiteGenerator::class,
            fn () => new SiteGenerator(
                app(PageRepository::class),
            ),
        );
    }

    public function boot(): void
    {
        $loader = new SiteConfigLoader(Storage::disk('current'));
        $loader->load();
    }
}
