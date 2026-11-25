# Deploy Kost.in ke Vercel (Container Runtime) dengan FrankenPHP.
# Database: Neon (PostgreSQL + PostGIS + pgRouting). Foto: Neon Object Storage (disk s3).

FROM node:22-bookworm-slim AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY vite.config.js ./
COPY resources ./resources
RUN npm run build

FROM dunglas/frankenphp:1-php8.4-bookworm AS base
RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && install-php-extensions intl zip pdo_pgsql pgsql bcmath \
    && printf "upload_max_filesize=32M\npost_max_size=40M\nmemory_limit=256M\n" > "$PHP_INI_DIR/conf.d/kostin.ini"
WORKDIR /app

FROM base AS dependencies
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-progress --no-scripts --optimize-autoloader --prefer-dist
COPY . .
COPY --from=assets /app/public/build ./public/build
RUN mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/app/private bootstrap/cache \
    && composer dump-autoload --no-dev --classmap-authoritative \
    && php artisan view:cache

FROM base AS runtime
COPY --from=dependencies --chown=www-data:www-data /app /app
COPY --chown=www-data:www-data Caddyfile /etc/frankenphp/Caddyfile
RUN setcap CAP_NET_BIND_SERVICE=+eip /usr/local/bin/frankenphp \
    && chown -R www-data:www-data /config/caddy /data/caddy
ENV PORT=80 \
    APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    SESSION_DRIVER=database \
    CACHE_STORE=database \
    QUEUE_CONNECTION=sync \
    FILESYSTEM_DISK=local \
    KOSTIN_FOTO_DISK=s3 \
    DB_CONNECTION=pgsql \
    DB_SSLMODE=require
USER www-data
CMD ["frankenphp", "run", "--config", "/etc/frankenphp/Caddyfile"]
