<?php

namespace App\Providers;

use App\Domain\Generator\Page\PageRepository;
use App\Domain\Generator\Page\YoutubeNocookieEmbedAdapter;
use App\Domain\Generator\SiteGenerator;
use App\Domain\Settings\SiteConfigLoader;
use Embed\Embed;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use League\CommonMark\Extension\Embed\Bridge\OscaroteroEmbedAdapter;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singletonIf(
            PageRepository::class,
            fn () => new PageRepository,
        );

        $this->app->singletonIf(
            SiteConfigLoader::class,
            fn () => new SiteConfigLoader(Storage::disk('current')),
        );

        $this->app->singletonIf(
            SiteGenerator::class,
            fn () => new SiteGenerator(
                app(PageRepository::class),
            ),
        );

        $this->app->singletonIf(
            YoutubeNocookieEmbedAdapter::class,
            function () {
                $embed = new Embed;
                $embed->setSettings([
                    'oembed:query_parameters' => [
                        'maxwidth' => 1280,
                        'maxheight' => 720,
                    ],
                ]);

                return new YoutubeNocookieEmbedAdapter(new OscaroteroEmbedAdapter($embed));
            },
        );
    }

    public function boot(): void
    {
        $this->app->make(SiteConfigLoader::class)->load();
    }
}
