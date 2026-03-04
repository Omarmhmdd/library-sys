# Stage 0: fetch repo (works for public GitHub without git auth)
ARG BRANCH=main
FROM node:20-alpine AS repo
RUN apk add --no-cache curl
WORKDIR /repo
RUN curl -sL "https://github.com/Omarmhmdd/library-sys/archive/refs/heads/${BRANCH}.tar.gz" | tar xz
RUN test -d library-sys-main/frontend || (echo "frontend dir missing in archive" && exit 1)

# Stage 1: build frontend
FROM node:20-alpine AS frontend
WORKDIR /app
COPY --from=repo /repo/library-sys-main/frontend/package*.json ./
RUN npm ci
COPY --from=repo /repo/library-sys-main/frontend ./
ENV VITE_API_BASE=
RUN npm run build
RUN test -f /app/dist/index.html || (echo "frontend build produced no index.html" && exit 1)

# Stage 2: backend + serve frontend from public/spa
FROM php:8.2-cli-alpine
RUN apk add --no-cache git unzip libzip-dev libpng-dev oniguruma-dev \
    && docker-php-ext-install zip pdo_mysql mbstring exif pcntl bcmath
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
WORKDIR /app
COPY . .
RUN composer install --no-dev --no-scripts --prefer-dist \
    && composer dump-autoload --optimize
COPY --from=frontend /app/dist /app/public/spa
RUN test -f /app/public/spa/index.html || (echo "spa index missing after copy" && exit 1)
ENV PORT=8000
EXPOSE 8000
CMD ["sh", "-c", "php artisan migrate --force --no-interaction || true; php artisan config:cache; php artisan serve --host=0.0.0.0 --port=${PORT:-8000}"]
