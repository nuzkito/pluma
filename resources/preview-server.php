<?php

/**
 * Router script for the preview server (php -S).
 *
 * Without it, the built-in server falls back to a parent index.html when the
 * requested path does not exist, so every missing page would render the home
 * page instead of the generated 404 page.
 */
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
