FROM php:8.4-fpm

# Install dependensi sistem + ekstensi PHP yang penting
RUN apt-get update && apt-get install -y \
    git curl zip unzip libpq-dev libonig-dev libxml2-dev \
    && docker-php-ext-install pdo pdo_mysql mbstring sockets \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# Copy file proyek
COPY . .

# Set permission agar Laravel bisa menulis log/cache
RUN mkdir -p bootstrap/cache storage && chown -R www-data:www-data /var/www

# Install dependensi Laravel
RUN composer install --no-dev --optimize-autoloader

CMD ["php-fpm"]
