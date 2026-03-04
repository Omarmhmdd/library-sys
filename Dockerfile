# Deploy backend from repo root (so Railway doesn't need Root Directory setting)
FROM php:8.2-cli-alpine

RUN apk add --no-cache git unzip libzip-dev libpng-dev oniguruma-dev \
    && docker-php-ext-install zip pdo_mysql mbstring exif pcntl bcmath

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY backend /app

RUN composer install --no-dev --no-scripts --prefer-dist \
    && composer dump-autoload --optimize

ENV PORT=8000
EXPOSE 8000
CMD ["sh", "-c", "php artisan migrate --force --no-interaction && php artisan config:cache && php artisan serve --host=0.0.0.0 --port=${PORT:-8000}"]
