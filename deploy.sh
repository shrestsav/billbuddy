#!/bin/bash
set -e

echo "🚀 Starting deployment..."

cd /var/www/billbuddy

# Pull latest code
echo "📥 Pulling latest code..."
git fetch origin main
git reset --hard origin/main

# Backend deployment
echo "🔧 Installing backend dependencies..."
cd /var/www/billbuddy/backend
composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev

echo "🗄️ Running migrations..."
php artisan migrate --force

echo "🧹 Clearing caches..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Frontend deployment
echo "📦 Installing frontend dependencies..."
cd /var/www/billbuddy/frontend
npm ci

echo "🏗️ Building frontend..."
npm run build

echo "✅ Deployment complete!"
