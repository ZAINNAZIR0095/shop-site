FROM php:8.4-cli

WORKDIR /app

COPY . .

RUN cp .env.example .env

RUN apt-get update && apt-get install -y git unzip \
    && docker-php-ext-install pdo pdo_mysql

RUN npm install
RUN npm run build

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN composer install --no-dev --optimize-autoloader

CMD php artisan serve --host=0.0.0.0 --port=8080
