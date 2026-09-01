FROM php:8.3-cli

RUN apt-get update && apt-get install -y --no-install-recommends \
    nano \
    && docker-php-ext-install pdo_mysql \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

EXPOSE 8080

# Copied outside /var/www/html so the compose bind mount (./app -> /var/www/html)
# can't shadow it. Waits for MySQL, then runs scripts/create_tables.php before
# handing off to CMD.
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh
ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]

# router.php is the front controller and sits ABOVE the doc root.
# -t www makes the built-in server serve static files only from www/,
# so nothing in ./scripts, connection.php or auth.php is URL-reachable.
CMD ["php", "-S", "0.0.0.0:8080", "-t", "www", "router.php"]
