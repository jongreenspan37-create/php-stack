FROM php:8.3-cli

RUN apt-get update && apt-get install -y --no-install-recommends \
    nano \
    && docker-php-ext-install pdo_mysql \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

EXPOSE 8080

CMD ["php", "-S", "0.0.0.0:8080", "router.php"]
