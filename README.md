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

The container runs the code from the repository, so install the PHP dependencies first:

```bash
docker compose run --rm composer install
```

This writes `vendor/` into the repository using the same PHP version as the container, so you don't need PHP or Composer installed on the host. Run it again whenever `composer.json` changes.

To try Pluma while developing it, run:

```bash
docker compose up --build
```

This scaffolds a test site with `pluma new` inside the container and starts `pluma serve`:

- Editor: http://localhost:8000
- Site preview: http://localhost:8001

The repository is bind-mounted into the container, so code changes are picked up on the next request. Frontend changes require `npm run build` (or a running `npm run dev`) on the host.

The test site lives only inside the container: it survives `docker compose stop`/`start` and is reset to a fresh scaffold when the container is recreated (e.g. after `--build` or `--force-recreate`).
