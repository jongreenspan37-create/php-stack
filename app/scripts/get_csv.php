<?php
// Reads CSV files from scripts/csv/.
// get_list() feeds the fruit exercises; get_formula_1() loads every F1 CSV into MySQL.

// Reads csv/fruits.csv and returns one array per row, keyed by the header row:
// [['id' => '1', 'fruit' => 'melon'], ...]. Like Python's csv.DictReader.
// Every value comes back as a string, because CSV has no types.
function get_list()
{
    $fruits_list = [];
    // __DIR__ is the folder this file lives in, so the path works whatever
    // the current working directory is (like Path(__file__).parent in Python).
    $fruits_path = __DIR__ . "/csv/fruits.csv";

    // fopen returns a file handle, or false if the file can't be opened.
    $file = fopen($fruits_path, 'r');
    if ($file === false) {
        throw new RuntimeException("Could not open file: $fruits_path");
    }

    // The first line is the header: ['id', 'fruit']
    $header = fgetcsv($file);
    $rows = [];
    // fgetcsv reads one line at a time and returns false at the end of the file.
    while (($rows = fgetcsv($file)) !== false) {
        // array_combine pairs header names with row values:
        // ['id', 'fruit'] + ['1', 'melon'] => ['id' => '1', 'fruit' => 'melon']
        $fruits_list[] = array_combine($header, $rows);
    }

    fclose($file);

    return $fruits_list;
}

// Rebuilds every F1 table from its CSV file in csv/formula_1/.
// Called by import_formula_1() in create_tables.php. Each table is dropped and
// recreated, so running it again gives a fresh copy instead of duplicate rows.
function get_formula_1(): void
{
    // f1_schema.php defines $f1_tables: table name => [column name => SQL type].
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
        // Prepare the INSERT once and reuse it for every row (much faster than
        // building a new query per row).
        $stmt = prepare_insert($conn, $table, $columnNames);

        // A transaction saves all the rows in one go at commit(), instead of
        // MySQL writing each row to disk separately.
        $conn->beginTransaction();
        while (($row = fgetcsv($file)) !== false) {
            insert_row($stmt, $row);
        }
        $conn->commit();
        fclose($file);
    }
}
