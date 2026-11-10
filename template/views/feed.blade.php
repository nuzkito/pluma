{!! '<'.'?xml version="1.0" encoding="UTF-8"?'.'>' !!}
@php
    $baseUrl = (string) $web->url;
    $absoluteUrls = fn (string $html) => preg_replace('#\b(href|src)="/(?!/)#', '$1="'.$baseUrl.'/', $html);
@endphp
<rss version="2.0" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:atom="http://www.w3.org/2005/Atom">
<channel>
<title>{{ $web->title }}</title>
<link>{{ $baseUrl }}/</link>
<atom:link href="{{ $web->url->append('feed.xml') }}" rel="self" type="application/rss+xml" />
<description>{{ $web->description }}</description>
<language>{{ $web->language }}</language>
@foreach ($pages as $page)
<item>
<title>{{ $page->title }}</title>
<link>{{ $web->url->append($page->path) }}/</link>
<description>{{ $absoluteUrls($page->content->html()) }}</description>
<pubDate>{{ $page->published_at->toRssString() }}</pubDate>
@if($web->author)
<dc:creator>{{ $web->author }}</dc:creator>
@endif
<guid>{{ $web->url->append($page->path) }}/</guid>
</item>
@endforeach
</channel>
</rss>
