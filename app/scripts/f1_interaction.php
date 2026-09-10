<?php
require_once __DIR__ . '/../connection.php';

$conn = db();

function query_f1_data($query)
{
    global $conn;
    $stmt = $conn->query($query);
    return $stmt->fetchAll();
}

$rows = query_f1_data("SELECT Name
FROM constructors;");

print_r($rows);
