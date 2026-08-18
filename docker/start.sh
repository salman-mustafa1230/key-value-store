#!/bin/sh
set -e

cd /var/www/html

# Rebuild package manifest without require-dev (Pail, Collision, …).
php artisan package:discover --ansi --no-interaction

php artisan l5-swagger:generate --no-interaction

# Railway (and local Docker) inject env at runtime. Do not cache config at image build.
php artisan migrate --force --no-interaction

exec php artisan serve --host=0.0.0.0 --port="${PORT:-8000}"
