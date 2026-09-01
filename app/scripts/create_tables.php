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

        $conn->exec($sql_roles);
        $conn->exec($sql_users);
        $conn->exec($sql_rate_limits);

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

        return ['status' => 'ok', 'message' => 'roles, users and rate_limits tables have been created'];
    } catch (Exception $e) {
        return ['status' => 'error', 'detail' => $e->getMessage()];
    }
}
