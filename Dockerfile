# Stage 1: build frontend (same URL = API at /api, so no base URL)
FROM node:20-alpine AS frontend
WORKDIR /app
COPY frontend/package*.json ./
RUN npm ci
COPY frontend ./
ENV VITE_API_BASE=
RUN npm run build

# Stage 2: backend + serve frontend from public/spa
FROM php:8.2-cli-alpine
RUN apk add --no-cache git unzip libzip-dev libpng-dev oniguruma-dev \
    && docker-php-ext-install zip pdo_mysql mbstring exif pcntl bcmath
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
WORKDIR /app
COPY backend /app
COPY --from=frontend /app/dist /app/public/spa
RUN composer install --no-dev --no-scripts --prefer-dist \
    && composer dump-autoload --optimize
ENV PORT=8000
EXPOSE 8000
CMD ["sh", "-c", "php artisan migrate --force --no-interaction || true; php artisan config:cache; php artisan serve --host=0.0.0.0 --port=${PORT:-8000}"]
