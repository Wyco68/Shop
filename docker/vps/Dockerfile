# Production image for self-hosted VPS deployment (docker-compose.vps.yml).
# Builds frontend assets and PHP vendor into the image — no composer/npm at container start.
# syntax=docker/dockerfile:1

FROM node:22-bookworm-slim AS frontend

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY vite.config.js ./
COPY resources ./resources
COPY public ./public

ARG VITE_REVERB_APP_KEY=
ARG VITE_REVERB_HOST=
ARG VITE_REVERB_PORT=443
ARG VITE_REVERB_SCHEME=https
ARG VITE_APP_NAME=E-Commerce

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
    curl \
    libzip-dev \
    libpng-dev \
    libonig-dev \
    && docker-php-ext-install -j$(nproc) \
        bcmath \
        mbstring \
        opcache \
        pcntl \
        pdo_mysql \
        zip \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && rm -rf /var/lib/apt/lists/*

COPY docker/vps/opcache.ini /usr/local/etc/php/conf.d/zz-opcache.ini
COPY docker/vps/uploads.ini /usr/local/etc/php/conf.d/zz-uploads.ini

COPY --from=vendor /app /var/www/html
COPY --from=frontend /app/public/build /var/www/html/public/build

WORKDIR /var/www/html

RUN mkdir -p database storage/framework/{cache/data,sessions,views} storage/logs storage/app/public storage/app/private bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache database

COPY docker/vps/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENV PORT=8080 \
    LOG_CHANNEL=stderr

EXPOSE 8080

ENTRYPOINT ["entrypoint.sh"]
CMD ["serve"]
