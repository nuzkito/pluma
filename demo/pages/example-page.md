---
title: 'Example Page'
path: example-page
cover_image: null
created_at: '2026-08-13T10:00:00+00:00'
rss: true
published_at: '2026-08-13T10:00:00+00:00'
tags:
    - 'First Tag'
---

This page shows how every Markdown element is written and how it looks with the templates of the site. Edit it, delete it or keep it around as a cheat sheet.

## Headings

Write one to six `#` characters before the text. The title of the page is already an `<h1>`, so headings inside the content start at `##`.

### A third level heading

#### A fourth level heading

## Text

Text can be **bold**, *italic*, ***both*** at once or ~~struck through~~. Wrap anything in backticks to get `inline code`, and leave a blank line between paragraphs to start a new one.

Links are written as [a link to the tag page](/tags/first-tag/), and a bare URL such as https://www.w3.org becomes a link on its own.

## Lists

Unordered lists use a dash, and indenting an item nests it:

- An item
- Another item
    - A nested item
    - Another nested item

Ordered lists use numbers:

1. First step
2. Second step
3. Third step

Adding a checkbox to the items turns them into a task list:

- [x] A completed task
- [ ] A pending task

## Quotes

> A blockquote holds a quotation or an aside.
>
> It can span several paragraphs.

## Code

Fence a block with three backticks and write the language right after them:

```php
$page = ContentPage::draft('Example Page', 'example-page');

$repository->save($page);
```

## Images

Upload an image from the editor and it is inserted with its filename:

![example.svg](example.svg)

## Tables

| Element | Syntax | Notes |
| --- | --- | --- |
| Heading | `## Heading` | Six levels are available |
| Bold | `**bold**` | `__bold__` also works |
| Link | `[text](url)` | The URL can be relative or absolute |

## Separators

Three dashes on a line of their own draw a horizontal rule:

---

## Embedded content

With **Enable embedded content** turned on, a URL alone on its own line is replaced by the content it points to, such as a video:

```
https://www.youtube.com/watch?v=dQw4w9WgXcQ
```

Only the domains listed in the settings are embedded; every other URL stays a plain link.
