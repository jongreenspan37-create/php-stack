<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            corePlugins: {
                preflight: false
            },
        };
    </script>
    <link rel="stylesheet" href="style.css" />
</head>

<body>
    <nav>
        <ul>
            <li><a href="index.html">Main Page</a></li>
            <li><a href="list-manipulation.html">List Manipulation</a></li>
            <li><a href="database-interaction.html">Database Interaction</a></li>
            <li><a href="formula1.php">Formula 1</a></li>
            <li><a href="learning.php">Learning PHP</a></li>
        </ul>
    </nav>

    <h1>My first PHP page</h1>
    <div>Variables</div>
    <?php
    // PHP code inside the page runs on the server; whatever it echoes becomes part of the HTML.
    // myglobal.php defines $g_string and $g_int.
    require 'myglobal.php';
    $x = 5; // global scope

    // PHP functions can't see outside variables unless they say `global $name`.
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
        // $GLOBALS is an array of every global variable, another way to reach them.
        $test = $GLOBALS['g_string'];
        echo "This is using the global array in a function $test<br>";
    }
    my_global();

    print '<div>' . $g_string . '</div>';
    ?>
    <div style="text-align:center;">Finding variable types
        <div>

            <?php
            // var_dump prints a value with its type and length, e.g. string(5) "hello".
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
            // str_contains returns true/false; var_dump shows it as bool(true).
            $bool = (str_contains($my_string, "API"));
            var_dump($bool);
            // __FILE__ = this file's full path, __DIR__ = its folder.
            $my_file = __FILE__;
            echo $my_file;
            echo "<br>";
            echo __DIR__;
            ?>

        </div>
    </div>

</body>

</html>