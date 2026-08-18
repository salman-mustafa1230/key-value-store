# Railway free instance: one container, listen on $PORT, migrate on boot.
# Local: docker compose builds this image and talks to the postgres service.
FROM php:8.4-cli-bookworm

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libpq-dev \
        libzip-dev \
    && docker-php-ext-install -j$(nproc) pdo_pgsql pgsql zip opcache \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

COPY . .
RUN rm -f bootstrap/cache/packages.php bootstrap/cache/services.php \
    && composer dump-autoload --optimize --no-dev --no-scripts \
    && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs storage/api-docs bootstrap/cache \
    && chmod -R ug+rwx storage bootstrap/cache \
    && chmod +x docker/start.sh \
    && cp docker/php-errors.ini "$PHP_INI_DIR/conf.d/zz-errors.ini"

ENV APP_ENV=production
ENV LOG_CHANNEL=stderr
ENV SESSION_DRIVER=array
ENV CACHE_STORE=array
ENV QUEUE_CONNECTION=sync

EXPOSE 8000

CMD ["docker/start.sh"]
