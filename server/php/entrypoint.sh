#!/bin/sh
set -e

cd /var/www/client

mkdir -p vendor
chown -R www:www /var/www/client

if [ -f composer.json ]; then
  if [ ! -f vendor/autoload.php ] || [ composer.lock -nt vendor/autoload.php ]; then
    echo "Installing Composer dependencies..."
    su-exec www:www composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
  fi
fi

exec su-exec www:www "$@"