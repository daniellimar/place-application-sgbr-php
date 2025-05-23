FROM php:8.2-cli

RUN apt-get update && apt-get install -y \
    libzip-dev \
    libpq-dev \
    unzip \
    git \
    pkg-config \
    zip \
    && docker-php-ext-configure zip \
    && docker-php-ext-install pdo pdo_pgsql zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

RUN mkdir -p storage bootstrap/cache framework/cache

RUN chown -R www-data:www-data storage bootstrap/cache framework/cache

RUN composer install --no-interaction --prefer-dist --optimize-autoloader

COPY storage/data/city.json /var/www/html/storage/app/private/data/city.json
COPY storage/data/state.json /var/www/html/storage/app/private/data/state.json

RUN php artisan session:table || true

EXPOSE 8000

CMD php artisan migrate --force && php artisan db:seed --class=DatabaseSeeder && php artisan serve --host=0.0.0.0 --port=8000
