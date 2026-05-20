<?php

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

// Vercel: define these in Project Settings → Environment Variables.
// - DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT (optional)
// Backwards-compatible: also accepts common MySQL provider var names.
$host = env_first(['DB_HOST', 'MYSQLHOST', 'MYSQL_HOST', 'DATABASE_HOST', 'RAILWAY_TCP_PROXY_DOMAIN'], '127.0.0.1');
$user = env_first(['DB_USER', 'MYSQLUSER', 'MYSQL_USER', 'DATABASE_USER', 'RAILWAY_DATABASE_USERNAME'], 'root');
$pass = env_first(['DB_PASS', 'MYSQLPASSWORD', 'MYSQL_PASSWORD', 'DATABASE_PASSWORD', 'RAILWAY_DATABASE_PASSWORD'], '');
$db   = env_first(['DB_NAME', 'MYSQLDATABASE', 'MYSQL_DATABASE', 'DATABASE_NAME', 'RAILWAY_DATABASE_NAME'], 'repairlyrd');
$port = (int)(env_first(['DB_PORT', 'MYSQLPORT', 'MYSQL_PORT', 'DATABASE_PORT', 'RAILWAY_TCP_PROXY_PORT'], '3306'));

$conn = new mysqli($host, $user, $pass, $db, $port);

if ($conn->connect_error) {
    die('Error de conexiÃ³n: ' . $conn->connect_error);
}
