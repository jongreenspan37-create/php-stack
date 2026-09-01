<?php
require_once __DIR__ . '/../connection.php';

function health($body = null)
{
    try {
        $conn = get_connection();
        $stmt = $conn->query('SELECT VERSION(), NOW();');
        [$version, $serverTime] = $stmt->fetch(PDO::FETCH_NUM);
        return ['status' => 'ok', 'db_version' => $version, 'server_time' => $serverTime];
    } catch (Exception $e) {
        return ['status' => 'error', 'detail' => $e->getMessage()];
    }
}
