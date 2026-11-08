<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Uri\Rfc3986\Uri;

/**
 * Rejects requests whose Host header is not a known local host, so a page
 * that rebinds its domain to 127.0.0.1 cannot talk to the editor.
 */
class EnsureLocalHost
{
    private const array LOCAL_HOSTS = ['localhost', '127.0.0.1', '::1'];

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! in_array($this->normalize($request->getHost()), $this->allowedHosts(), true)) {
            abort(403);
        }

        return $next($request);
    }

    /**
     * @return list<string>
     */
    private function allowedHosts(): array
    {
        $configuredHosts = [
            Uri::parse((string) config('pluma.editor_url'))?->getHost(),
            Uri::parse((string) config('pluma.url'))?->getHost(),
            ...explode(',', (string) config('pluma.allowed_hosts')),
        ];

        return array_values(array_filter([
            ...self::LOCAL_HOSTS,
            ...array_map(fn (?string $host): string => $this->normalize((string) $host), $configuredHosts),
        ]));
    }

    private function normalize(string $host): string
    {
        return strtolower(trim($host, " []\t"));
    }
}
