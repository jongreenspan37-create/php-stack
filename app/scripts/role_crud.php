<?php
// CRUD (Create, Read, Update, Delete) for the roles table.
// Mirrors python-stack/app/www/scripts/role_crud.py. Called from the roles
// form/table in script.js on database-interaction.html.
// require_fields() and db_try() are also reused by user_crud.php.
require_once __DIR__ . '/../connection.php';

// Checks the request body has every field in $fields, each a single non-empty value.
// Returns an error message string, or null when everything is fine.
// (FastAPI does this for you with a Pydantic model; here we do it by hand.)
function require_fields($body, array $fields)
{
    // !$body is true for null, an empty array, an empty string...
    if (!$body) {
        return 'missing request body';
    }
    // The body must be a JSON object (decoded to a PHP array), not e.g. a plain string.
    if (!is_array($body)) {
        return 'request body must be a JSON object';
    }
    foreach ($fields as $f) {
        if (!array_key_exists($f, $body) || $body[$f] === null || $body[$f] === '') {
            return "$f is required";
        }
        // is_scalar = string, number or bool. Rejects lists/objects, which would
        // otherwise turn into the text "Array" and print a PHP warning.
        if (!is_scalar($body[$f])) {
            return "$f must be a single value";
        }
    }
    return null;
}

// Opens a database connection, runs $fn with it, and turns any database error
// into a JSON-friendly error array. Saves repeating try/catch in every function
// (Python repeats try/except/finally in each one instead).
// callable = "a function you can call"; here we pass an anonymous function.
function db_try(callable $fn)
{
    try {
        $conn = get_connection();
        return $fn($conn);
    } catch (Exception $e) {
        return ['status' => 'error', 'detail' => $e->getMessage()];
    }
    // No close() needed: PHP closes the connection when $conn goes out of scope.
}

// Inserts a new role. Body: {"id": .., "name": ..}
function add_role($body)
{
    // Assign and test in one go: if require_fields returned a message, $err holds it.
    if ($err = require_fields($body, ['id', 'name'])) {
        return ['status' => 'error', 'detail' => $err];
    }

    // function ($conn) use ($body) { ... } is an anonymous function (like a Python lambda,
    // but multi-line). `use ($body)` lets it see $body from the outer function --
    // PHP functions can't see outer variables unless you list them.
    return db_try(function ($conn) use ($body) {
        // prepare() + ? placeholders: the values are sent separately from the SQL,
        // which prevents SQL injection (same as %s with psycopg2 in Python).
        $stmt = $conn->prepare('INSERT INTO roles (id, name) VALUES (?, ?);');
        $stmt->execute([$body['id'], $body['name']]);
        // No commit() needed: PDO auto-commits each statement unless you start
        // a transaction (psycopg2 in Python needs conn.commit()).
        return ['status' => 'ok'];
    });
}

// Returns every role: {"status": "ok", "roles": [{"id": 1, "name": "Admin"}, ...]}
function list_roles($body = null)
{
    return db_try(function ($conn) {
        // query() is fine here because there's no user input in the SQL.
        $stmt = $conn->query('SELECT id, name FROM roles ORDER BY id;');
        // FETCH_ASSOC returns each row as ['id' => .., 'name' => ..], which becomes a JSON object.
        return ['status' => 'ok', 'roles' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
    });
}

// Renames a role. Body: {"id": .., "name": ..}
function update_role($body)
{
    if ($err = require_fields($body, ['id', 'name'])) {
        return ['status' => 'error', 'detail' => $err];
    }

    return db_try(function ($conn) use ($body) {
        $stmt = $conn->prepare('UPDATE roles SET name = ? WHERE id = ?;');
        $stmt->execute([$body['name'], $body['id']]);
        return ['status' => 'ok'];
    });
}

// Deletes a role. Body: {"id": ..}
// Fails with a database error if a user still has this role (the foreign key blocks it).
function delete_role($body)
{
    if ($err = require_fields($body, ['id'])) {
        return ['status' => 'error', 'detail' => $err];
    }

    return db_try(function ($conn) use ($body) {
        $stmt = $conn->prepare('DELETE FROM roles WHERE id = ?;');
        $stmt->execute([$body['id']]);
        return ['status' => 'ok'];
    });
}
