<?php
require_once __DIR__ . '/../connection.php';

$conn = db();
$sql_1 = "CREATE TABLE IF NOT EXISTS orders ("
    . "OrderID INT AUTO_INCREMENT PRIMARY KEY, "
    . "product_name VARCHAR(50));";
$conn->exec($sql_1);

$sql_2 = "ALTER TABLE orders MODIFY COLUMN product_name VARCHAR(100);";
$conn->exec($sql_2);

$sql_3 = "DROP TABLE orders;";
$conn->exec($sql_3);

$sql_emp = "CREATE TABLE employees ("
    . "id INT AUTO_INCREMENT PRIMARY KEY, first_name VARCHAR(50),"
    . "last_name VARCHAR(50),"
    . "hire_date DATE,"
    . "email VARCHAR(100),"
    . "INDEX idx_email (email)),"
    . "role_id INT,"
    . "CONSTRAINT fk_role FOREIGN KEY (role_id) REFERENCES roles(id);";
