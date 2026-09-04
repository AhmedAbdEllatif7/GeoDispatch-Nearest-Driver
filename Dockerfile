FROM php:8.4-fpm

WORKDIR /public_html

# Copy composer files first (to leverage Docker caching)
COPY composer.json composer.lock ./

# Update package list, install only required packages (no extra recommended ones),
# add PostgreSQL dev libraries, enable PDO extension for PostgreSQL,
# then clean up apt cache to reduce image size.
RUN apt-get update && apt-get install -y --no-install-recommends unzip libpq-dev \
    && docker-php-ext-install pdo_pgsql \
    && rm -rf /var/lib/apt/lists/*

# Copy Composer binary directly from official Composer image (cleaner & faster)
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY . .

# Install PHP dependencies without asking questions, using distribution packages (faster)
RUN composer install --no-interaction --prefer-dist

EXPOSE 8000

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
