<?php

use Illuminate\Filesystem\Filesystem;

/**
 * Runs the preview server script as php -S would, returning the require
 * result (false means the built-in server should serve the request itself)
 * and the output it produced.
 *
 * @return array{0: mixed, 1: string}
 */
function runPreviewServer(string $documentRoot, string $uri, string $host = 'localhost:8001'): array
{
    $_SERVER['DOCUMENT_ROOT'] = $documentRoot;
    $_SERVER['REQUEST_URI'] = $uri;
    $_SERVER['HTTP_HOST'] = $host;

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

    putenv('PLUMA_ALLOWED_HOSTS');

    http_response_code(200);
});

test('lets the server handle existing files', function () {
    [$result] = runPreviewServer($this->documentRoot, '/styles.css');

    expect($result)->toBeFalse();
});

test('serves the index of directories containing one', function (string $uri, string $index) {
    [$result, $output] = runPreviewServer($this->documentRoot, $uri);

    expect($result)->not->toBeFalse()
        ->and($output)->toBe($index);
})->with([
    ['/', 'home'],
    ['/hello-world/', 'hello'],
    ['/hello-world', 'hello'],
]);

test('serves PHP files as their source instead of executing them', function (string $filename) {
    $source = '<?php echo "EXEC:".(6*7);';
    file_put_contents($this->documentRoot.'/'.$filename, $source);

    [$result, $output] = runPreviewServer($this->documentRoot, '/'.$filename);

    expect($result)->not->toBeFalse()
        ->and($output)->toBe($source);
})->with(['x.php', 'x.PHP', 'x.phtml']);

test('serves the index.html of a directory that also contains an index.php', function () {
    file_put_contents($this->documentRoot.'/hello-world/index.php', '<?php echo "EXEC:".(6*7);');

    [$result, $output] = runPreviewServer($this->documentRoot, '/hello-world/');

    expect($result)->not->toBeFalse()
        ->and($output)->toBe('hello');
});

test('renders the 404 page for paths that do not exist', function () {
    [$result, $output] = runPreviewServer($this->documentRoot, '/pagina-inventada');

    expect($result)->not->toBeFalse()
        ->and($output)->toBe('page not found')
        ->and(http_response_code())->toBe(404);
});

test('forbids requests to an unknown host without serving the file', function (string $uri) {
    [$result, $output] = runPreviewServer($this->documentRoot, $uri, 'attacker.example:8001');

    expect($result)->not->toBeFalse()
        ->and($output)->toBe('')
        ->and(http_response_code())->toBe(403);
})->with(['/styles.css', '/pagina-inventada']);

test('serves requests to local hosts', function (string $host) {
    [$result] = runPreviewServer($this->documentRoot, '/styles.css', $host);

    expect($result)->toBeFalse();
})->with(['localhost', '127.0.0.1:8001', '[::1]:8001']);

test('serves requests to the hosts listed in PLUMA_ALLOWED_HOSTS', function () {
    putenv('PLUMA_ALLOWED_HOSTS=other.test,pluma.test');

    [$result] = runPreviewServer($this->documentRoot, '/styles.css', 'pluma.test:8001');

    expect($result)->toBeFalse();
});
