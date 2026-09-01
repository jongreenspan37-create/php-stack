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

// ---------------------------------------------------------------------------
// Login throttling  (backs the rate_limits table)
// ---------------------------------------------------------------------------
//
// Fixed-window counter, one row per "who is trying which account":
//   identifier    = "login:<client ip>:<email>"
//   attempts      = failed logins seen in the current window
//   window_start  = when that window opened
//   locked_until  = once attempts hits LOGIN_MAX_ATTEMPTS, the wall-clock
//                   time the caller may try again
//
// login_lockout_seconds() is the gate: it runs BEFORE the password check.
// A failure is recorded with login_register_failure(); a success wipes the
// row with login_clear_rate_limit(). Window and lockout are equal so a
// caller can never be re-locked without a fresh batch of failures.

const LOGIN_MAX_ATTEMPTS    = 5;   // failed logins allowed per window
const LOGIN_WINDOW_MINUTES   = 15; // window the failures are counted in
const LOGIN_LOCKOUT_MINUTES  = 15; // how long the lock lasts once tripped

/**
 * Throttle bucket for one login attempt: this client IP against this email.
 *
 * REMOTE_ADDR only -- behind a real proxy you would read a *validated*
 * X-Forwarded-For instead (the raw header is client-controlled). The email
 * is lower-cased and clipped so "login:" + IPv6 (<=45) + ":" + email always
 * fits VARCHAR(255) / the UNIQUE key.
 */
function login_rate_key(string $email): string
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    return 'login:' . $ip . ':' . substr(strtolower($email), 0, 190);
}

/**
 * Seconds still to wait if this identifier is inside a lockout window,
 * else 0. Called before the password is looked at.
 */
function login_lockout_seconds(PDO $conn, string $identifier): int
{
    $stmt = $conn->prepare(
        'SELECT GREATEST(0, TIMESTAMPDIFF(SECOND, NOW(), locked_until))'
        . ' FROM rate_limits'
        . ' WHERE identifier = ? AND locked_until IS NOT NULL AND locked_until > NOW();'
    );
    $stmt->execute([$identifier]);
    $secs = $stmt->fetchColumn();
    return $secs === false ? 0 : (int) $secs;
}

/**
 * Record one failed attempt for this identifier.
 *
 * One atomic UPSERT on UNIQUE(identifier) -- no read-then-write race.
 * MySQL evaluates the ON DUPLICATE KEY UPDATE assignments left to right and
 * a later expression sees columns already assigned earlier in the same
 * statement, so the order matters:
 *   1. attempts     -- reads the OLD window_start: stale window => 1, else +1
 *   2. locked_until  -- reads the NEW attempts: at the limit => stamp a lock
 *   3. window_start  -- reads its own OLD value last: stale => reopen at NOW()
 */
function login_register_failure(PDO $conn, string $identifier): void
{
    $sql = sprintf(
        'INSERT INTO rate_limits (identifier, attempts, window_start)'
        . ' VALUES (?, 1, NOW())'
        . ' ON DUPLICATE KEY UPDATE'
        . '   attempts = IF(window_start < NOW() - INTERVAL %1$d MINUTE, 1, attempts + 1),'
        . '   locked_until = IF(attempts >= %2$d, NOW() + INTERVAL %3$d MINUTE, locked_until),'
        . '   window_start = IF(window_start < NOW() - INTERVAL %1$d MINUTE, NOW(), window_start);',
        LOGIN_WINDOW_MINUTES,
        LOGIN_MAX_ATTEMPTS,
        LOGIN_LOCKOUT_MINUTES
    );
    $conn->prepare($sql)->execute([$identifier]);
}

/** Drop this identifier's failure counter after a successful login. */
function login_clear_rate_limit(PDO $conn, string $identifier): void
{
    $stmt = $conn->prepare('DELETE FROM rate_limits WHERE identifier = ?;');
    $stmt->execute([$identifier]);
}

/** 429 page telling the caller how long to wait, then stop. */
function login_lockout_response(int $seconds): void
{
    http_response_code(429);
    header('Retry-After: ' . $seconds);
    render_page(
        'Too many attempts',
        '<p>Too many failed login attempts. Please wait about '
        . (int) ceil($seconds / 60) . ' minute(s) and try again.</p>'
    );
    exit;
}
