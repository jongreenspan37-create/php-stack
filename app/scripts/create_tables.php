<?php
// Creates the database tables: roles/users for the CRUD exercise, and the F1 data.
require_once __DIR__ . '/../connection.php';
require_once __DIR__ . '/get_csv.php';

// Returns true if a table (or view) called $name exists in our database.
// MySQL keeps a list of every table in information_schema.tables.
function table_exists(PDO $conn, string $name): bool
{
    // :db and :table are named placeholders; the values are sent separately in
    // execute(), so they can never change the SQL itself (no SQL injection).
    $stmt = $conn->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = :db AND table_name = :table");
    $stmt->execute([
        ':db' => getenv('MYSQL_DATABASE'),
        ':table' => $name
    ]);
    // fetchColumn() gets the single COUNT(*) number; (bool) turns 0/1 into false/true.
    return (bool)$stmt->fetchColumn();
}



// Creates the roles and users tables plus the users_with_roles view.
// Safe to run more than once: "IF NOT EXISTS" and table_exists() skip anything already there.
function create_tables($body = null)
{
    try {
        $conn = db();
        //These two tables show a join between users and roles, with a foreign key constraint. The accounts table is for authentication and is separate from the users table.
        // Long SQL is built from pieces joined with . (PHP's string join operator).
        $sql_roles = "CREATE TABLE IF NOT EXISTS roles ("
            . "id INT PRIMARY KEY,"
            . "name VARCHAR(255)"
            . ");";
        // users.role_id must match an existing roles.id (or be NULL) because of the
        // FOREIGN KEY. AUTO_INCREMENT means MySQL picks the next id itself.
        $sql_users = "CREATE TABLE IF NOT EXISTS users ("
            . "id INT AUTO_INCREMENT PRIMARY KEY,"
            . "Last_Name VARCHAR(255),"
            . "First_Name VARCHAR(255),"
            . "email VARCHAR(255),"
            . "role_id INT,"
            . "CONSTRAINT fk_role FOREIGN KEY (role_id) REFERENCES roles(id)"
            . ");";

        // exec() runs SQL that doesn't return rows (CREATE, DROP, ...).
        $conn->exec($sql_roles);
        $conn->exec($sql_users);

        // Creates a viewinstead of re-writing the join everywhere.
        // A view is a saved SELECT you can query like a table: SELECT * FROM users_with_roles.
        // LEFT JOIN keeps users that have no role (role_name is then NULL).
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

        // MySQL has no "CREATE VIEW IF NOT EXISTS", so check first.
        if (!table_exists($conn, 'users_with_roles')) {
            $conn->exec($sql_users_roles_view);
        }

        return ['status' => 'ok', 'message' => 'roles and users tables (+ users_with_roles view) have been created'];
    } catch (Throwable $e) {
        return ['status' => 'error', 'detail' => $e->getMessage()];
    }
}

// Loads all the F1 CSV files into MySQL (see get_formula_1() in get_csv.php).
// Also reached through the 'create_tables/create_f1_tables' route, Python's name for it.
// ": array" is a return type hint, like "-> dict" in Python, but PHP enforces it.
function import_formula_1($body = null): array
{
    try {
        get_formula_1();
        return ['status' => 'ok', 'message' => 'F1 data imported successfully'];
    } catch (Throwable $e) {
        return ['status' => 'error', 'detail' => $e->getMessage()];
    }
}
