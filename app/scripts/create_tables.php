<?php
require_once __DIR__ . '/../connection.php';
require_once __DIR__ . '/get_csv.php';

function table_exists(PDO $conn, string $name): bool
{
    $stmt = $conn->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = :db AND table_name = :table");
    $stmt->execute([
        ':db' => getenv('MYSQL_DATABASE'),
        ':table' => $name
    ]);
    return (bool)$stmt->fetchColumn();
}



function create_tables($body = null)
{
    try {
        $conn = db();
        //These two tables show a join between users and roles, with a foreign key constraint. The accounts table is for authentication and is separate from the users table.
        $sql_roles = "CREATE TABLE IF NOT EXISTS roles ("
            . "id INT PRIMARY KEY,"
            . "name VARCHAR(255)"
            . ");";
        $sql_users = "CREATE TABLE IF NOT EXISTS users ("
            . "id INT AUTO_INCREMENT PRIMARY KEY,"
            . "Last_Name VARCHAR(255),"
            . "First_Name VARCHAR(255),"
            . "email VARCHAR(255),"
            . "role_id INT,"
            . "CONSTRAINT fk_role FOREIGN KEY (role_id) REFERENCES roles(id)"
            . ");";

        $conn->exec($sql_roles);
        $conn->exec($sql_users);

        // Creates a viewinstead of re-writing the join everywhere.
        $sql_users_roles_view = "CREATE VIEW users_with_roles AS "
            . "SELECT "
            . "u.id, "
            . "u.First_Name, "
            . "u.Last_Name, "
            . "u.email, "
            . "u.role_id, "
            . "r.name AS role_name "
            . "FROM users u "
            . "LEFT JOIN roles r ON r.id = u.role_id";

        if (!table_exists($conn, 'users_with_roles')) {
            $conn->exec($sql_users_roles_view);
        }

        return ['status' => 'ok', 'message' => 'roles, users and rate_limits tables (+ users_with_roles view) have been created'];
    } catch (Throwable $e) {
        return ['status' => 'error', 'detail' => $e->getMessage()];
    }
}

function import_formula_1($body = null): array
{
    try {
        get_formula_1();
        return ['status' => 'ok', 'message' => 'F1 data imported successfully'];
    } catch (Throwable $e) {
        return ['status' => 'error', 'detail' => $e->getMessage()];
    }
}
