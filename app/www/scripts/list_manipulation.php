<?php

function upload_fruits($body = Null)
{

    $fruits_list = [];
    $fruits_path = __Dir__ . "/csv/fruits.csv";


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
