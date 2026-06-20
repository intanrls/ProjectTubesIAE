FROM php:8.3-cli

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    zip \
    libzip-dev \
    && docker-php-ext-install zip

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY . .

RUN composer install
RUN php artisan l5-swagger:generate
RUN mkdir -p /app/database && touch /app/database/database.sqlite

EXPOSE 8000

CMD php artisan serve --host=0.0.0.0 --port=8000