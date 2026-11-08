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

$phpExtensions = ['php', 'php3', 'php4', 'php5', 'php6', 'php7', 'php8', 'phtml', 'phar', 'pht', 'phps'];

if (is_file($file)) {
    if (! in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), $phpExtensions, true)) {
        return false;
    }

    header('Content-Type: text/plain; charset=utf-8');
    readfile($file);

    return true;
}

$directoryIndex = rtrim($file, '/').'/index.html';

if (is_dir($file) && is_file($directoryIndex)) {
    header('Content-Type: text/html; charset=utf-8');
    readfile($directoryIndex);

    return true;
}

http_response_code(404);

$notFoundPage = $_SERVER['DOCUMENT_ROOT'].'/404.html';

if (is_file($notFoundPage)) {
    header('Content-Type: text/html; charset=utf-8');
    readfile($notFoundPage);
}
