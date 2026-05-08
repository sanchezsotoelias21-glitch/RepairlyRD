<?php

mysqli_report(MYSQLI_REPORT_OFF);

$databaseUrl = getenv('MYSQL_URL') ?: getenv('MYSQL_PUBLIC_URL') ?: getenv('DATABASE_URL') ?: '';

$host = getenv('DB_HOST') ?: getenv('MYSQLHOST') ?: getenv('MYSQL_HOST') ?: 'turntable.proxy.rlwy.net';
$rawPort = getenv('DB_PORT') ?: getenv('MYSQLPORT') ?: getenv('MYSQL_PORT') ?: '';
$port = (int)$rawPort;
$user = getenv('DB_USER') ?: getenv('MYSQLUSER') ?: getenv('MYSQL_USER') ?: 'root';
$password = getenv('DB_PASSWORD') ?: getenv('MYSQLPASSWORD') ?: getenv('MYSQL_PASSWORD') ?: 'wUeNcKAWLZqGgJwmXcKJwPgiQdeyTIgA';
$database = getenv('DB_NAME') ?: getenv('MYSQLDATABASE') ?: getenv('MYSQL_DATABASE') ?: 'taller_reparaciones';

if ($databaseUrl !== '') {
    $parts = parse_url($databaseUrl);
    if (is_array($parts)) {
        $host = $parts['host'] ?? $host;
        $port = isset($parts['port']) ? (int)$parts['port'] : $port;
        $user = isset($parts['user']) ? urldecode($parts['user']) : $user;
        $password = isset($parts['pass']) ? urldecode($parts['pass']) : $password;
        $database = isset($parts['path']) ? ltrim($parts['path'], '/') : $database;
    }
}

if ($port <= 0 && str_contains($host, 'proxy.rlwy.net')) {
    http_response_code(500);
    die('Error de conexión a MySQL: falta DB_PORT/MYSQLPORT. El host público de Railway (*.proxy.rlwy.net) necesita el puerto TCP público de MySQL.');
}

$conn = mysqli_init();
if ($conn === false) {
    http_response_code(500);
    die('Error de conexión: no se pudo inicializar mysqli.');
}

$conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 10);

$connected = $conn->real_connect($host, $user, $password, $database, $port ?: 3306);
if (!$connected) {
    http_response_code(500);
    die(
        'Error de conexión a MySQL. Revisa las variables DB_HOST, DB_PORT, DB_USER, DB_PASSWORD y DB_NAME en Railway. ' .
        'Intentando conectar a ' . $host . ':' . ($port ?: 3306) . '. ' .
        'Detalle: ' . $conn->connect_error
    );
}

$conn->set_charset('utf8mb4');

