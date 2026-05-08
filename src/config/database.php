<?php

$host = "turntable.proxy.rlwy.net";
$user = "root";
$password = "wUeNcKAWLZqGgJwmXcKJwPgiQdeyTIgA";
$database = "taller_reparaciones";

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die("❌ Error de conexión: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

?>