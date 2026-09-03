<?php
// auth.php -- shared bits for register.php and login.php.
//
// Both pages load this. It holds the HTML escaping helper, the little
// result-page renderer, and the login-throttle helpers, so the two entry
// points stay thin. The `accounts` and `rate_limits` tables it works with
// are created by scripts/create_tables.php, which the container runs on
// startup (see docker-entrypoint.sh).

declare(strict_types=1);

require_once __DIR__ . '/connection.php';

require_once __DIR__ . '/scripts/render.php';


//This allows for current setup and to work with a reverse proxy in production.
//Note it also requires PHP ports to be hidden in docker compose
//Could be made more robust and check it comes from Nginx or Apache but for now this is enough to get the correct IP address 
function client_ip(): string
{
    return $_SERVER['HTTP_X_REAL_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

//This checks if requester is currently locked out from registering or logging in. 
//It returns the number of seconds to wait before trying again, or 0 if not locked out. It uses the rate_limits table to track attempts and lockouts.
function rate_limit_seconds(PDO $conn, string $action, string $remote_ip, string $remote_email = ''): int
{
    $stmt = $conn->prepare(
        'SELECT GREATEST(0, TIMESTAMPDIFF(SECOND, NOW(), locked_until))'
            . ' FROM rate_limits'
            . ' WHERE action = ? AND remote_ip = ? AND remote_email = ?'
            . ' AND locked_until IS NOT NULL AND locked_until > NOW();'
    );
    $stmt->execute([$action, $remote_ip, $remote_email]);
    $secs = $stmt->fetchColumn();
    return $secs === false ? 0 : (int) $secs;
}

const REGISTER_MAX_ATTEMPTS   = 15;
const REGISTER_WINDOW_MINUTES = 60;
const REGISTER_LOCKOUT_MINUTES = 60;



const LOGIN_MAX_ATTEMPTS    = 5;   // failed logins allowed per window
const LOGIN_WINDOW_MINUTES   = 15; // window the failures are counted in
const LOGIN_LOCKOUT_MINUTES  = 15; // how long the lock lasts once tripped

/** Record one attempt for this bucket -- one atomic UPSERT, no read-then-write race. */
function record_attempt(
    PDO $conn,
    string $action,
    string $remote_ip,
    string $remote_email,
    int $max_attempts,
    int $window_minutes,
    int $lockout_minutes
): void {
    $sql = sprintf(
        'INSERT INTO rate_limits (action, remote_ip, remote_email, attempts, window_start)'
            . ' VALUES (?, ?, ?, 1, NOW())'
            . ' ON DUPLICATE KEY UPDATE'
            . '   attempts = IF(window_start < NOW() - INTERVAL %1$d MINUTE, 1, attempts + 1),'
            . '   locked_until = IF(attempts >= %2$d, NOW() + INTERVAL %3$d MINUTE, locked_until),'
            . '   window_start = IF(window_start < NOW() - INTERVAL %1$d MINUTE, NOW(), window_start);',
        $window_minutes,
        $max_attempts,
        $lockout_minutes
    );
    $conn->prepare($sql)->execute([$action, $remote_ip, $remote_email]);
}

/** Drop this bucket's counter -- e.g. after a successful login. */
function clear_attempts(PDO $conn, string $action, string $remote_ip, string $remote_email = ''): void
{
    $stmt = $conn->prepare(
        'DELETE FROM rate_limits WHERE action = ? AND remote_ip = ? AND remote_email = ?;'
    );
    $stmt->execute([$action, $remote_ip, $remote_email]);
}

/** 429 page telling the caller how long to wait, then stop. */
function too_many_attempts(int $seconds): void
{
    http_response_code(429);
    header('Retry-After: ' . $seconds);
    render_page(
        'Too many attempts',
        '<p>Too many attempts. Please wait about '
            . (int) ceil($seconds / 60) . ' minute(s) and try again.</p>'
    );
    exit;
}
