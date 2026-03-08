FROM php:8.4-cli

WORKDIR /app

COPY . .

RUN apt-get update && apt-get install -y git unzip curl \
    && docker-php-ext-install pdo pdo_mysql \
    && curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN composer install --no-dev --optimize-autoloader

RUN npm install
RUN npm run build

CMD php artisan config:clear && php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=$PORT
