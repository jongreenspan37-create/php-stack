<?php
require_once __DIR__ . '/../connection.php';

function require_fields($body, array $fields)
{
    if (!$body) {
        return 'missing request body';
    }
    foreach ($fields as $f) {
        if (!array_key_exists($f, $body) || $body[$f] === null || $body[$f] === '') {
            return "$f is required";
        }
    }
    return null;
}

function db_try(callable $fn)
{
    try {
        $conn = get_connection();
        return $fn($conn);
    } catch (Exception $e) {
        return ['status' => 'error', 'detail' => $e->getMessage()];
    }
}

function add_role($body)
{
    if ($err = require_fields($body, ['id', 'name'])) {
        return ['status' => 'bad_input', 'detail' => $err];
    }

    return db_try(function ($conn) use ($body) {
        $stmt = $conn->prepare('INSERT INTO roles (id, name) VALUES (?, ?);');
        $stmt->execute([$body['id'], $body['name']]);
        return ['status' => 'created', 'data' => ['id' => $body['id'], 'name' => $body['name']]];
    });
}

function list_roles($body = null)
{
    return db_try(function ($conn) {
        $stmt = $conn->query('SELECT id, name FROM roles ORDER BY id;');
        return ['status' => 'ok', 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
    });
}

function update_role($body)
{
    if ($err = require_fields($body, ['id', 'name'])) {
        return ['status' => 'bad_input', 'detail' => $err];
    }

    return db_try(function ($conn) use ($body) {
        $stmt = $conn->prepare('UPDATE roles SET name = ? WHERE id = ?;');
        $stmt->execute([$body['name'], $body['id']]);
        return ['status' => 'ok'];
    });
}

function delete_role($body)
{
    if ($err = require_fields($body, ['id'])) {
        return ['status' => 'bad_input', 'detail' => $err];
    }

    return db_try(function ($conn) use ($body) {
        $stmt = $conn->prepare('DELETE FROM roles WHERE id = ?;');
        $stmt->execute([$body['id']]);
        return ['status' => 'ok'];
    });
}
