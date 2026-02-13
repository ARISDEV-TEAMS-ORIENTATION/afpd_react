#!/usr/bin/env bash
set -euo pipefail

if [ -z "${APP_KEY:-}" ]; then
  echo "APP_KEY is missing. Set APP_KEY in Render Environment."
  echo "Generate one locally with: php artisan key:generate --show"
  exit 1
fi

mkdir -p storage/app/public storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache

php artisan config:clear
php artisan storage:link || true

if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
  php artisan migrate --force
fi

exec php artisan serve --host=0.0.0.0 --port="${PORT:-10000}"
