@extends('layout')

@section('title', $page->title)

@section('content')
<article>
    <a href="{{ $baseUrl }}">Go back</a>
    <h1>{{ $page->title }}</h1>
    @if($page->tags)
        <ul>
            @foreach($page->tags as $tag)
                <li>
                    @if(config('pluma.create_tag_pages'))
                        <a href="{{ $baseUrl }}/{{ config('pluma.tag_pages_path') }}/{{ \Illuminate\Support\Str::slug($tag) }}/">{{ $tag }}</a>
                    @else
                        {{ $tag }}
                    @endif
                </li>
            @endforeach
        </ul>
    @endif
    <time datetime="{{ $page->published_at->toDateString() }}">{{ $page->published_at->toDateString() }}</time>
    <div>{!! $page->content->html() !!}</div>
</article>
@endsection
