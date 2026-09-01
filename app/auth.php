<?php
// auth.php -- shared bits for register.php and login.php.
//
// Both pages load this. It holds the account table definition, the HTML
// escaping helper, and the little result-page renderer, so the two entry
// points stay thin.

declare(strict_types=1);

require_once __DIR__ . '/connection.php';

/**
 * Create the accounts table on first use.
 *
 * Named "accounts", NOT "users": scripts/create_tables.php already defines a
 * different `users` table for the roles/users CRUD exercise (role_id FK, a
 * BEFORE INSERT trigger capping it at 5 rows). This table backs the
 * register / login pages and its columns line up 1:1 with register.html --
 * plus password_hash (never the plaintext) and a created_at stamp.
 */
function ensure_accounts_table(PDO $conn): void
{
    $conn->exec(
        'CREATE TABLE IF NOT EXISTS accounts ('
        . ' id INT AUTO_INCREMENT PRIMARY KEY,'
        . ' title VARCHAR(10) NOT NULL,'
        . ' first_name VARCHAR(50) NOT NULL,'
        . ' last_name VARCHAR(50) NOT NULL,'
        . ' email VARCHAR(254) NOT NULL UNIQUE,'
        . ' password_hash VARCHAR(255) NOT NULL,'
        . ' created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP'
        . ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;'
    );
}

/** HTML-escape a value before putting it in a page. */
function esc(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

/** Render a minimal result page using the shared blue-palette CSS. */
function render_page(string $heading, string $body_html): void
{
    echo '<!doctype html><html lang="en"><head>'
        . '<meta charset="UTF-8">'
        . '<meta name="viewport" content="width=device-width, initial-scale=1.0">'
        . '<title>' . esc($heading) . '</title>'
        . '<link rel="stylesheet" href="style.css">'
        . '<link rel="stylesheet" href="register.css">'
        . '</head><body><main class="auth-card">'
        . '<h1>' . esc($heading) . '</h1>'
        . $body_html
        . '</main></body></html>';
}
