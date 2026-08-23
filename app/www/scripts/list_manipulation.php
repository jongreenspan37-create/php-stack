<?php
require_once __DIR__ . '/get_csv.php';

function upload_fruits($body = Null)
{
    return get_list();
}

function count_fruit($body)
{
    $fruit = $body;
    $fruits_list = get_list();

    $count = 0;
    foreach ($fruits_list as $row) {
        if ($row['fruit'] === $fruit) {
            $count++;
        }
    }

    return $count;
}
