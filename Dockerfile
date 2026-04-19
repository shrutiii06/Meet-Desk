FROM php:8.2-apache

RUN pecl install mongodb && docker-php-ext-enable mongodb

RUN a2enmod rewrite

COPY . /var/www/html/

RUN chown -R www-data:www-data /var/www/html/

EXPOSE 80
