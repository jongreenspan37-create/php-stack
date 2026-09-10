<?php
$array = ['table1' => [1 => 'one', 2 => 'two', 3 => 'three', 4 => 'four', 5 => 'five', 6 => 'six'], 'table2' => [1 => 'uno', 2 => 'dos', 3 => 'tres', 4 => 'cuatro', 5 => 'cinco', 6 => 'seis']];

foreach ($array as $table => $value) {
    echo "Table: $table\n";
    foreach ($value as $key => $val) {
        echo "Key: $key, Value: $val\n";
    }
    echo "\n";
}
