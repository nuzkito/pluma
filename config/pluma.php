<?php

return [
    'editor_url' => 'http://localhost:8000',
    'url' => 'http://localhost:8001',
    'title' => '',
    'description' => '',
    'tags' => [
        'create_pages' => false,
        'pages_path' => 'tags',
    ],
    'rss' => [
        'enabled' => false,
    ],
    'embedding' => [
        'enabled' => false,
        'allowed_domains' => ['youtube.com', 'x.com', 'github.com'],
    ],
];
