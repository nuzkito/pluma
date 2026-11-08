<?php

/**
 * Router script for the preview server (php -S).
 *
 * Without it, the built-in server falls back to a parent index.html when the
 * requested path does not exist, so every missing page would render the home
 * page instead of the generated 404 page.
 *
 * It also rejects requests whose Host header is not a known local host, so a
 * page that rebinds its domain to 127.0.0.1 cannot read the preview. The list
 * mirrors App\Http\Middleware\EnsureLocalHost.
 */
$allowedHosts = array_filter(array_map(
    fn (string $host): string => strtolower(trim($host, " []\t")),
    ['localhost', '127.0.0.1', '::1', ...explode(',', (string) getenv('PLUMA_ALLOWED_HOSTS'))],
));
$requestHost = strtolower($_SERVER['HTTP_HOST'] ?? '');
$requestHost = str_starts_with($requestHost, '[')
    ? substr($requestHost, 1, (int) strpos($requestHost, ']') - 1)
    : explode(':', $requestHost)[0];

if (! in_array($requestHost, $allowedHosts, true)) {
    http_response_code(403);

    return true;
}

$path = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/');
$file = $_SERVER['DOCUMENT_ROOT'].$path;

if (is_file($file)) {
    return false;
}

if (is_dir($file) && is_file(rtrim($file, '/').'/index.html')) {
    return false;
}

http_response_code(404);

$notFoundPage = $_SERVER['DOCUMENT_ROOT'].'/404.html';

if (is_file($notFoundPage)) {
    header('Content-Type: text/html; charset=utf-8');
    readfile($notFoundPage);
}
