<?php

function get_list()
{
    $fruits_list = [];
    $fruits_path = __DIR__ . "/csv/fruits.csv";

    $file = fopen($fruits_path, 'r');
    if ($file === false) {
        throw new RuntimeException("Could not open file: $fruits_path");
    }

    $header = fgetcsv($file);
    $rows = [];
    while (($rows = fgetcsv($file)) !== false) {
        $fruits_list[] = array_combine($header, $rows);
    }

    fclose($file);

    return $fruits_list;
}

function get_formula_1(): void
{
    require_once __DIR__ . '/f1_schema.php';
    require_once __DIR__ . '/table_helpers.php';

    $conn = db();


    // Loop through each table in the $f1_tables gives $table as table name and $columns as the column definitions in an array
    foreach ($f1_tables as $table => $columns) {
        $f1_path = __DIR__ . "/csv/formula_1/{$table}.csv";


        $file = fopen($f1_path, 'r');
        //Check file exists
        if ($file === false) {
            throw new RuntimeException("Could not open file: $f1_path");
        }

        create_table($conn, $table, $columns); //create the table in the database using the column definitions from the schema
        fgetcsv($file); // reads and skips header row -- $columns already has the names, in order

        $columnNames = array_keys($columns);
        $stmt = prepare_insert($conn, $table, $columnNames);

        $conn->beginTransaction();
        while (($row = fgetcsv($file)) !== false) {
            insert_row($stmt, $row);
        }
        $conn->commit();
        fclose($file);
    }
}
