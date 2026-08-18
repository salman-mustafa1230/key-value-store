#!/bin/sh
# Optional Railway pre-deploy hook if the Dockerfile start script is not used.
set -e
php artisan migrate --force --no-interaction
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
