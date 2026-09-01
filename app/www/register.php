<?php
// register.php -- handles the standard POST from register.html.
//
// The /api/run/* endpoints read a JSON body from php://input (see router.php).
// This one is different: it's a plain browser form submit, so the body is
// application/x-www-form-urlencoded and PHP parses it for us -- every field
// shows up in the $_POST superglobal, keyed by the <input name="...">.
//
// FastAPI analogue:
//   async def register(
//       title: str = Form(...), first_name: str = Form(...),
//       last_name: str = Form(...), email: str = Form(...),
//       password: str = Form(...), website: str = Form(""),  # honeypot
//   ): ...

declare(strict_types=1);

require_once __DIR__ . '/../auth.php';

// --- 1. Only accept POST ---------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Method Not Allowed -- submit the form at register.html');
}

// --- 2. Honeypot check ---------------------------------------------------
// .hp-field is off screen for humans. If "website" comes back with anything
// in it, treat the sender as a bot: return a normal-looking 200 (so the bot
// can't tell it was filtered) but write nothing.
if (trim($_POST['website'] ?? '') !== '') {
    error_log('register.php: honeypot tripped from ' . ($_SERVER['REMOTE_ADDR'] ?? '?'));
    render_page('Thanks!', '<p>Your registration has been received.</p>');
    exit;
}

// --- 3. Collect + validate ---------------------------------------------
// Note the field names: register.html uses hyphens ("first-name"), which are
// legal in HTML but not in a PHP variable, so read them straight from $_POST.
$title      = trim($_POST['title'] ?? '');
$first_name = trim($_POST['first-name'] ?? '');
$last_name  = trim($_POST['last-name'] ?? '');
$email      = trim($_POST['email'] ?? '');
$password   = (string) ($_POST['password'] ?? '');

$allowed_titles = ['Mr', 'Mrs', 'Ms', 'Mx', 'Dr'];
$errors = [];

if (!in_array($title, $allowed_titles, true)) {
    $errors[] = 'Choose a valid title.';
}
if ($first_name === '' || mb_strlen($first_name) > 50) {
    $errors[] = 'First name is required (max 50 characters).';
}
if ($last_name === '' || mb_strlen($last_name) > 50) {
    $errors[] = 'Last name is required (max 50 characters).';
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Enter a valid email address.';
}
if (strlen($password) < 8 || strlen($password) > 128) {
    $errors[] = 'Password must be 8-128 characters.';
}

if ($errors) {
    http_response_code(422);
    render_page(
        'Check your details',
        '<ul class="errors"><li>' . implode('</li><li>', array_map('esc', $errors)) . '</li></ul>'
        . '<p class="form-footer"><a href="register.html">Back to the form</a></p>'
    );
    exit;
}

// --- 4. Hash the password -- the plaintext is never stored --------------
$password_hash = password_hash($password, PASSWORD_DEFAULT, ['cost' => 12]);
unset($password);

// --- 5. Persist -------------------------------------------------------
try {
    $conn = get_connection();

    // Prepared statement: values are bound, never concatenated into SQL.
    $stmt = $conn->prepare(
        'INSERT INTO accounts (title, first_name, last_name, email, password_hash)'
        . ' VALUES (?, ?, ?, ?, ?);'
    );
    $stmt->execute([$title, $first_name, $last_name, $email, $password_hash]);
} catch (PDOException $e) {
    // SQLSTATE 23000 = integrity constraint violation (here: duplicate email).
    if ($e->getCode() === '23000') {
        http_response_code(409);
        render_page(
            'Already registered',
            '<p>That email address is already in use.</p>'
            . '<p class="form-footer"><a href="login_form.html">Log in instead</a></p>'
        );
        exit;
    }
    http_response_code(500);
    error_log('register.php: ' . $e->getMessage());
    render_page('Something went wrong', '<p>Please try again in a moment.</p>');
    exit;
}

// --- 6. Success ------------------------------------------------------
render_page(
    'Welcome, ' . esc($first_name) . '!',
    '<p>Your account has been created for <strong>' . esc($email) . '</strong>.</p>'
    . '<p class="form-footer"><a href="login_form.html">Log in</a></p>'
);
