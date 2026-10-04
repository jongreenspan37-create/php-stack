<?php
// Mirrors python-stack/app/www/scripts/user_crud.py.
// Reuses require_fields() and db_try() from role_crud.php.
// CRUD for the users table, called from the users form/table in script.js.
// The page uses camelCase names (firstName); the table uses First_Name, so
// every query translates between the two.
require_once __DIR__ . '/role_crud.php';

// Maximum length of each text field. `const` = a value that can never change.
const USER_MAX_FIELD_LENGTHS = [
    'firstName' => 25,
    'lastName' => 25,
    'email' => 50,
];

// Returns an error message if any field is too long (or roleId isn't a single
// value), otherwise null.
function check_user_field_lengths(array $body)
{
    // roleId is optional, so require_fields() doesn't check it -- do it here.
    if (isset($body['roleId']) && !is_scalar($body['roleId'])) {
        return 'roleId must be a single value';
    }
    foreach (USER_MAX_FIELD_LENGTHS as $field => $max) {
        // (string) converts the value to text so strlen() can count its characters.
        if (strlen((string) ($body[$field] ?? '')) > $max) {
            return "$field must be $max characters or fewer";
        }
    }
    return null;
}

// Inserts a user. Body: {"firstName", "lastName", "email", "roleId" (optional)}
function add_user($body)
{
    if ($err = require_fields($body, ['firstName', 'lastName', 'email'])) {
        return ['status' => 'error', 'detail' => $err];
    }
    if ($err = check_user_field_lengths($body)) {
        return ['status' => 'error', 'detail' => $err];
    }

    return db_try(function ($conn) use ($body) {
        $stmt = $conn->prepare('INSERT INTO users (First_Name, Last_Name, email, role_id) VALUES (?, ?, ?, ?);');
        // ($body['roleId'] ?? null) ?: null -> a missing, empty or 0 roleId is stored as NULL ("no role").
        $stmt->execute([$body['firstName'], $body['lastName'], $body['email'], ($body['roleId'] ?? null) ?: null]);
        // lastInsertId() gives the id MySQL just generated (Python uses RETURNING id).
        return ['status' => 'ok', 'id' => (int) $conn->lastInsertId()];
    });
}

// Returns every user with their role name:
// {"status": "ok", "users": [{"id", "firstName", "lastName", "email", "roleId", "roleName"}, ...]}
function list_users($body = null)
{
    return db_try(function ($conn) {
        // Aliases give the camelCase keys script.js expects.
        // LEFT JOIN keeps users with no role (roleName is then null).
        $stmt = $conn->query(
            'SELECT u.id, u.First_Name AS firstName, u.Last_Name AS lastName, u.email, '
            . 'u.role_id AS roleId, r.name AS roleName '
            . 'FROM users u LEFT JOIN roles r ON u.role_id = r.id '
            . 'ORDER BY u.id;'
        );
        return ['status' => 'ok', 'users' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
    });
}

// Updates every field of one user. Body: same as add_user plus "id".
function update_user($body)
{
    if ($err = require_fields($body, ['id', 'firstName', 'lastName', 'email'])) {
        return ['status' => 'error', 'detail' => $err];
    }
    if ($err = check_user_field_lengths($body)) {
        return ['status' => 'error', 'detail' => $err];
    }

    return db_try(function ($conn) use ($body) {
        $stmt = $conn->prepare('UPDATE users SET First_Name = ?, Last_Name = ?, email = ?, role_id = ? WHERE id = ?;');
        // The values must be in the same order as the ? placeholders above.
        $stmt->execute([$body['firstName'], $body['lastName'], $body['email'], ($body['roleId'] ?? null) ?: null, $body['id']]);
        return ['status' => 'ok'];
    });
}

// Deletes one user. Body: {"id": ..}
function delete_user($body)
{
    if ($err = require_fields($body, ['id'])) {
        return ['status' => 'error', 'detail' => $err];
    }

    return db_try(function ($conn) use ($body) {
        $stmt = $conn->prepare('DELETE FROM users WHERE id = ?;');
        $stmt->execute([$body['id']]);
        return ['status' => 'ok'];
    });
}
