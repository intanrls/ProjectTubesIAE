#!/bin/sh

# Salin .env jika belum ada
if [ ! -f .env ]; then
    cp .env.example .env
    php artisan key:generate
fi

# Pastikan APP_URL selalu mengarah ke gateway port 8000 (bukan port internal 3000)
# agar URL asset Swagger ter-generate dengan benar
sed -i 's|APP_URL=http://localhost:3000|APP_URL=http://localhost:8000|g' .env

# Jalankan generator Swagger Laravel untuk memicu penulisan file JSON dokumentasi di awal
php artisan l5-swagger:generate --ansi

# Jalankan PHP-FPM di latar belakang
php-fpm -D

# Jalankan Nginx di depan (foreground)
nginx -g "daemon off;"
