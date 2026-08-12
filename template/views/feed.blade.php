<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0">
<channel>
<title>{{ $web->title }}</title>
<link>{{ $web->url }}</link>
<description>{{ $web->description }}</description>
@foreach ($pages as $page)
<item>
<title>{{ $page->title }}</title>
<link>{{ $web->url->append($page->path) }}/</link>
<description>{{ strip_tags($page->content->html()) }}</description>
<pubDate>{{ $page->published_at->toRssString() }}</pubDate>
</item>
@endforeach
</channel>
</rss>
