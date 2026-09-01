<?php
// login.php -- verifies the POST from login_form.html and starts a session.
//
// Same $_POST story as register.php (a plain urlencoded form submit).
// FastAPI analogue: a login route taking OAuth2PasswordRequestForm and
// setting a session cookie.

declare(strict_types=1);

require_once __DIR__ . '/../auth.php';

session_start();

// --- 1. Only accept POST -----------------------------------------------
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Method Not Allowed -- submit the form at login_form.html');
}

$email    = trim($_POST['email'] ?? '');
$password = (string) ($_POST['password'] ?? '');

// --- 2. Shape check only ----------------------------------------------
// Anything more specific here leaks which accounts exist.
if ($email === '' || $password === '') {
    login_failed('Enter your email and password.');
}

// --- 3. Refuse early if this IP+email is already locked out ----------
// The throttle guards the `accounts` login: N failures in a window ->
// a timed lock, checked here BEFORE the password is looked at so a
// locked caller costs nothing.
$rate_key = login_rate_key($email);

try {
    $conn = get_connection();
    ensure_accounts_table($conn);
    ensure_rate_limits_table($conn);
} catch (PDOException $e) {
    http_response_code(500);
    error_log('login.php: ' . $e->getMessage());
    render_page('Something went wrong', '<p>Please try again in a moment.</p>');
    exit;
}

$wait = login_lockout_seconds($conn, $rate_key);
if ($wait > 0) {
    login_lockout_response($wait);
}

// --- 4. Look the account up and verify the hash --------------------
try {
    $stmt = $conn->prepare(
        'SELECT id, first_name, last_name, email, password_hash'
        . ' FROM accounts WHERE email = ?;'
    );
    $stmt->execute([$email]);
    $account = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    http_response_code(500);
    error_log('login.php: ' . $e->getMessage());
    render_page('Something went wrong', '<p>Please try again in a moment.</p>');
    exit;
}

// password_verify() is constant-time and re-derives cost/salt from the
// stored string. Unknown email and wrong password give the SAME response
// on purpose -- don't tell an attacker which half was right.
if ($account === false || !password_verify($password, $account['password_hash'])) {
    // Count the failure first. If it's the one that trips the limit,
    // say so; otherwise the generic message.
    login_register_failure($conn, $rate_key);
    $wait = login_lockout_seconds($conn, $rate_key);
    if ($wait > 0) {
        login_lockout_response($wait);
    }
    login_failed('Email or password is incorrect.');
}

// Good credentials -- clear this identifier's failure counter.
login_clear_rate_limit($conn, $rate_key);

// --- 5. Opportunistic rehash if the cost/algorithm moved on ----------
if (password_needs_rehash($account['password_hash'], PASSWORD_DEFAULT, ['cost' => 12])) {
    try {
        $upd = $conn->prepare('UPDATE accounts SET password_hash = ? WHERE id = ?;');
        $upd->execute([
            password_hash($password, PASSWORD_DEFAULT, ['cost' => 12]),
            $account['id'],
        ]);
    } catch (PDOException $e) {
        error_log('login.php rehash: ' . $e->getMessage());
    }
}
unset($password);

// --- 6. New session id on privilege change -> blocks session fixation
session_regenerate_id(true);
$_SESSION['account_id'] = (int) $account['id'];
$_SESSION['email']      = $account['email'];
$_SESSION['name']       = $account['first_name'] . ' ' . $account['last_name'];

// --- 7. Post/Redirect/Get: a refresh of the landing page won't re-post
header('Location: index.html');
exit;


/** Render the login page again with an error, then stop. */
function login_failed(string $message): void
{
    http_response_code(401);
    render_page(
        'Log in',
        '<ul class="errors"><li>' . esc($message) . '</li></ul>'
        . '<p class="form-footer"><a href="login_form.html">Try again</a></p>'
    );
    exit;
}
