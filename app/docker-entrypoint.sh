#!/bin/sh
# Runs before the CMD (the built-in PHP server). Waits for MySQL to accept
# connections, then runs scripts/create_tables.php so a fresh `docker compose
# up` -- including the first one after a clone, when the mysql volume is empty
# -- comes up with the schema already in place. create_tables() checks
# table_exists() before each CREATE, so running this every start is a no-op
# once the tables exist.
set -e

DB_HOST="${DB_HOST:-mysql}"
DB_PORT="${DB_PORT:-3306}"
max_tries=30
tries=0

echo "[entrypoint] waiting for MySQL at ${DB_HOST}:${DB_PORT}..."
until php -r '
    $h = getenv("DB_HOST") ?: "mysql";
    $p = getenv("DB_PORT") ?: "3306";
    try {
        new PDO("mysql:host=$h;port=$p", getenv("MYSQL_USER"), getenv("MYSQL_PASSWORD"),
                [PDO::ATTR_TIMEOUT => 2]);
        exit(0);
    } catch (Throwable $e) {
        exit(1);
    }
'; do
    tries=$((tries + 1))
    if [ "$tries" -ge "$max_tries" ]; then
        echo "[entrypoint] MySQL still unreachable after ${max_tries} tries; giving up." >&2
        exit 1
    fi
    echo "[entrypoint] not ready yet (${tries}/${max_tries}); retrying in 2s..."
    sleep 2
done

echo "[entrypoint] MySQL is up; ensuring schema exists..."
php /var/www/html/scripts/create_tables.php

echo "[entrypoint] starting: $*"
exec "$@"
