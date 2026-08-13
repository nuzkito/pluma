@extends('layout')

@section('title', $page->title)

@section('content')
    <nav>
        <a href="{{ $web->url }}">Go back</a>
    </nav>
    <article>
        <header>
            @if($page->cover_image)
                <img src="{{ rawurlencode($page->cover_image) }}" alt="{{ $page->cover_image }}">
            @endif
            <h1>{{ $page->title }}</h1>
            <time datetime="{{ $page->published_at->toDateString() }}">{{ $page->published_at->toDateString() }}</time>
            @if($page->tags)
                <ul>
                    @foreach($page->tags as $tag)
                        <li>
                            @if(config('pluma.tags.create_pages'))
                                <a href="{{ $web->url->append(config('pluma.tags.pages_path'))->append(\Illuminate\Support\Str::slug($tag)) }}/">{{ $tag }}</a>
                            @else
                                {{ $tag }}
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </header>
        <div>{!! $page->content->html() !!}</div>
    </article>
@endsection
