@extends('layout')

@section('title', $page->title)

@section('content')
<article>
    <a href="{{ $baseUrl }}">Go back</a>
    <h1>{{ $page->title }}</h1>
    @if($page->tags)
        <ul>
            @foreach($page->tags as $tag)
                <li>{{ $tag }}</li>
            @endforeach
        </ul>
    @endif
    <time datetime="{{ $page->published_at->toDateString() }}">{{ $page->published_at->toDateString() }}</time>
    <div>{!! $page->content->html() !!}</div>
</article>
@endsection
