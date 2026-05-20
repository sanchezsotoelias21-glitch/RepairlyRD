<?php

// Vercel entrypoint (Serverless Function).
// Routes every request to the existing PHP app entrypoints.

$root = realpath(__DIR__ . '/..');
if ($root === false) {
    http_response_code(500);
    echo 'Server misconfigured.';
    exit;
}

$uri = $_SERVER['REQUEST_URI'] ?? '/';
if (!is_string($uri) || $uri === '') {
    $uri = '/';
}

$path = parse_url($uri, PHP_URL_PATH);
if (!is_string($path) || $path === '') {
    $path = '/';
}

// Block direct access to internal folders even if a route slips through.
if (preg_match('~^/(includes|sql|data)(/|$)~', $path) === 1) {
    http_response_code(404);
    exit;
}

// Allow calling specific root PHP files directly (e.g. /login.php).
// Otherwise fall back to the app router in index.php.
$allowed = [
    '/index.php' => $root . DIRECTORY_SEPARATOR . 'index.php',
    '/login.php' => $root . DIRECTORY_SEPARATOR . 'login.php',
    '/portal.php' => $root . DIRECTORY_SEPARATOR . 'portal.php',
    '/health.php' => $root . DIRECTORY_SEPARATOR . 'health.php',
    '/ai_assistant.php' => $root . DIRECTORY_SEPARATOR . 'ai_assistant.php',
];

if (isset($allowed[$path]) && is_file($allowed[$path])) {
    require $allowed[$path];
    exit;
}

require $root . DIRECTORY_SEPARATOR . 'index.php';

