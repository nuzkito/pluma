@extends('layout')

@section('title', $tag->title)

@section('rss', true)

@section('content')
<main>
    <a href="{{ $baseUrl }}">Go back</a>
    @if($tag->cover_image)
        <img src="{{ rawurlencode($tag->cover_image) }}" alt="{{ $tag->cover_image }}">
    @endif
    <h1>{{ $tag->title }}</h1>
    @if(trim((string) $tag->content) !== '')
        <div>{!! $tag->content->html() !!}</div>
    @endif
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
