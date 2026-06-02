# CarPart — production image for Render (portfolio / demo)
# syntax=docker/dockerfile:1

FROM node:22-bookworm-slim AS frontend

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY vite.config.js ./
COPY resources ./resources
COPY public ./public

# Optional Reverb / Vite env (set in Render before build)
ARG VITE_REVERB_APP_KEY=
ARG VITE_REVERB_HOST=
ARG VITE_REVERB_PORT=443
ARG VITE_REVERB_SCHEME=https
ARG VITE_APP_NAME=CarPart

ENV VITE_REVERB_APP_KEY=$VITE_REVERB_APP_KEY \
    VITE_REVERB_HOST=$VITE_REVERB_HOST \
    VITE_REVERB_PORT=$VITE_REVERB_PORT \
    VITE_REVERB_SCHEME=$VITE_REVERB_SCHEME \
    VITE_APP_NAME=$VITE_APP_NAME

RUN npm run build

FROM composer:2 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-scripts \
    --prefer-dist \
    --optimize-autoloader

COPY . .

RUN composer install \
    --no-dev \
    --no-interaction \
    --optimize-autoloader

FROM php:8.4-cli-bookworm

RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    unzip \
    libsqlite3-dev \
    libzip-dev \
    libonig-dev \
    && docker-php-ext-install -j$(nproc) \
        bcmath \
        mbstring \
        opcache \
        pdo_sqlite \
        zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=vendor /app /var/www/html
COPY --from=frontend /app/public/build /var/www/html/public/build

WORKDIR /var/www/html

RUN mkdir -p database storage/framework/{cache,sessions,views} storage/logs bootstrap/cache \
    && chmod +x docker/render/start.sh \
    && chown -R www-data:www-data storage bootstrap/cache database 2>/dev/null || true

ENV PORT=10000 \
    LOG_CHANNEL=stderr

EXPOSE 10000

CMD ["/var/www/html/docker/render/start.sh"]
