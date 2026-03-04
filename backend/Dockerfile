# Mini Library API - production image
FROM php:8.2-cli-alpine

RUN apk add --no-cache git unzip libzip-dev libpng-dev oniguruma-dev \
    && docker-php-ext-install zip pdo_mysql mbstring exif pcntl bcmath

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY . .

# Install deps (no dev for smaller image)
RUN composer install --no-dev --no-scripts --prefer-dist \
    && composer dump-autoload --optimize

# Generate key at runtime if missing; migrations/seed at runtime via start script
ENV PORT=8000
EXPOSE 8000

CMD ["sh", "-c", "php artisan migrate --force --no-interaction && php artisan config:cache && php artisan serve --host=0.0.0.0 --port=${PORT:-8000}"]
