#!/bin/sh
set -e

cd /var/www/html

export LOG_CHANNEL="${LOG_CHANNEL:-stderr}"

echo "=== boot diagnostics (values hidden) ===" >&2
echo "APP_ENV=${APP_ENV:-unset}" >&2
echo "LOG_CHANNEL=${LOG_CHANNEL}" >&2
echo "DB_CONNECTION=${DB_CONNECTION:-unset}" >&2
echo "DB_SSLMODE=${DB_SSLMODE:-unset}" >&2
if [ -n "${APP_KEY:-}" ]; then echo "APP_KEY=set" >&2; else echo "APP_KEY=MISSING — php artisan key:generate --show" >&2; fi
if [ -n "${DB_URL:-}${DATABASE_URL:-}" ]; then echo "DB_URL=set" >&2; else echo "DB_URL=MISSING — set DB_URL to \${{Postgres.DATABASE_URL}}" >&2; fi

php artisan package:discover --ansi --no-interaction

PORT="${PORT:-8000}"
export PHP_CLI_SERVER_WORKERS="${PHP_CLI_SERVER_WORKERS:-8}"

echo "Listening on 0.0.0.0:${PORT} workers=${PHP_CLI_SERVER_WORKERS}" >&2

# php artisan serve is single-process; Railway healthchecks plus a browser
# request starve it and the edge returns 502. The built-in server with workers
# can accept more than one connection.
php -S 0.0.0.0:"${PORT}" -t public docker/router.php &
SERVE_PID=$!

shutdown() {
  kill -TERM "${SERVE_PID}" 2>/dev/null || true
  wait "${SERVE_PID}" 2>/dev/null || true
}

trap shutdown TERM INT

sleep 1
if ! kill -0 "${SERVE_PID}" 2>/dev/null; then
  echo "php -S exited before it could listen on ${PORT}" >&2
  wait "${SERVE_PID}" || true
  exit 1
fi

echo "=== migrate ===" >&2
set +e
php artisan migrate --force --no-interaction -v > /tmp/migrate.log 2>&1
MIGRATE_STATUS=$?
set -e
cat /tmp/migrate.log >&2
if [ "${MIGRATE_STATUS}" -ne 0 ]; then
  echo "=== migrate failed (exit ${MIGRATE_STATUS}). /health stays up; API will 500 until DB is reachable. ===" >&2
fi

php artisan l5-swagger:generate --no-interaction || true

wait "${SERVE_PID}"
