---
paths:
  - '**'
---

# General

## Run the project commands through Docker
Everything runs in Docker, so don't assume PHP, Composer, Node or npm are available on the host.

- `docker compose run --rm composer install` — install the PHP dependencies into `vendor/`. Run it again whenever `composer.json` changes.
- `docker compose run --rm node install` — install the frontend dependencies into `node_modules/`.
- `docker compose up` — start the development environment: it scaffolds a test site with `pluma new`, runs `pluma serve` and the Vite dev server. Editor on http://localhost:8000, site preview on http://localhost:8001, Vite on http://localhost:5173.
- `docker compose run --rm node run build` — compile the assets into `public/build/`.
- `docker compose run --rm --entrypoint php composer vendor/bin/pest --tia --parallel` — run the tests.

`public/build/` is committed and the Vite dev server keeps the compiled assets in memory, so always build before committing frontend changes.

The test site lives only inside the container: it survives `docker compose stop`/`start`, but is reset when the container is recreated, back to the scaffold plus the example content in `demo/` (a page with Markdown samples, a tag and the settings the demo shows off enabled).
