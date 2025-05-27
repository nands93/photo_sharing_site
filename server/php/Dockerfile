FROM php:8.3-fpm-alpine

WORKDIR /var/www/client

COPY client/ .

RUN chown -R www-data:www-data /var/www/client

EXPOSE 9000

CMD ["php-fpm"]