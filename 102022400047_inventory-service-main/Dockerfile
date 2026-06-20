FROM php:8.4-apache

# Install dependensi
RUN apt-get update && apt-get install -y libpng-dev libzip-dev zip unzip git \
    && rm -rf /var/lib/apt/lists/*

# Install ekstensi PHP
RUN docker-php-ext-install pdo_mysql gd zip sockets

# Aktifkan rewrite module Apache
RUN a2enmod rewrite

# --- BAGIAN INI YANG PENTING UNTUK MENGATASI 403 ---
# Mengubah DocumentRoot dari /var/www/html menjadi /var/www/html/public
RUN sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/sites-available/000-default.conf
# ----------------------------------------------------

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

# Running composer install to populate vendor directory
RUN composer install --no-interaction --no-dev --optimize-autoloader

# Generate Swagger UI documentation
RUN php artisan l5-swagger:generate

# Buat database sqlite kosong dan migrate+seed agar data awal terisi ke dalam image
RUN mkdir -p /var/www/html/database && touch /var/www/html/database/database.sqlite
RUN php artisan migrate:fresh --seed
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/database

EXPOSE 80