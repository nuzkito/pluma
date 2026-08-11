# Pluma

Pluma is a static‑site generator that converts Markdown files into a fully‑static HTML website.

It ships with a built‑in web editor, auto‑save, RSS feed generation, customizable templates, and a live preview during development.

## Features

- Markdown editor with autosave
- RSS feed creation
- Fully customizable HTML templates
- Live preview while you write

## Requirements

- PHP 8.4 or newer

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

You can config urls and ports in `config.php` file.

Any changes you make to pages with the editor are automatically rebuilt and reflected in the preview.

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
