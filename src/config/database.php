<?php

mysqli_report(MYSQLI_REPORT_OFF);

function env_first(array $keys, ?string $default = null): ?string
{
    foreach ($keys as $key) {
        $val = getenv($key);
        if ($val !== false && $val !== '') {
            return $val;
        }
        if (isset($_ENV[$key]) && is_string($_ENV[$key]) && $_ENV[$key] !== '') {
            return $_ENV[$key];
        }
    }
    return $default;
}

$databaseUrl = env_first(['MYSQL_URL', 'MYSQL_PUBLIC_URL', 'DATABASE_URL'], '');

$host = env_first(['DB_HOST', 'MYSQLHOST', 'MYSQL_HOST', 'DATABASE_HOST', 'RAILWAY_TCP_PROXY_DOMAIN'], '127.0.0.1');
$rawPort = env_first(['DB_PORT', 'MYSQLPORT', 'MYSQL_PORT', 'DATABASE_PORT', 'RAILWAY_TCP_PROXY_PORT'], '3306');
$port = (int)$rawPort;
$user = env_first(['DB_USER', 'MYSQLUSER', 'MYSQL_USER', 'DATABASE_USER', 'RAILWAY_DATABASE_USERNAME'], 'root');
$password = env_first(
    ['DB_PASS', 'DB_PASSWORD', 'MYSQLPASSWORD', 'MYSQL_PASSWORD', 'DATABASE_PASSWORD', 'RAILWAY_DATABASE_PASSWORD'],
    ''
);
$database = env_first(['DB_NAME', 'MYSQLDATABASE', 'MYSQL_DATABASE', 'DATABASE_NAME', 'RAILWAY_DATABASE_NAME'], 'repairlyrd');

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

$conn = mysqli_init();
if ($conn === false) {
    http_response_code(500);
    die('Error de conexiÃ³n: no se pudo inicializar mysqli.');
}

$conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 10);

$connected = $conn->real_connect($host, $user, $password, $database, $port ?: 3306);
if (!$connected) {
    http_response_code(500);
    die(
        'Error de conexiÃ³n a MySQL. Revisa las variables DB_HOST, DB_PORT, DB_USER, DB_PASS/DB_PASSWORD y DB_NAME. ' .
        'Intentando conectar a ' . $host . ':' . ($port ?: 3306) . '. ' .
        'Detalle: ' . $conn->connect_error
    );
}

$conn->set_charset('utf8mb4');

