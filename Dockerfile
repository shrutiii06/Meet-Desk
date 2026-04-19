FROM php:8.2-apache

RUN pecl install mongodb && docker-php-ext-enable mongodb

RUN a2enmod rewrite && \
    a2dismod mpm_event && \
    a2enmod mpm_prefork

COPY . /var/www/html/

RUN chown -R www-data:www-data /var/www/html/

EXPOSE 80
