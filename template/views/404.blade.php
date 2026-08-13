@extends('layout')

@section('title', 'Page not found')

@section('content')
<h1>Page not found</h1>
<p>The page you are looking for does not exist.</p>
<nav>
    <a href="{{ $web->url }}/">Go to home</a>
</nav>
@endsection
