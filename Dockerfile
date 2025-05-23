FROM php:8.2-cli

RUN apt-get update && apt-get install -y \
    libzip-dev \
    libpq-dev \
    unzip \
    git \
    pkg-config \
    && docker-php-ext-configure zip \
    && docker-php-ext-install pdo pdo_pgsql zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

COPY . /var/www/html

RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Gera a migration da tabela sessions
RUN php artisan session:table || true

EXPOSE 8000

CMD php artisan migrate --force && php artisan db:seed --class=DatabaseSeeder && php artisan serve --host=0.0.0.0 --port=8000
