<?php
// Front controller for PHP's built-in server: php -S 0.0.0.0:8080 router.php
// Mirrors python-stack/app/www/router.py: an explicit route table maps
// "<file>/<func>" strings to real callables. Unlike require($file) +
// function_exists($func) + $func($body), a request can never reach a
// function that wasn't deliberately added to $routes below -- PHP's
// function table is global, so function_exists()/a dynamic call on a
// user-supplied name would also match every built-in (system, exec, ...).
// $file was previously used to build a filesystem path too, allowing
// path traversal to require() arbitrary .php files; routes below are
// looked up by exact key instead, so user input never reaches a path.
//
// How a request flows (FastAPI comparison: this file is your app + router):
//   1. php -S runs this whole file from the top for EVERY request.
//   2. Non-API URLs return false -> the built-in server serves the file from www/.
//   3. API URLs are looked up in $routes, the JSON body is decoded, the matching
//      function runs, and whatever array it returns is echoed back as JSON.

// $_SERVER['REQUEST_URI'] is the full path + query string, e.g. "/api/run/basic/add_numbers?x=1".
// parse_url(..., PHP_URL_PATH) keeps just the path part: "/api/run/basic/add_numbers".
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$prefix = '/api/run/';

//compare start of uri with prefix if false serve static file i.e. return false
if (!str_starts_with($uri, $prefix)) {
    return false; // let the built-in server serve static files / 404
}

//set the header always the same
// Every API response is JSON, so set it once here instead of in each function.
// (FastAPI does this for you when a route returns a dict.)
header('Content-Type: application/json');

// PHP prints warnings/errors into the response body by default, which turns
// the JSON into HTML. Log them instead, and turn anything uncaught into a JSON
// 500 -- the same job app.py's try/except in handle_run() does for Python.
ini_set('display_errors', '0');
ini_set('log_errors', '1');

//add the script pages
// require_once loads each file's functions into memory for this request
// ("_once" means a file pulled in twice, e.g. connection.php, is only loaded once).
// Python equivalent: the `from scripts.x import y` lines at the top of router.py.
// These files must ONLY define functions -- any echo at the top level of an
// included file goes straight into the response and breaks the JSON.
require_once __DIR__ . '/scripts/basic.php';
require_once __DIR__ . '/scripts/create_tables.php';
require_once __DIR__ . '/scripts/get_csv.php';
require_once __DIR__ . '/scripts/health.php';
require_once __DIR__ . '/scripts/list_manipulation.php';
require_once __DIR__ . '/scripts/role_crud.php';
require_once __DIR__ . '/scripts/user_crud.php';
require_once __DIR__ . '/scripts/date_manipulation.php';
require_once __DIR__ . '/scripts/test.php';


// The route table: URL name => function name. Same idea as ROUTES in router.py.
// To add an endpoint: write the function in scripts/, require_once its file
// above, then add one line here.
$routes = [
    'basic/add_numbers' => 'add_numbers',
    'basic/add_strings' => 'add_strings',
    'basic/add_phrase' => 'add_phrase',
    'basic/string_func' => 'string_func',
    'list_manipulation/upload_fruits' => 'upload_fruits',
    'list_manipulation/count_fruit' => 'count_fruit',
    'list_manipulation/prepare_data' => 'prepare_data',
    'health/health' => 'health',
    'create_tables/create_tables' => 'create_tables',
    'create_tables/import_formula_1' => 'import_formula_1',
    'role_crud/add_role' => 'add_role',
    'role_crud/list_roles' => 'list_roles',
    'role_crud/update_role' => 'update_role',
    'role_crud/delete_role' => 'delete_role',
    'user_crud/add_user' => 'add_user',
    'user_crud/list_users' => 'list_users',
    'user_crud/update_user' => 'update_user',
    'user_crud/delete_user' => 'delete_user',
    'date_manipulation/adjust_date' => 'adjust_date',
    'test/test_1' => 'test_1',
    'test/test_2' => 'test_2',
    // PHP's equivalent of Python's create_f1_tables is import_formula_1.
    'create_tables/create_f1_tables' => 'import_formula_1',

];


// Get the last part to check against list i.e. romve '/api/run/'
$name = substr($uri, strlen($prefix));
// Append the route name to debug.log (mode 3 = write to the file given).
error_log("name = " . $name, 3, __DIR__ . '/debug.log');

//check if name exists
if (!isset($routes[$name])) {
    http_response_code(404);
    echo json_encode(['error' => 'not found']);
    return true; // true = "I've handled this request", so the server sends our response
}

//extract json body - cant use $_GET or $_POST get raw data from php://input
// ($_POST only understands HTML form posts, not a JSON body.)

$body = null;
$raw = file_get_contents('php://input');
if ($raw !== '') {
    // true = decode JSON objects into PHP associative arrays (like Python dicts)
    // instead of stdClass objects. Invalid JSON gives null.
    $body = json_decode($raw, true);
}

//run script using $body which may be null and turn result into json which gets sent back to requester
// $routes[$name] is a string like 'add_numbers'; adding ($body) calls the function with that name.
// Safe here because $name can only be one of the keys in $routes above.
try {
    echo json_encode($routes[$name]($body));
} catch (Throwable $e) {
    // Throwable catches both Exceptions and PHP Errors (e.g. TypeError),
    // like a bare `except Exception` in Python.
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
