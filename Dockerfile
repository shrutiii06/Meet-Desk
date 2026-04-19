FROM php:8.2-cli

RUN pecl install mongodb && docker-php-ext-enable mongodb

WORKDIR /app
COPY . /app/

EXPOSE 8080

CMD ["php", "-S", "0.0.0.0:8080", "-t", "/app"]
