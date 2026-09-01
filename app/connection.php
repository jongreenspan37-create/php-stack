<?php
//Creates a database connection keeping value secret. Not a pool only one connection

function get_connection(?string $dbname = null): PDO
{
    $host = getenv('DB_HOST');
    $port = getenv('DB_PORT') ?: '3306';
    $dbname = $dbname ?: getenv('MYSQL_DATABASE');
    $user = getenv('MYSQL_USER');
    $password = getenv('MYSQL_PASSWORD');

    $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";

    // CRITICAL SECURITY & STABILITY OPTIONS
    $options = [
        // 1. Force PDO to throw exceptions on database errors instead of silently failing
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,

        // 2. Fetch results as associative arrays by default
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,

        // 3. CRITICAL: Disable emulated prepared statements. 
        // This forces PHP to use real, native MySQL prepared statements, 
        // completely neutralizing advanced SQL injection bypass vectors.
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    return new PDO($dsn, $user, $password, $options);
}
