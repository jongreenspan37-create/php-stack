<?php
require_once __DIR__ . '/../connection.php';

function table_exists(PDO $conn, string $name): bool
{
    $stmt = $conn->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = :db AND table_name = :table");
    $stmt->execute([
        ':db' => getenv('MYSQL_DATABASE'),
        ':table' => $name
    ]);
    return (bool)$stmt->fetchColumn();
}

function trigger_exists(PDO $conn, string $name): bool
{
    $stmt = $conn->prepare(
        "SELECT COUNT(*) FROM information_schema.triggers "
            . "WHERE trigger_schema = DATABASE() AND trigger_name = :name"
    );
    $stmt->execute(['name' => $name]);
    return (bool) $stmt->fetchColumn();
}

function create_tables($body = null)
{
    try {
        $conn = get_connection();
        //These two tables show a join between users and roles, with a foreign key constraint. The accounts table is for authentication and is separate from the users table.
        $sql_roles = "CREATE TABLE roles ("
            . "id INT PRIMARY KEY,"
            . "name VARCHAR(255)"
            . ");";
        $sql_users = "CREATE TABLE users ("
            . "id INT AUTO_INCREMENT PRIMARY KEY,"
            . "Last_Name VARCHAR(255),"
            . "First_Name VARCHAR(255),"
            . "email VARCHAR(255),"
            . "role_id INT,"
            . "CONSTRAINT fk_role FOREIGN KEY (role_id) REFERENCES roles(id)"
            . ");";

        // Backs the register / login pages. Must stay identical to
        // ensure_accounts_table() in auth.php -- that function creates this
        // same table on first use, and register.php inserts
        // (title, first_name, last_name, email, password_hash). Never the
        // plaintext password; email is UNIQUE so a duplicate signup 409s.
        $sql_accounts = "CREATE TABLE accounts ("
            . "id INT AUTO_INCREMENT PRIMARY KEY,"
            . "title VARCHAR(10) NOT NULL,"
            . "first_name VARCHAR(50) NOT NULL,"
            . "last_name VARCHAR(50) NOT NULL,"
            . "email VARCHAR(254) NOT NULL UNIQUE,"
            . "password_hash VARCHAR(255) NOT NULL,"
            . "created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP"
            . ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

        // Fixed-window login throttle. One row per identifier (e.g.
        // "login:<ip>" or an email); no FK to users because it has to work
        // before we know who -- or whether -- the caller is.
        //   attempts      : hits counted in the current window
        //   window_start  : when that window opened; a request past
        //                   window_start + INTERVAL resets attempts to 1
        //   locked_until   : set once attempts crosses the limit; while
        //                    NOW() < locked_until every request is refused
        // Written with INSERT ... ON DUPLICATE KEY UPDATE against the
        // UNIQUE(identifier) key, so check-and-bump is one atomic statement.
        $sql_rate_limits = "CREATE TABLE rate_limits ("
            . "id INT AUTO_INCREMENT PRIMARY KEY,"
            . "identifier VARCHAR(255) NOT NULL,"
            . "attempts INT NOT NULL DEFAULT 0,"
            . "window_start DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,"
            . "locked_until DATETIME NULL,"
            . "UNIQUE KEY uq_rate_limits_identifier (identifier)"
            . ");";

        if (!table_exists($conn, 'roles')) {
            $conn->exec($sql_roles);
        }
        if (!table_exists($conn, 'users')) {
            $conn->exec($sql_users);
        }
        if (!table_exists($conn, 'accounts')) {
            $conn->exec($sql_accounts);
        }
        if (!table_exists($conn, 'rate_limits')) {
            $conn->exec($sql_rate_limits);
        }

        // No row-limit trigger on roles: adding a role will be a protected,
        // auth-gated endpoint rather than something capped in the schema.

        $sql_users_trigger = "CREATE TRIGGER users_row_limit "
            . "BEFORE INSERT ON users "
            . "FOR EACH ROW "
            . "BEGIN "
            . "IF (SELECT COUNT(*) FROM users) >= 5 THEN "
            . "SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'users table row limit (5) reached'; "
            . "END IF; "
            . "END";

        if (!trigger_exists($conn, 'users_row_limit')) {
            $conn->exec($sql_users_trigger);
        }

        // Read model: one row per user with the role name resolved.
        // LEFT JOIN because users.role_id is nullable (a user may have no
        // role yet). Query this instead of re-writing the join everywhere.
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
    } catch (Exception $e) {
        return ['status' => 'error', 'detail' => $e->getMessage()];
    }
}

// When run straight from the CLI (the Docker entrypoint does this on every
// container start) rather than require()'d by router.php, actually build the
// schema and exit non-zero on failure so a broken DB stops the container
// instead of being served. require_once from the router hits PHP_SAPI
// 'cli-server' / 'fpm-fcgi' and skips this block.
if (PHP_SAPI === 'cli' && isset($argv[0]) && realpath($argv[0]) === __FILE__) {
    $result = create_tables();
    fwrite(STDOUT, json_encode($result) . "\n");
    exit($result['status'] === 'ok' ? 0 : 1);
}
