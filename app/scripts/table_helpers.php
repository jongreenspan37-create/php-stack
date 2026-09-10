<?php
function create_table(PDO $pdo, string $table, array $columns): void
{
    $columnDefs = ['`id` INT AUTO_INCREMENT PRIMARY KEY']; //Creates start of array with id column as primary key

    foreach ($columns as $name => $type) {
        $columnDefs[] = "`$name` $type NULL";
    }

    // Full reload each import: drop so schema changes in f1_schema.php actually
    // take effect (CREATE TABLE IF NOT EXISTS would keep a stale definition).
    $pdo->exec(sprintf('DROP TABLE IF EXISTS `%s`', $table));

    $sql = sprintf('CREATE TABLE `%s` (%s)', $table, implode(', ', $columnDefs));
    $pdo->exec($sql);
}

function prepare_insert(PDO $pdo, string $table, array $columnNames): PDOStatement
{
    $pdo->exec("TRUNCATE TABLE `$table`");

    $columnList   = implode(', ', array_map(fn($c) => "`$c`", $columnNames));
    $placeholders = implode(', ', array_fill(0, count($columnNames), '?'));

    return $pdo->prepare("INSERT INTO `$table` ($columnList) VALUES ($placeholders)");
}

function insert_row(PDOStatement $stmt, array $row): void
{
    $stmt->execute(array_map(fn($v) => $v === '' ? null : $v, $row));
}
