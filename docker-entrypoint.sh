#!/bin/bash
set -e

# ใช้ .env.docker แทน .env ปกติ (สำหรับ Docker)
if [ -f /var/www/.env.docker ]; then
    cp /var/www/.env.docker /var/www/.env
fi

# Install dependencies
composer install --optimize-autoloader --quiet

# Clear config cache
php artisan config:clear 2>/dev/null || true

# Create storage link
php artisan storage:link --force 2>/dev/null || true

# Set permissions
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache 2>/dev/null || true

# Start Laravel
exec php artisan serve --host=0.0.0.0 --port=8000
