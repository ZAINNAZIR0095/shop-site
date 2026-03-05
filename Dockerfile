# Use PHP 8.4 CLI as base
FROM php:8.4-cli

# Set working directory
WORKDIR /app

# Copy all project files
COPY . .

# Ensure .env exists
RUN cp .env.example .env

# Install system dependencies, PHP extensions, Node.js
RUN apt-get update && apt-get install -y git unzip curl \
    && docker-php-ext-install pdo pdo_mysql \
    && curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader

# Install frontend dependencies and build Vue frontend
RUN npm install
RUN npm run build

# Set environment variables from Railway (optional override)
# APP_KEY can also be set via Railway Variables to avoid key generation errors

# Start Laravel server with config cache and migrations
CMD php artisan config:cache && php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=8080
