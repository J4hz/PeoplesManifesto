# Production image for Render or any Docker host.
# The same image runs the web server, Horizon and the scheduler with different commands.

FROM node:22-bookworm-slim AS assets
WORKDIR /app
COPY package.json package-lock.json vite.config.js ./
RUN npm ci
COPY resources ./resources
COPY public ./public
RUN npm run build

FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
# Extensions are checked in the final image, which has them installed.
RUN composer install --no-dev --no-interaction --no-scripts --no-autoloader --prefer-dist --ignore-platform-reqs
COPY . .
RUN composer dump-autoload --no-dev --optimize --classmap-authoritative --no-scripts

FROM serversideup/php:8.3-fpm-nginx-bookworm

USER root

# pg_dump must match the server's major version for backups to work.
RUN install-php-extensions gd exif intl bcmath \
    && apt-get update \
    && apt-get install -y --no-install-recommends curl ca-certificates gnupg \
    && install -d /usr/share/postgresql-common/pgdg \
    && curl -fsSL https://www.postgresql.org/media/keys/ACCC4CF8.asc -o /usr/share/postgresql-common/pgdg/apt.postgresql.org.asc \
    && echo "deb [signed-by=/usr/share/postgresql-common/pgdg/apt.postgresql.org.asc] https://apt.postgresql.org/pub/repos/apt bookworm-pgdg main" > /etc/apt/sources.list.d/pgdg.list \
    && apt-get update \
    && apt-get install -y --no-install-recommends postgresql-client-17 \
    && rm -rf /var/lib/apt/lists/*

COPY --chown=www-data:www-data --from=vendor /app /var/www/html
COPY --chown=www-data:www-data --from=assets /app/public/build /var/www/html/public/build

RUN rm -f /var/www/html/public/hot \
    && rm -rf /var/www/html/tests /var/www/html/node_modules \
    && php /var/www/html/artisan package:discover --ansi

USER www-data

# serversideup runs config:cache, route:cache, view:cache and event:cache on start.
# Migrations run once per deploy from the platform, not from every container.
ENV AUTORUN_ENABLED=true \
    AUTORUN_LARAVEL_MIGRATION=false \
    PHP_OPCACHE_ENABLE=1 \
    SSL_MODE=off \
    LOG_CHANNEL=stderr
