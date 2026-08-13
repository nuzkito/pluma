@extends('layout')

@section('title', $tag->title)

@section('rss', true)

@section('content')
<nav>
    <a href="{{ $web->url }}">Go back</a>
</nav>
<header>
    @if($tag->cover_image)
        <img src="{{ rawurlencode($tag->cover_image) }}" alt="{{ $tag->cover_image }}">
    @endif
    <h1>{{ $tag->title }}</h1>
    @if(trim((string) $tag->content) !== '')
        <div>{!! $tag->content->html() !!}</div>
    @endif
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
