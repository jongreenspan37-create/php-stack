<?php
// Database health check: proves PHP can connect to MySQL.
require_once __DIR__ . '/../connection.php';

// Asks MySQL for its version and current time. If that works, the connection is good.
function health($body = null)
{
    try {
        $conn = get_connection();
        $stmt = $conn->query('SELECT VERSION(), NOW();');
        // FETCH_NUM returns the row as a numbered list [0 => version, 1 => time];
        // [$a, $b] = ... unpacks it, like `version, server_time = row` in Python.
        [$version, $serverTime] = $stmt->fetch(PDO::FETCH_NUM);
        return ['status' => 'ok', 'db_version' => $version, 'server_time' => $serverTime];
    } catch (Exception $e) {
        // e.g. wrong password or MySQL not running: report it as JSON instead of crashing.
        return ['status' => 'error', 'detail' => $e->getMessage()];
    }
}
