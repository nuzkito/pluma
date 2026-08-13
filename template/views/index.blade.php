@extends('layout')

@section('title', $web->title)

@section('rss', true)

@section('content')
<header>
    @if($web->cover_image->isDefined())
        <img src="{{ $web->cover_image->url() }}" alt="{{ $web->title }}">
    @endif
    <h1>{{ $web->title }}</h1>
    <p>{{ $web->description }}</p>
</header>
<ul>
    @foreach ($pages as $page)
    <li>
        <a href="{{ $web->url->append($page->path) }}/">{{ $page->title }}</a>
        <time datetime="{{ $page->published_at->toDateString() }}">{{ $page->published_at->toDateString() }}</time>
    </li>
    @endforeach
</ul>
@endsection
