# Composer stage: PHP with Composer, the tools it needs to fetch packages and
# the extensions composer.json requires (gd), so the platform check passes.
# The build below uses it, and so does the `composer` service in
# docker-compose.yml, which installs into the mounted repo.
FROM php:8.4-cli-alpine AS composer-cli

RUN apk add --no-cache git unzip libpng-dev libjpeg-turbo-dev freetype-dev \
    && docker-php-ext-configure gd --with-jpeg --with-freetype \
    && docker-php-ext-install gd

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Composer keeps its cache and config under $COMPOSER_HOME; the default ($HOME)
# is not writable when the container runs as the host user. Create it upfront so
# a volume mounted there inherits the ownership instead of belonging to root.
ENV COMPOSER_HOME=/tmp/composer

RUN mkdir -p /tmp/composer && chown 1000:1000 /tmp/composer

WORKDIR /pluma

ENTRYPOINT ["composer"]

# Build stage: install dependencies and scaffold the test site with `pluma new`.
FROM composer-cli AS builder

COPY . .

RUN --mount=type=cache,target=/tmp/composer/cache \
    composer install --no-dev --no-interaction --no-progress

WORKDIR /site

RUN php /pluma/bin/pluma new

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
