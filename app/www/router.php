<?php
// Front controller for PHP's built-in server: php -S 0.0.0.0:8080 router.php
// Mirrors python-stack/app/www/router.py: /api/run/<file>/<func> loads
// scripts/<file>.php with require and calls <func>(body).

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$prefix = '/api/run/';

if (strncmp($uri, $prefix, strlen($prefix)) !== 0) {
    return false; // let the built-in server serve static files / 404
}

header('Content-Type: application/json');

$name = substr($uri, strlen($prefix));
[$file, $func] = array_pad(explode('/', $name, 2), 2, null);

$scriptPath = __DIR__ . "/scripts/{$file}.php";

if ($file === null || $func === null || !is_file($scriptPath)) {
    http_response_code(404);
    echo json_encode(['error' => 'not found']);
    return true;
}

require $scriptPath;

if (!function_exists($func)) {
    http_response_code(404);
    echo json_encode(['error' => 'not found']);
    return true;
}

$body = null;
$raw = file_get_contents('php://input');
if ($raw !== '') {
    $body = json_decode($raw, true);
}

echo json_encode($func($body));
