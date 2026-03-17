@extends('layout')

@section('title', 'Page not found')

@section('content')
<main>
    <h1>Page not found</h1>
    <p>The page you are looking for does not exist.</p>
    <a href="{{ $baseUrl }}/">Go to home</a>
</main>
@endsection
