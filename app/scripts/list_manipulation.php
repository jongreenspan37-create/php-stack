<?php
// Working with lists (PHP arrays) using the rows of csv/fruits.csv.
// Mirrors python-stack/app/www/scripts/list_manipulation.py.
require_once __DIR__ . '/get_csv.php';

// Returns every row of fruits.csv, e.g. [{"id":"1","fruit":"melon"}, ...].
// Used by the "list" and "table" buttons on list-manipulation.html.
function upload_fruits($body = Null)
{
    return get_list();
}

// Counts how many times one fruit appears in the CSV.
// The page sends just a JSON string as the body, e.g. "apple".
function count_fruit($body)
{
    $fruit = $body;
    // Reject anything that isn't a non-empty string (e.g. a list or object),
    // otherwise using it in the "not found" message below would print a PHP
    // warning into the response and break the JSON.
    if (!is_string($fruit) || $fruit === '') {
        return ['error' => 'enter a fruit name'];
    }
    $fruits_list = upload_fruits();

    // Loop over every row and count the matches.
    // Python does this in one line: sum(1 for row in fruits if row["fruit"] == target)
    $count = 0;
    foreach ($fruits_list as $row) {
        if ($row['fruit'] === $fruit) {
            $count++;
        }
    }

    if ($count === 0) {
        // Double quotes let PHP insert variables into the string: "$fruit not found"
        // (like an f-string in Python: f"{fruit} not found").
        return ['error' => "$fruit not found"];
    }

    // The page reads data.fruit and data.count, so the keys must match exactly.
    return ['fruit' => $fruit, 'count' => $count];
}

// Builds a new list from the fruits: one {"id", "description"} item per row.
function prepare_data($body = Null)
{
    $prepared = [];
    $fruits = upload_fruits();
    $text = " is a fruit";

    foreach ($fruits as $row) {
        // $array[] = value appends to the end, like Python's list.append(value).
        // The . operator joins strings (Python uses +).
        $prepared[] = ["id" => $row['id'], "description" => $row['fruit'] . $text];
    }
    return $prepared;
}
