# Build stage: install dependencies and scaffold the test site with `pluma new`.
FROM php:8.4-cli-alpine AS builder

RUN apk add --no-cache git unzip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /pluma

COPY . .

RUN --mount=type=cache,target=/root/.composer/cache \
    composer install --no-dev --no-interaction --no-progress

WORKDIR /site

RUN php /pluma/bin/pluma new

# Final stage: only PHP and the scaffolded site. The application code,
# vendor/, .env and public/build come from the bind mount at runtime.
# Alpine's php84 packages with just the extensions Laravel needs are much
# smaller than the official monolithic PHP build.
FROM alpine:3.22

RUN apk add --no-cache \
        php84 \
        php84-ctype \
        php84-curl \
        php84-dom \
        php84-fileinfo \
        php84-iconv \
        php84-mbstring \
        php84-openssl \
        php84-session \
        php84-simplexml \
        php84-tokenizer \
        php84-xml \
        php84-xmlreader \
        php84-xmlwriter \
    && ln -s /usr/bin/php84 /usr/bin/php

COPY --from=builder --chown=1000:1000 /site /site

WORKDIR /site

EXPOSE 8000 8001

CMD ["php", "/pluma/bin/pluma", "serve", "--host=0.0.0.0"]
