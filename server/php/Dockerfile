FROM php:8.3-fpm-alpine

RUN addgroup -g 1000 www && \
    adduser -D -s /bin/sh -u 1000 -G www www

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
    unzip \
    fcgi \
    && rm -rf /var/cache/apk/*

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/client

RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install pdo_mysql mysqli mbstring exif pcntl bcmath gd

RUN echo "expose_php = Off" >> /usr/local/etc/php/conf.d/security.ini && \
    echo "display_errors = Off" >> /usr/local/etc/php/conf.d/security.ini && \
    echo "log_errors = On" >> /usr/local/etc/php/conf.d/security.ini && \
    echo "error_log = /var/log/php_errors.log" >> /usr/local/etc/php/conf.d/security.ini && \
    echo "upload_max_filesize = 10M" >> /usr/local/etc/php/conf.d/security.ini && \
    echo "post_max_size = 10M" >> /usr/local/etc/php/conf.d/security.ini && \
    echo "max_execution_time = 30" >> /usr/local/etc/php/conf.d/security.ini && \
    echo "memory_limit = 256M" >> /usr/local/etc/php/conf.d/security.ini && \
    echo "max_input_vars = 1000" >> /usr/local/etc/php/conf.d/security.ini && \
    echo "max_file_uploads = 20" >> /usr/local/etc/php/conf.d/security.ini && \
    echo "session.cookie_httponly = 1" >> /usr/local/etc/php/conf.d/security.ini && \
    echo "session.cookie_secure = 1" >> /usr/local/etc/php/conf.d/security.ini && \
    echo "session.use_strict_mode = 1" >> /usr/local/etc/php/conf.d/security.ini && \
    echo "session.cookie_samesite = Strict" >> /usr/local/etc/php/conf.d/security.ini && \
    echo "allow_url_fopen = Off" >> /usr/local/etc/php/conf.d/security.ini && \
    echo "allow_url_include = Off" >> /usr/local/etc/php/conf.d/security.ini

RUN echo "[www]" > /usr/local/etc/php-fpm.d/security.conf && \
    echo "listen.mode = 0660" >> /usr/local/etc/php-fpm.d/security.conf && \
    echo "pm = dynamic" >> /usr/local/etc/php-fpm.d/security.conf && \
    echo "pm.max_children = 20" >> /usr/local/etc/php-fpm.d/security.conf && \
    echo "pm.start_servers = 2" >> /usr/local/etc/php-fpm.d/security.conf && \
    echo "pm.min_spare_servers = 1" >> /usr/local/etc/php-fpm.d/security.conf && \
    echo "pm.max_spare_servers = 3" >> /usr/local/etc/php-fpm.d/security.conf && \
    echo "pm.max_requests = 500" >> /usr/local/etc/php-fpm.d/security.conf && \
    echo "pm.process_idle_timeout = 30s" >> /usr/local/etc/php-fpm.d/security.conf && \
    echo "request_terminate_timeout = 30s" >> /usr/local/etc/php-fpm.d/security.conf

RUN cat /usr/local/etc/php-fpm.d/security.conf

COPY client/composer.json client/composer.lock* ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

COPY client/ .

RUN chown -R www:www /var/www/client && \
    chmod -R 755 /var/www/client

USER root

RUN mkdir -p /var/www/client/uploads /var/log && \
    chown -R www:www /var/www/client && \
    chmod -R 755 /var/www/client && \
    chmod -R 775 /var/www/client/uploads && \
    chown www:www /var/log/php_errors.log 2>/dev/null || touch /var/log/php_errors.log && chown www:www /var/log/php_errors.log

RUN find /var/www/client -type f -name "*.php" -exec chmod 644 {} \; && \
    find /var/www/client -type d -exec chmod 755 {} \;

RUN echo '#!/bin/sh' > /usr/local/bin/healthcheck.sh && \
    echo 'cgi-fcgi -bind -connect 127.0.0.1:9000' >> /usr/local/bin/healthcheck.sh && \
    chmod +x /usr/local/bin/healthcheck.sh

HEALTHCHECK --interval=30s --timeout=10s --start-period=5s --retries=3 \
    CMD /usr/local/bin/healthcheck.sh || exit 1

EXPOSE 9000

CMD ["php-fpm", "--nodaemonize", "--fpm-config", "/usr/local/etc/php-fpm.conf"]