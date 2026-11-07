# Pluma

Pluma is a static‑site generator that converts Markdown files into a fully‑static HTML website.

It ships with a built‑in web editor, auto‑save, RSS feed generation, customizable templates, and a live preview during development.

## Features

- Markdown editor with autosave
- RSS feed creation
- Fully customizable HTML templates
- Live preview while you write

## Requirements

- PHP 8.5 or newer

## Installation

Install Pluma globally via Composer:

```bash
composer global require nuzkito/pluma
```

Make sure the Composer global vendor/bin directory is in your system’s PATH, so you can run the pluma command from any terminal.

## Getting started

### Create a new site

Navigate to an empty directory and run:

```bash
pluma new
```

This scaffolds a fresh project, creating directories for content, templates, assets, etc., along with example files.

### Build the site

Once the scaffold is in place, generate the static website:

```bash
pluma generate
```

The output will be placed in a site/ folder.

You can run this command as part of your CI/CD pipeline to build and deploy the site automatically.

### Development server & editor

To edit pages via the web interface, start the local development server:

```bash
pluma serve
```

By default, urls are:
- Editor UI: http://localhost:8000
- Site preview: http://localhost:8001

You can config urls and ports in the `pluma-settings.json` file, or from the settings screen of the editor.

Any changes you make to pages with the editor are automatically rebuilt and reflected in the preview.

## Templates

The templates of your site live in the `views/` directory and are plain [Blade](https://laravel.com/docs/blade) files, so you can edit them freely. Pluma renders one view per kind of output:

| View | Output | Variables |
| --- | --- | --- |
| `index` | `site/index.html` | `$web`, `$pages` |
| `page` | `site/{path}/index.html` | `$web`, `$page`, `$pages` |
| `tag` | `site/{tag path}/index.html` | `$web`, `$tag`, `$pages` |
| `404` | `site/404.html` | `$web`, `$pages` |
| `feed` | `site/feed.xml` | `$web`, `$pages` |

`layout` is not rendered on its own: the other views extend it, and it receives their variables.

### Head tags

Pluma fills the `<head>` of every document with [Laravel Head](https://laravel.com/docs/head). Put the `@head` directive inside the `<head>` of your layout to render the tags:

```blade
<head>
    <meta charset="UTF-8">
    @head
</head>
```

The tags are built from the page, tag or site being rendered:

| Tag | `index` | `page` | `tag` | `404` |
| --- | --- | --- | --- | --- |
| `<title>`, `og:title` | Site title | Page title | Tag name | `Page not found` |
| `description`, `og:description` | Site description | The start of the page content, or the site description | The start of the tag content, or the site description | Site description |
| `og:image`, `twitter:image` | Site cover image | Page cover image, or the site cover image | Tag cover image, or the site cover image | Site cover image |
| `canonical`, `og:url` | Site URL | Page URL | Tag page URL | — |
| `og:type` | `website` | `article` | `website` | `website` |

| `og:image:width`, `og:image:height`, `og:image:type` | The size and type of the image, as the site serves it once optimized. SVG images only get the type | | | |
| JSON-LD | `WebSite` | `Article` and `BreadcrumbList` | `CollectionPage` and `BreadcrumbList` | — |

Every document also gets:

- `og:site_name` and `og:locale`, from the title and the language of the site.
- A Twitter card: `summary_large_image` when there is an image, `summary` when there is not. It includes `twitter:site` and `twitter:creator` when the X accounts are set in the settings.
- `<meta name="author">` when the author is set in the settings. The `Article` and `WebSite` schemas name the author too.
- The favicon and the Apple touch icon, when they are set in the settings.
- The link to the RSS feed, when the site publishes one.

Pages add `article:published_time` and, when they have been revised, `article:modified_time`. Their breadcrumbs start at the site home and include every published page whose path contains theirs: `blog/my-post` lists `blog` when it is a published page. The `404` page is marked as `noindex` and has no structured data.

Empty values are left out of every tag and schema, so the site does not need every setting.

To change or add tags, call `Head` from your views. The views run before the layout, so the calls reach `@head`:

```blade
@php(Head::title($page->title.' - '.$web->title)->meta('keywords', 'laravel, php'))
```

The tags that depend only on the design of the template, such as the viewport, can be written as plain HTML in the layout.

### The `$pages` collection

`$pages` is a collection of pages, and every view receives one. What it holds depends on the view:

| View | Pages it holds |
| --- | --- |
| `index`, `404`, `page` | Every published page |
| `tag` | The published pages with that tag |
| `feed` | The published pages with the RSS feed enabled |

In `page` the collection also includes the page being rendered, so filter it out when you list related or recent posts:

```blade
@foreach ($pages->where('path', '!=', $page->path)->take(5) as $recent)
    <a href="{{ $web->url->append($recent->path) }}/">{{ $recent->title }}</a>
@endforeach
```

### The `$web` object

`$web` holds the data of your site, taken from its settings, and is available in every view:

| Attribute | Type | Description |
| --- | --- | --- |
| `$web->url` | `Url` | The public URL of the site |
| `$web->title` | `string` | The title of the site |
| `$web->description` | `string` | A short summary of the site |
| `$web->cover_image` | `CoverImage` | The cover image of the site, always present |
| `$web->language` | `string` | The language tag of the site content, such as `en` or `es-ES` |
| `$web->author` | `string` | The author of the site, empty when it is not set |
| `$web->favicon` | `CoverImage` | The favicon of the site, always present |
| `$web->apple_touch_icon` | `CoverImage` | The Apple touch icon of the site, always present |
| `$web->twitter_site` | `string` | The X account of the site, with its leading `@`, empty when it is not set |
| `$web->twitter_creator` | `string` | The X account of the author, with its leading `@`, empty when it is not set |

For example, to show the title of your site:

```blade
<h1>{{ $web->title }}</h1>
```

### The `Url` object

`$web->url` is a `Url`. Printing it gives you the URL of the site, always without a trailing slash:

```blade
<a href="{{ $web->url }}">Go to home</a>
```

Use `append()` to build a URL under it. It returns a new `Url`, so you can chain the calls and the original one is left untouched:

```blade
<link rel="stylesheet" href="{{ $web->url->append('styles.css') }}">
<a href="{{ $web->url->append('tags')->append('laravel') }}/">Laravel</a>
```

The slashes between the segments are added for you, and any extra one is removed.

### The `CoverImage` object

`$web->cover_image`, `$web->favicon` and `$web->apple_touch_icon` are always a `CoverImage`, even when the site has no image configured. Ask it with `isDefined()`:

```blade
@if($web->cover_image->isDefined())
    <img src="{{ $web->cover_image->url() }}" alt="{{ $web->cover_image }}">
@endif
```

Printing it gives you the filename of the image, and `url()` gives you its full URL on the site, already encoded. When there is no cover image, both are empty.

### The `Tag` object

Each tag is a Markdown file in the `tags/` directory of your site, named after its id, which is what the pages store in their `tags` list. Its name and slug can be changed from the editor without touching the pages tagged with it, and its content is the description of the tag.

Sites created with older versions of Pluma reference their tags by name. Run `pluma migrate-tags` once to create the file of each tag and store its id in the pages instead.

The `tag` view receives the tag as `$tag`, and `$page->tags` is a list of them:

| Attribute | Type | Description |
| --- | --- | --- |
| `$tag->name` | `string` | The name of the tag |
| `$tag->slug` | `string` | The last segment of the URL of its page |
| `$tag->path` | `PagePath` | The path of its page, relative to the site root |
| `$tag->content` | `Markdown` | The description of the tag |
| `$tag->cover_image` | `?string` | The filename of its cover image |
| `$tag->hasPage()` | `bool` | Whether the site has a page for the tag |

The site only has tag pages when **Create tag pages** is enabled in the settings, so check `hasPage()` before linking to one:

```blade
@foreach($page->tags as $tag)
    @if($tag->hasPage())
        <a href="{{ $web->url->append($tag->path) }}/">{{ $tag->name }}</a>
    @else
        {{ $tag->name }}
    @endif
@endforeach
```

### Types

Each type is a Markdown file in the `types/` directory of your site, named after its id, which is what a page stores in its `type`. New sites come with a `page` and a `post` type. `$page->type` holds the id, so templates can rely on it:

```blade
@foreach($pages->where('type', 'post') as $post)
    <a href="{{ $web->url->append($post->path) }}/">{{ $post->title }}</a>
@endforeach
```

## Local development with Docker

A `Makefile` wraps the most common commands, so you can use the shortcuts below instead of typing the full `docker compose` ones:

| Shortcut | Runs |
| --- | --- |
| `make install` | Installs the PHP and frontend dependencies |
| `make up` | Starts the development environment |
| `make build` | Builds the assets |
| `make test` | Runs the tests affected by your changes |

The container runs the code from the repository, so install the PHP dependencies first:

```bash
docker compose run --rm composer install
```

This writes `vendor/` into the repository using the same PHP version as the container, so you don't need PHP or Composer installed on the host. Run it again whenever `composer.json` changes.

Do the same for the frontend dependencies:

```bash
docker compose run --rm node install
```

This writes `node_modules/` into the repository, so you don't need Node or npm installed on the host either.

Both installs run together with `make install`.

To try Pluma while developing it, run:

```bash
docker compose up
```

This scaffolds a test site with `pluma new` inside the container, starts `pluma serve` and runs the Vite dev server:

- Editor: http://localhost:8000
- Site preview: http://localhost:8001
- Vite dev server: http://localhost:5173

The repository is bind-mounted into the containers, so code changes are picked up on the next request, and changes to the CSS and JS under `resources/` are rebuilt on the fly by `npm run dev`.

The test site lives only inside the container: it survives `docker compose stop`/`start` and is reset to a fresh scaffold when the container is recreated (e.g. after `--build` or `--force-recreate`).

### Building the assets

The dev server keeps the compiled assets in memory, so `public/build/` is not updated while it runs. Since those files are committed to the repository, build them before committing frontend changes:

```bash
docker compose run --rm node run build
```

Or simply `make build`.

### Running the tests

The suite runs with Pest, inside the same container as everything else:

```bash
docker compose run --rm --entrypoint php composer vendor/bin/pest --tia --parallel
```

Or simply `make test`.

`--tia` enables Pest's test impact analysis: it runs only the tests affected by the changes since the last run and replays the rest from cache. The cache lives in a Docker volume, so it is kept between runs and survives rebuilding the images. `docker compose down -v` removes it, and the next run is a full one that records the dependency graph again.
