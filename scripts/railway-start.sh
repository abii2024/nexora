#!/usr/bin/env bash
# Railway-start script: migrate + seed-if-empty + serve.
# Idempotent op re-deploys — seed alleen als users-tabel leeg is.

set -euo pipefail

echo "→ Caching config for production..."
php artisan config:cache
php artisan route:cache

echo "→ Running migrations..."
php artisan migrate --force

USER_COUNT=$(php artisan tinker --execute "echo \App\Models\User::count();" 2>/dev/null | tail -1 | tr -d '[:space:]')
if [ "$USER_COUNT" = "0" ]; then
  echo "→ Empty users-table detected, running DatabaseSeeder..."
  php artisan db:seed --force --class=DatabaseSeeder
else
  echo "→ Users-table has $USER_COUNT records, skipping seed."
fi

echo "→ Booting Laravel on :$PORT"
exec php artisan serve --host=0.0.0.0 --port="$PORT"
