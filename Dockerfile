# Node stage: used only by the `node` service in docker-compose.yml, which
# installs node_modules into the mounted repo and runs the Vite dev server.
FROM alpine:3.23 AS node-cli

RUN apk add --no-cache nodejs npm

# npm's default cache lives in $HOME, which is not writable when the container
# runs as the host user. Create it upfront so the volume mounted there inherits
# the ownership instead of belonging to root.
ENV npm_config_cache=/tmp/npm

RUN mkdir -p /tmp/npm && chown 1000:1000 /tmp/npm

WORKDIR /pluma

ENTRYPOINT ["npm"]

# Composer stage: PHP with Composer, the tools it needs to fetch packages and
# the extensions composer.json requires (gd), so the platform check passes.
# The build below uses it, and so does the `composer` service in
# docker-compose.yml, which installs into the mounted repo.
FROM php:8.4-cli-alpine AS composer-cli

RUN apk add --no-cache git unzip libpng-dev libjpeg-turbo-dev freetype-dev \
    && docker-php-ext-configure gd --with-jpeg --with-freetype \
    && docker-php-ext-install gd

# Pest's test impact analysis records which source lines each test covers, so it
# needs a coverage driver. Without one it silently records nothing and every run
# re-runs the whole suite. pcov is used instead of Xdebug because it only does
# coverage, which is all this needs, and is much faster at it.
RUN apk add --no-cache --virtual .pcov-build-deps $PHPIZE_DEPS \
    && pecl install pcov \
    && docker-php-ext-enable pcov \
    && apk del .pcov-build-deps

# Short tags break any template starting with an XML declaration.
RUN printf 'short_open_tag = Off\n' > /usr/local/etc/php/conf.d/99-short-open-tag.ini

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Composer keeps its cache and config under $COMPOSER_HOME; the default ($HOME)
# is not writable when the container runs as the host user. Create it upfront so
# a volume mounted there inherits the ownership instead of belonging to root.
ENV COMPOSER_HOME=/tmp/composer

RUN mkdir -p /tmp/composer && chown 1000:1000 /tmp/composer

# Pest keeps the test impact analysis graph under $HOME, which defaults to the
# unwritable `/` when the container runs as the host user. Point it somewhere
# writable so a volume mounted there keeps the graph between runs; without it
# every `--rm` run starts cold and re-runs the whole suite.
ENV HOME=/tmp/pest

RUN mkdir -p /tmp/pest && chown 1000:1000 /tmp/pest

WORKDIR /pluma

ENTRYPOINT ["composer"]

# Build stage: install dependencies and scaffold the test site with `pluma new`,
# filled with the example content from demo/.
FROM composer-cli AS builder

COPY . .

RUN --mount=type=cache,target=/tmp/composer/cache \
    composer install --no-dev --no-interaction --no-progress

WORKDIR /site

RUN php /pluma/bin/pluma new

# The scaffold has no pages and /site is built from scratch every time the
# container is recreated, so the image ships an example site: a page with
# Markdown samples, a tag and the settings the demo shows off already on.
RUN cp -a /pluma/demo/. /site/

# Final stage: only PHP and the scaffolded site. The application code,
# vendor/, .env and public/build come from the bind mount at runtime.
# Alpine's php84 packages with just the extensions Laravel needs (plus gd,
# required to optimize image assets at generation time) are still much
# smaller than the official monolithic PHP build.
FROM alpine:3.22

RUN apk add --no-cache \
        php84 \
        php84-ctype \
        php84-curl \
        php84-dom \
        php84-fileinfo \
        php84-gd \
        php84-iconv \
        php84-mbstring \
        php84-openssl \
        php84-session \
        php84-simplexml \
        php84-tokenizer \
        php84-xml \
        php84-xmlreader \
        php84-xmlwriter \
    && ln -s /usr/bin/php84 /usr/bin/php \
    # PHP discards bigger uploads before the application sees them, so the limit
    # must match the one Livewire enforces on temporary uploads (12 MB), plus
    # some room in post_max_size for the rest of the multipart request.
    && printf 'upload_max_filesize = 12M\npost_max_size = 13M\n' > /etc/php84/conf.d/99-uploads.ini

COPY --from=builder --chown=1000:1000 /site /site

WORKDIR /site

EXPOSE 8000 8001

CMD ["php", "/pluma/bin/pluma", "serve", "--host=0.0.0.0"]
