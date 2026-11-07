<!DOCTYPE html>
<html lang="{{ $web->language }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="dark">
    <meta name="theme-color" content="#212121">
    @head

    <link rel="stylesheet" href="https://www.w3.org/StyleSheets/Core/Chocolate" type="text/css">
    <link rel="stylesheet" href="{{ $web->url->append('styles.css') }}">
</head>
<body>
    <main>
        @yield('content')
    </main>
    <script src="{{ $web->url->append('scripts.js') }}"></script>
</body>
</html>
