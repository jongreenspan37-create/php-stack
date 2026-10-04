<?php
// Helpers used by get_formula_1() (get_csv.php) to load the F1 CSVs into MySQL.

// Drops and recreates $table with an id column plus one column per entry in
// $columns (column name => SQL type, from f1_schema.php).
function create_table(PDO $pdo, string $table, array $columns): void
{
    $columnDefs = ['`id` INT AUTO_INCREMENT PRIMARY KEY']; //Creates start of array with id column as primary key

    foreach ($columns as $name => $type) {
        $columnDefs[] = "`$name` $type NULL";
    }

    // Full reload each import: drop so schema changes in f1_schema.php actually
    // take effect (CREATE TABLE IF NOT EXISTS would keep a stale definition).
    $pdo->exec(sprintf('DROP TABLE IF EXISTS `%s`', $table));

    // sprintf fills in each %s in order, like Python's "..." % (a, b).
    $sql = sprintf('CREATE TABLE `%s` (%s)', $table, implode(', ', $columnDefs));
    $pdo->exec($sql);
}

// Empties $table and returns a prepared INSERT with one ? per column,
// ready to be run once per CSV row.
function prepare_insert(PDO $pdo, string $table, array $columnNames): PDOStatement
{
    $pdo->exec("TRUNCATE TABLE `$table`");

    // Backticks quote MySQL column names. array_map runs the arrow function on
    // each name (like a Python list comprehension); fn($c) => ... is a one-line function.
    $columnList   = implode(', ', array_map(fn($c) => "`$c`", $columnNames));
    $placeholders = implode(', ', array_fill(0, count($columnNames), '?'));

    return $pdo->prepare("INSERT INTO `$table` ($columnList) VALUES ($placeholders)");
}

// Inserts one CSV row. Empty CSV cells are stored as NULL instead of ''.
function insert_row(PDOStatement $stmt, array $row): void
{
    $stmt->execute(array_map(fn($v) => $v === '' ? null : $v, $row));
}
