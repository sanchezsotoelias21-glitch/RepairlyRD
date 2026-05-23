<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../includes/sql_helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

// Obtener datos del formulario
$nombre = trim($_POST['nombre'] ?? '');
$email = trim($_POST['email'] ?? '');
$problema = trim($_POST['problema'] ?? '');

if (empty($nombre) || empty($email) || empty($problema)) {
    http_response_code(400);
    echo json_encode(['error' => 'Todos los campos son requeridos']);
    exit;
}

// Determinar el nombre de la tabla de notificaciones
$nt = pick_table($conn, ['notificacion', 'Notificacion']);
if ($nt === '') {
    http_response_code(500);
    echo json_encode(['error' => 'Tabla de notificaciones no encontrada']);
    exit;
}

// Obtener columnas de la tabla
$nc = table_columns($conn, $nt);

// Preparar el mensaje
$mensaje = "Nuevo mensaje de contacto de {$nombre} ({$email}): {$problema}";
$tipo = 'contacto';
$estado = 'pendiente';
$fecha_envio = date('Y-m-d H:i:s');

// Construir la consulta INSERT dinámicamente según las columnas disponibles
$columns = [];
$values = [];
$types = '';
$params = [];

if (isset($nc['tipo'])) {
    $columns[] = 'tipo';
    $values[] = '?';
    $types .= 's';
    $params[] = $tipo;
}

if (isset($nc['mensaje'])) {
    $columns[] = 'mensaje';
    $values[] = '?';
    $types .= 's';
    $params[] = $mensaje;
}

if (isset($nc['estado'])) {
    $columns[] = 'estado';
    $values[] = '?';
    $types .= 's';
    $params[] = $estado;
}

if (isset($nc['fecha_envio'])) {
    $columns[] = 'fecha_envio';
    $values[] = '?';
    $types .= 's';
    $params[] = $fecha_envio;
}

if (empty($columns)) {
    http_response_code(500);
    echo json_encode(['error' => 'No se encontraron columnas válidas en la tabla de notificaciones']);
    exit;
}

$sql = "INSERT INTO `{$nt}` (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $values) . ")";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al preparar la consulta: ' . $conn->error]);
    exit;
}

$stmt->bind_param($types, ...$params);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Mensaje enviado correctamente']);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Error al guardar el mensaje: ' . $stmt->error]);
}

$stmt->close();
