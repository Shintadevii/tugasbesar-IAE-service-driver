FROM php:8.4-fpm

# Install dependensi sistem + ekstensi PHP yang penting
RUN apt-get update && apt-get install -y \
    git curl zip unzip libpq-dev libonig-dev libxml2-dev supervisor \
    && docker-php-ext-install pdo pdo_mysql pdo_pgsql mbstring sockets bcmath \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# Copy file proyek
COPY . .

# Install dependencies
RUN composer install --no-dev --optimize-autoloader

# Set permission agar Laravel bisa menulis log/cache
RUN mkdir -p bootstrap/cache storage && chown -R www-data:www-data /var/www

# Copy supervisor config untuk menjalankan consumer
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Start supervisor (akan menjalankan php-fpm dan rabbitmq consumer)
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
