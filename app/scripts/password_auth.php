<?php
// Password hashing pattern in isolation. NOT added to router.php $routes.
//
// FastAPI analogue: passlib's CryptContext.
//   pwd = CryptContext(schemes=["bcrypt"], deprecated="auto")
//   pwd.hash(plain)              ->  password_hash()
//   pwd.verify(plain, stored)    ->  password_verify()
//   pwd.needs_update(stored)     ->  password_needs_rehash()
//
// PHP ships these three functions in core (ext-standard), no library,
// no Composer. bcrypt is always available; argon2 depends on the build.
//
// Run the demo (no database needed for the hash/verify half):
//   docker compose exec app php scripts/password_auth.php

require_once __DIR__ . '/../connection.php';


// ---------------------------------------------------------------------------
// 1. Hash a plaintext password
// ---------------------------------------------------------------------------

function hash_password(string $plain): string
{
    // PASSWORD_DEFAULT = "whatever PHP currently considers best" (bcrypt today,
    // may change in a future PHP release -- that's the point of needs_rehash).
    //
    // The salt is generated internally with a CSPRNG. You never manage it:
    // the returned string is self-describing and already contains
    //   $2y$ (algo) $12$ (cost) <22 chars salt><31 chars hash>
    // e.g. $2y$12$eImiTXuWVxfM37uY4JANjQ.../nyLQE.M4y8Yb...
    //
    // Explicit cost so it's visible; 12 is a reasonable 2026 default.
    return password_hash($plain, PASSWORD_DEFAULT, ['cost' => 12]);

    // argon2id variant (only if your PHP build has it):
    // return password_hash($plain, PASSWORD_ARGON2ID, [
    //     'memory_cost' => 65536, 'time_cost' => 4, 'threads' => 1,
    // ]);
}


// ---------------------------------------------------------------------------
// 2. Add the column that doesn't exist yet (one-off "migration")
// ---------------------------------------------------------------------------

function add_password_column($body = null)
{
    try {
        $conn = get_connection();

        // 255 chars: bcrypt output is 60, argon2id ~96. 255 leaves headroom
        // for whatever PASSWORD_DEFAULT becomes later. Never size it to 60.
        // Nullable so existing rows stay valid until each user sets one.
        $conn->exec(
            "ALTER TABLE users ADD COLUMN password_hash VARCHAR(255) NULL AFTER email;"
        );

        return ['status' => 'ok', 'message' => 'users.password_hash added'];
    } catch (Exception $e) {
        return ['status' => 'error', 'detail' => $e->getMessage()];
    }
}


// ---------------------------------------------------------------------------
// 3. Set / change a user's password  ->  store ONLY the hash
// ---------------------------------------------------------------------------

function set_user_password($body)
{
    $id       = $body['id'] ?? null;
    $password = $body['password'] ?? null;

    if (!$id || !$password) {
        return ['status' => 'error', 'detail' => 'id and password are required'];
    }

    try {
        $conn = get_connection();

        $stmt = $conn->prepare('UPDATE users SET password_hash = ? WHERE id = ?;');
        $stmt->execute([hash_password($password), $id]);

        // The plaintext is never written anywhere -- no log, no column, no
        // return value. It goes out of scope when this function returns.
        return ['status' => 'ok', 'rows' => $stmt->rowCount()];
    } catch (Exception $e) {
        return ['status' => 'error', 'detail' => $e->getMessage()];
    }
}


// ---------------------------------------------------------------------------
// 4. Check a submitted password against the stored hash
// ---------------------------------------------------------------------------

function verify_user_password($body)
{
    $id       = $body['id'] ?? null;
    $password = $body['password'] ?? null;

    if (!$id || !$password) {
        return ['status' => 'error', 'detail' => 'id and password are required'];
    }

    try {
        $conn = get_connection();

        $stmt = $conn->prepare('SELECT password_hash FROM users WHERE id = ?;');
        $stmt->execute([$id]);
        $stored = $stmt->fetchColumn();   // string, or false if no such row

        // password_verify does a constant-time comparison and re-derives the
        // cost/salt from the stored string itself -- you pass it nothing else.
        // Returns plain bool. Wrong password and unknown user look the same.
        if ($stored === false || !password_verify($password, $stored)) {
            return ['status' => 'ok', 'valid' => false];
        }

        $result = ['status' => 'ok', 'valid' => true];

        // Opportunistic upgrade: if PASSWORD_DEFAULT or the cost changed since
        // this hash was made, re-hash now that we have the plaintext in hand.
        if (password_needs_rehash($stored, PASSWORD_DEFAULT, ['cost' => 12])) {
            $upd = $conn->prepare('UPDATE users SET password_hash = ? WHERE id = ?;');
            $upd->execute([hash_password($password), $id]);
            $result['rehashed'] = true;
        }

        return $result;
    } catch (Exception $e) {
        return ['status' => 'error', 'detail' => $e->getMessage()];
    }
}


// ---------------------------------------------------------------------------
// CLI demo -- the hash/verify half runs with no database at all
// ---------------------------------------------------------------------------

if (PHP_SAPI === 'cli' && realpath($argv[0] ?? '') === realpath(__FILE__)) {
    $plain = 'correct horse battery staple';

    $hash = hash_password($plain);
    echo "plaintext : {$plain}\n";
    echo "hash      : {$hash}\n";
    echo "length    : " . strlen($hash) . "\n\n";

    // Same input hashed twice -> different strings (different random salt),
    // yet both verify. This is why you can't look a password up by its hash.
    $hash2 = hash_password($plain);
    echo "hashed again, differs : " . ($hash !== $hash2 ? 'yes' : 'no') . "\n\n";

    var_dump(password_verify($plain, $hash));            // true
    var_dump(password_verify('wrong password', $hash));  // false

    echo "\nneeds rehash at cost 4 (simulating a raised cost): ";
    var_dump(password_needs_rehash($hash, PASSWORD_DEFAULT, ['cost' => 4]));
}
