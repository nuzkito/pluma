<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title')</title>
    <link rel="stylesheet" href="{{ $baseUrl }}/styles.css">
    @hasSection('rss')
    <link rel="alternate" type="application/rss+xml" title="RSS Feed" href="{{ $baseUrl }}/feed.xml">
    @endif
</head>
<body>
    @yield('content')
    <script src="{{ $baseUrl }}/scripts.js"></script>
</body>
</html>
