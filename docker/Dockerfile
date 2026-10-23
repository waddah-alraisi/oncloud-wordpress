# syntax=docker/dockerfile:1
#
# THE ONCLOUD WORDPRESS IMAGE (ADR 0052): one build of this repository serves every site. Two targets:
#   php    PHP-FPM with WordPress core, WooCommerce, the allowed plugins, the companion (must-use) and the theme
#   nginx  the same web root's static files, and PHP requests passed to `php:9000`
# The private companion repository is read with the build secret `composer_auth` (a COMPOSER_AUTH JSON).

FROM composer:2 AS composer

FROM php:8.5-fpm-alpine AS build
COPY --from=composer /usr/bin/composer /usr/bin/composer
RUN apk add --no-cache git unzip gettext
WORKDIR /srv/site
COPY composer.json composer.lock* ./
RUN --mount=type=secret,id=composer_auth \
    export COMPOSER_AUTH="$(cat /run/secrets/composer_auth 2>/dev/null || true)"; \
    if [ -f composer.lock ]; then composer install --no-dev --no-interaction --no-progress --prefer-dist; \
    else composer update --no-dev --no-interaction --no-progress --prefer-dist; fi
COPY config ./config
COPY web/index.php web/wp-config.php ./web/
COPY web/app/mu-plugins/oncloud-loader.php ./web/app/mu-plugins/
COPY web/app/mu-plugins/oncloud-distribution ./web/app/mu-plugins/oncloud-distribution
COPY web/app/themes/oncloud ./web/app/themes/oncloud
COPY bin ./bin
COPY VERSION CHANGELOG.md ./
# The companion ships .po files; WordPress reads compiled .mo.
RUN for po in web/app/mu-plugins/oncloud/languages/*.po; do [ -f "$po" ] && msgfmt -o "${po%.po}.mo" "$po"; done; \
    php bin/check.php --installed

FROM php:8.5-fpm-alpine AS php
COPY --from=mlocati/php-extension-installer:2 /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions mysqli gd intl zip exif opcache bcmath redis
COPY docker/php.ini /usr/local/etc/php/conf.d/oncloud.ini
WORKDIR /srv/site
COPY --from=build --chown=www-data:www-data /srv/site /srv/site
RUN mkdir -p web/app/uploads && chown www-data:www-data web/app/uploads
VOLUME /srv/site/web/app/uploads
USER www-data
LABEL org.opencontainers.image.source="https://github.com/waddah-alraisi/oncloud-wordpress"

FROM nginx:1.29-alpine AS nginx
COPY docker/nginx.conf /etc/nginx/conf.d/default.conf
COPY --from=build /srv/site/web /srv/site/web
LABEL org.opencontainers.image.source="https://github.com/waddah-alraisi/oncloud-wordpress"
