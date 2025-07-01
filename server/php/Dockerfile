FROM php:8.3-fpm-alpine

RUN apk update && apk add --no-cache \
    libpng-dev \
    oniguruma-dev \
    libxml2-dev \
    freetype-dev \
    libjpeg-turbo-dev \
    libwebp-dev \
    zlib-dev \
    curl \
    git \
    unzip

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/client

RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install pdo_mysql mysqli mbstring exif pcntl bcmath gd

COPY client/composer.json* ./

# Instalar dependências do Composer (se composer.json existir)
RUN if [ -f composer.json ]; then composer install --no-dev --optimize-autoloader --no-scripts; fi

COPY client/ .
COPY .env .env

RUN if [ -f composer.json ]; then composer install --no-dev --optimize-autoloader; fi

RUN chown -R www-data:www-data /var/www/client

EXPOSE 9000

CMD ["php-fpm"]