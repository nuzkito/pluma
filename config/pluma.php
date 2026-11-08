<?php

return [
    'editor_url' => 'http://127.0.0.1:8000',
    'url' => 'http://127.0.0.1:8001',
    'allowed_hosts' => env('PLUMA_ALLOWED_HOSTS', ''),
    'title' => '',
    'description' => '',
    'cover_image' => '',
    'language' => 'en',
    'author' => '',
    'favicon' => '',
    'apple_touch_icon' => '',
    'twitter' => [
        'site' => '',
        'creator' => '',
    ],
    'tags' => [
        'create_pages' => false,
        'pages_path' => 'tags',
    ],
    'types' => [
        'default' => '',
    ],
    'rss' => [
        'enabled' => false,
    ],
    'embedding' => [
        'enabled' => false,
        'allowed_domains' => ['youtube.com', 'x.com', 'github.com'],
    ],
    'highlight' => [
        'theme' => 'highlight-light-lite',
    ],
];
