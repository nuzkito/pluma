<?php

use Illuminate\Filesystem\Filesystem;

/**
 * Runs the preview server script as php -S would, returning the require
 * result (false means the built-in server should serve the request itself)
 * and the output it produced.
 *
 * @return array{0: mixed, 1: string}
 */
function runPreviewServer(string $documentRoot, string $uri): array
{
    $_SERVER['DOCUMENT_ROOT'] = $documentRoot;
    $_SERVER['REQUEST_URI'] = $uri;

    ob_start();
    $result = require base_path('resources/preview-server.php');
    $output = ob_get_clean();

    return [$result, $output];
}

beforeEach(function () {
    $this->documentRoot = sys_get_temp_dir().'/pluma-preview-router-'.uniqid();

    mkdir($this->documentRoot.'/hello-world', 0755, true);
    file_put_contents($this->documentRoot.'/index.html', 'home');
    file_put_contents($this->documentRoot.'/styles.css', 'styles');
    file_put_contents($this->documentRoot.'/hello-world/index.html', 'hello');
    file_put_contents($this->documentRoot.'/404.html', 'page not found');
});

afterEach(function () {
    (new Filesystem)->deleteDirectory($this->documentRoot);

    http_response_code(200);
});

test('lets the server handle existing files', function () {
    [$result] = runPreviewServer($this->documentRoot, '/styles.css');

    expect($result)->toBeFalse();
});

test('lets the server handle directories containing an index', function (string $uri) {
    [$result] = runPreviewServer($this->documentRoot, $uri);

    expect($result)->toBeFalse();
})->with(['/', '/hello-world/', '/hello-world']);

test('renders the 404 page for paths that do not exist', function () {
    [$result, $output] = runPreviewServer($this->documentRoot, '/pagina-inventada');

    expect($result)->not->toBeFalse()
        ->and($output)->toBe('page not found')
        ->and(http_response_code())->toBe(404);
});
