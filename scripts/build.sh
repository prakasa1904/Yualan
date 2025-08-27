#!/bin/bash

# Install dependencies
npm i
composer install --optimize-autoloader --no-dev

# Build UI
npm run build

# Clear laravel cache
php artisan optimize:clear

# Setup environment
cp .env.example .env
php artisan key:generate

# Setup database and run migrations
php artisan migrate --seed

# link laravel storage (pipe and ignore error and echo if storage already exist)
php artisan storage:link | echo "storage:link alrady exist"

# Create laravel optimized version
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan optimize
