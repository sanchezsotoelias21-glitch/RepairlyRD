<?php

$host = $_ENV['turntable.proxy.rlwy.net'];
$user = $_ENV['root'];
$pass = $_ENV['wUeNcKAWLZqGgJwmXcKJwPgiQdeyTIgA'];
$db   = $_ENV['taller_reparaciones'];
$port = $_ENV['43024'];

$conn = new mysqli($host, $user, $pass, $db, $port);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}