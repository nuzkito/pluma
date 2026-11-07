<?php

namespace App\Domain\Generator;

use App\Domain\Generator\CoverImage\CoverImage;
use App\Domain\Generator\CoverImage\EmptyCoverImage;
use App\Domain\Generator\CoverImage\SiteCoverImage;

class Web
{
    public function __construct(
        public Url $url,
        public string $title,
        public string $description,
        public CoverImage $cover_image,
        public string $language = 'en',
        public string $author = '',
        public CoverImage $favicon = new EmptyCoverImage,
        public CoverImage $apple_touch_icon = new EmptyCoverImage,
        public string $twitter_site = '',
        public string $twitter_creator = '',
    ) {}

    public static function fromConfig(): self
    {
        $url = new Url((string) config('pluma.url'));

        return new self(
            url: $url,
            title: (string) config('pluma.title'),
            description: (string) config('pluma.description'),
            cover_image: self::siteImage((string) config('pluma.cover_image'), $url),
            language: (string) config('pluma.language') ?: 'en',
            author: trim((string) config('pluma.author')),
            favicon: self::siteImage((string) config('pluma.favicon'), $url),
            apple_touch_icon: self::siteImage((string) config('pluma.apple_touch_icon'), $url),
            twitter_site: self::twitterUsername((string) config('pluma.twitter.site')),
            twitter_creator: self::twitterUsername((string) config('pluma.twitter.creator')),
        );
    }

    private static function siteImage(string $filename, Url $url): CoverImage
    {
        return $filename === '' ? new EmptyCoverImage : new SiteCoverImage($filename, $url);
    }

    private static function twitterUsername(string $username): string
    {
        $username = ltrim(trim($username), '@');

        return $username === '' ? '' : "@{$username}";
    }
}
