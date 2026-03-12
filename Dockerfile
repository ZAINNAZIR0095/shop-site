# Use PHP 8.4 CLI as base
FROM php:8.4-cli

# Set working directory
WORKDIR /app

# Copy all project files
COPY . .

# Ensure .env exists
# RUN cp .env.example .env

# Install system dependencies
RUN apt-get update && apt-get install -y git unzip curl \
    && docker-php-ext-install pdo pdo_mysql \
    && curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader

# Install frontend dependencies
RUN npm install

# Build Vue frontend
RUN npm run build

# Start Laravel server with migration + seeder
CMD php artisan config:cache && php artisan migrate --seed --force && php artisan serve --host=0.0.0.0 --port=$PORT
