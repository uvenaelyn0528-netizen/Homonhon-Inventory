FROM php:8.2-apache

# Install Postgres and MySQL drivers
RUN apt-get update && apt-get install -y \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql pdo_mysql mysqli

COPY . /var/www/html/

RUN chown -R www-data:www-data /var/www/html/ \
    && chmod -R 755 /var/www/html/

EXPOSE 80