<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title')</title>
    <link rel="stylesheet" href="https://www.w3.org/StyleSheets/Core/Chocolate" type="text/css">
    <link rel="stylesheet" href="{{ $web->url->append('styles.css') }}">
    @hasSection('rss')
    <link rel="alternate" type="application/rss+xml" title="RSS Feed" href="{{ $web->url->append('feed.xml') }}">
    @endif
</head>
<body>
    <main>
        @yield('content')
    </main>
    <script src="{{ $web->url->append('scripts.js') }}"></script>
</body>
</html>
