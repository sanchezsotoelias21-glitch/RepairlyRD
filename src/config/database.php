<?php

$host = getenv('DB_HOST') ?: 'turntable.proxy.rlwy.net';
$port = (int)(getenv('DB_PORT') ?: 0);
$user = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASSWORD') ?: 'wUeNcKAWLZqGgJwmXcKJwPgiQdeyTIgA';
$database = getenv('DB_NAME') ?: 'taller_reparaciones';

$conn = mysqli_init();
if ($conn === false) {
    die('Error de conexión: no se pudo inicializar mysqli.');
}

if (!$conn->real_connect($host, $user, $password, $database, $port ?: null)) {
    die('Error de conexión: ' . mysqli_connect_error());
}

$conn->set_charset('utf8mb4');

?>
