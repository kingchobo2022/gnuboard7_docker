FROM php:8.3-fpm-alpine

# 필수 시스템 라이브러리 및 PHP 확장 설치
RUN apk update && apk add --no-cache \
    git \
    curl \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    zip \
    unzip \
    bash \
    nodejs \
    npm \
    oniguruma-dev

# PHP 익스텐션 구성 및 설치
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd zip pdo pdo_mysql mbstring exif bcmath

# Composer 설치
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
