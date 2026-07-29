@extends('layout')

@section('title', $title)

@section('rss', true)

@section('content')
<main>
    @if($coverImage)
        <img src="{{ rawurlencode($coverImage) }}" alt="{{ $title }}">
    @endif
    <h1>{{ $title }}</h1>
    <p>{{ $description }}</p>
    <ul>
        @foreach ($pages as $page)
        <li>
            <a href="{{ $baseUrl }}/{{ $page->path }}/">{{ $page->title }}</a>
            <time datetime="{{ $page->published_at->toDateString() }}">{{ $page->published_at->toDateString() }}</time>
        </li>
        @endforeach
    </ul>
</main>
@endsection
