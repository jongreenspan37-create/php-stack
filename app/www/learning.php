<!DOCTYPE html>
<html>

<body>

    <h1>My first PHP page</h1>
    <div>Variables</div>
    <?php
    require 'myglobal.php';
    $x = 5; // global scope

    function myTest()
    {
        // using x inside this function will not work
        global $x;
        echo "Variable x inside function is: $x<br>";
        global $g_int;
        echo "Global variable g_int inside function is: $g_int<br>";
    }
    myTest();

    echo "Variable x outside function is: $x";
    echo $g_string;

    function my_global()
    {
        $test = $GLOBALS['g_string'];
        echo "This is using the global array in a function $test<br>";
    }
    my_global();

    print '<div>' . $g_string . '</div>';
    ?>
    <div style="text-align:center;">Finding variable types
        <div>

            <?php
            var_dump($g_string);
            echo "<br> <br>";
            $ships = array("Passenger", "Bulk Cargo", "Oil Tanker");
            var_dump($ships);
            echo "<br> <br>";
            echo $ships[0];
            echo "<br> <br>";
            foreach ($ships as $ship) {
                echo $ship . "<br>";
            }

            ?>
        </div>
    </div>

    <div style="font-weight:bold;">String Functions
        <div>
            <?php
            print("the next string starts with a whitespace");
            echo $my_string = "     how many letters in this string and check for API";
            echo '<div>' . strlen($my_string) . '</div>';
            $bool = (str_contains($my_string, "API"));
            var_dump($bool);
            $my_file = __FILE__;
            echo $my_file;
            echo "<br>";
            echo __DIR__;
            ?>

        </div>
    </div>

</body>

</html>