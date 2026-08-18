#!/bin/sh
set -e

cd /var/www/html

# Rebuild package manifest without require-dev (Pail, Collision, …).
php artisan package:discover --ansi --no-interaction

PORT="${PORT:-8000}"

# Bind before migrate/swagger. Railway healthchecks fail with
# "service unavailable" if nothing is listening on $PORT.
php artisan serve --host=0.0.0.0 --port="${PORT}" &
SERVE_PID=$!

shutdown() {
  kill -TERM "${SERVE_PID}" 2>/dev/null || true
  wait "${SERVE_PID}" 2>/dev/null || true
}

trap shutdown TERM INT

sleep 1
if ! kill -0 "${SERVE_PID}" 2>/dev/null; then
  echo "php artisan serve exited before it could listen on ${PORT}" >&2
  wait "${SERVE_PID}" || true
  exit 1
fi

php artisan migrate --force --no-interaction || echo "migrate failed; /health is still up" >&2
php artisan l5-swagger:generate --no-interaction || true

wait "${SERVE_PID}"
