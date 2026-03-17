<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0">
<channel>
<title>{{ $title }}</title>
<link>{{ $baseUrl }}</link>
<description>{{ $description }}</description>
@foreach ($pages as $page)
<item>
<title>{{ $page->title }}</title>
<link>{{ $baseUrl }}/{{ $page->path }}/</link>
<description>{{ strip_tags($page->content->html()) }}</description>
<pubDate>{{ $page->published_at->toRssString() }}</pubDate>
</item>
@endforeach
</channel>
</rss>
