#!/usr/bin/env bash
set -e

echo ">>> Deploy started"

cd "${DEPLOY_PATH:-$(pwd)}"

echo ">>> Git pull"
git pull origin prod

echo ">>> Build and start containers"
docker compose build --no-cache
docker compose up -d

echo ">>> Composer install (inside app container)"
docker compose exec -T app composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

echo ">>> NPM build (inside app container)"
docker compose exec -T app sh -c "npm ci && npm run build" || true

echo ">>> Migrations"
docker compose exec -T app php artisan migrate --force

echo ">>> Cache clear"
docker compose exec -T app php artisan config:clear
docker compose exec -T app php artisan cache:clear
docker compose exec -T app php artisan view:clear
docker compose exec -T app php artisan route:clear

echo ">>> Cache rebuild"
docker compose exec -T app php artisan config:cache
docker compose exec -T app php artisan route:cache
# view:cache skipped - Laravel 12 bug: fails to resolve laravel-exceptions-renderer::icons.* when APP_DEBUG=false

echo ">>> Deploy complete"
