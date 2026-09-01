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

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$prefix = '/api/run/';

//compare start of uri with prefix if false serve static file i.e. return false
if (!str_starts_with($uri, $prefix)) {
    return false; // let the built-in server serve static files / 404
}

//set the header always the same
header('Content-Type: application/json');

//add the script pages
require_once __DIR__ . '/scripts/basic.php';
require_once __DIR__ . '/scripts/create_tables.php';
require_once __DIR__ . '/scripts/health.php';
require_once __DIR__ . '/scripts/list_manipulation.php';
require_once __DIR__ . '/scripts/role_crud.php';


$routes = [
    'basic/add_numbers' => 'add_numbers',
    'basic/add_strings' => 'add_strings',
    'basic/add_phrase' => 'add_phrase',
    'basic/string_func' => 'string_func',
    'list_manipulation/upload_fruits' => 'upload_fruits',
    'list_manipulation/count_fruit' => 'count_fruit',
    'health/health' => 'health',
    'create_tables/create_tables' => 'create_tables',
    'role_crud/add_role' => 'add_role',
    'role_crud/list_roles' => 'list_roles',
    'role_crud/update_role' => 'update_role',
    'role_crud/delete_role' => 'delete_role',

];


// Get the last part to check against list i.e. romve '/api/run/'
$name = substr($uri, strlen($prefix));
error_log("name = " . $name, 3, __DIR__ . '/debug.log');

//check if name exists
if (!isset($routes[$name])) {
    http_response_code(404);
    echo json_encode(['error' => 'not found']);
    return true;
}

//extract json body - cant use $_GET or $_POST 
$body = null;
$raw = file_get_contents('php://input');
if ($raw !== '') {
    $body = json_decode($raw, true);
}

//run script using $body which may be null and turn result into json which gets sent back to requester 
echo json_encode($routes[$name]($body));
