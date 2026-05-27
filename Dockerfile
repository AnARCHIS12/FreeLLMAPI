FROM php:8.3-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends libsqlite3-dev libcurl4-openssl-dev \
    && docker-php-ext-install sqlite3 pdo_sqlite curl \
    && a2enmod rewrite headers expires deflate \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

COPY . /var/www/html/

RUN mkdir -p /var/www/html/data \
    && chown -R www-data:www-data /var/www/html/data

EXPOSE 80
