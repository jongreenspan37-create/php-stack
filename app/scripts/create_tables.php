<?php
require_once __DIR__ . '/../connection.php';

function create_tables($body = null)
{
    try {
        $conn = get_connection();

        $sql_roles = "CREATE TABLE roles ("
            . "id INT PRIMARY KEY,"
            . "name VARCHAR(255)"
            . ");";
        $sql_users = "CREATE TABLE users ("
            . "id INT AUTO_INCREMENT PRIMARY KEY,"
            . "LastName VARCHAR(255),"
            . "FirstName VARCHAR(255),"
            . "email VARCHAR(255),"
            . "role_id INT,"
            . "CONSTRAINT fk_role FOREIGN KEY (role_id) REFERENCES roles(id)"
            . ");";

        $conn->exec($sql_roles);
        $conn->exec($sql_users);

        $sql_roles_trigger = "CREATE TRIGGER roles_row_limit "
            . "BEFORE INSERT ON roles "
            . "FOR EACH ROW "
            . "BEGIN "
            . "IF (SELECT COUNT(*) FROM roles) >= 3 THEN "
            . "SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'roles table row limit (3) reached'; "
            . "END IF; "
            . "END";
        $conn->exec($sql_roles_trigger);

        $sql_users_trigger = "CREATE TRIGGER users_row_limit "
            . "BEFORE INSERT ON users "
            . "FOR EACH ROW "
            . "BEGIN "
            . "IF (SELECT COUNT(*) FROM users) >= 5 THEN "
            . "SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'users table row limit (5) reached'; "
            . "END IF; "
            . "END";
        $conn->exec($sql_users_trigger);

        return ['status' => 'ok', 'message' => 'User and Role tables have been created'];
    } catch (Exception $e) {
        return ['status' => 'error', 'detail' => $e->getMessage()];
    }
}
