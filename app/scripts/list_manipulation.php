<?php
require_once __DIR__ . '/get_csv.php';

function upload_fruits($body = Null)
{
    return get_list();
}

function count_fruit($body)
{
    $fruit = $body;
    $fruits_list = upload_fruits();

    $count = 0;
    foreach ($fruits_list as $row) {
        if ($row['fruit'] === $fruit) {
            $count++;
        }
    }

    return $count;
}

function prepare_data($body = Null)
{
    $prepared = [];
    $fruits = upload_fruits($body = Null);
    $text = " is a fruit";

    foreach ($fruits as $row) {
        $prepared[] = ["description" => $row['fruit'] . $text];
    }
    return $prepared;
}
