<?php
require_once __DIR__ . '/src/config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

$nombre = trim($_POST['nombre'] ?? '');
$telefono = trim($_POST['telefono'] ?? '');
$email = trim($_POST['email'] ?? '');
$direccion = trim($_POST['direccion'] ?? '');
$password = $_POST['password'] ?? '';
$equipo = trim($_POST['equipo'] ?? '');
$codigo_orden = trim($_POST['codigo_orden'] ?? '');

if (empty($nombre) || empty($telefono) || empty($password)) {
    echo json_encode(['success' => false, 'error' => 'Nombre, teléfono y contraseña son requeridos']);
    exit;
}

// Verificar si el email ya existe en usuarios
$check_email = $conn->prepare("SELECT id_usuario FROM usuarios WHERE email = ?");
$check_email->bind_param('s', $email);
$check_email->execute();
$result = $check_email->get_result();

if ($result && $result->num_rows > 0) {
    echo json_encode(['success' => false, 'error' => 'El email ya está registrado']);
    exit;
}

// Verificar si el teléfono ya existe en clientes
$check_telefono = $conn->prepare("SELECT id_cliente FROM cliente WHERE telefono = ?");
$check_telefono->bind_param('s', $telefono);
$check_telefono->execute();
$result_tel = $check_telefono->get_result();

if ($result_tel && $result_tel->num_rows > 0) {
    echo json_encode(['success' => false, 'error' => 'El teléfono ya está registrado como cliente']);
    exit;
}

// Iniciar transacción
$conn->begin_transaction();

try {
    // 1. Crear usuario con rol 'cliente'
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    $username = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $nombre)) . rand(100, 999);
    
    $stmt_usuario = $conn->prepare("INSERT INTO usuarios (nombre, email, telefono, password, rol, username) VALUES (?, ?, ?, ?, 'cliente', ?)");
    $stmt_usuario->bind_param('sssss', $nombre, $email, $telefono, $password_hash, $username);
    $stmt_usuario->execute();
    $id_usuario = $conn->insert_id;
    $stmt_usuario->close();
    
    // 2. Crear registro en tabla cliente
    $stmt_cliente = $conn->prepare("INSERT INTO cliente (nombre, telefono, email, direccion) VALUES (?, ?, ?, ?)");
    $stmt_cliente->bind_param('ssss', $nombre, $telefono, $email, $direccion);
    $stmt_cliente->execute();
    $id_cliente = $conn->insert_id;
    $stmt_cliente->close();
    
    $equipo_creado = false;
    
    // 3. Si se proporcionó equipo, crear registro en tabla equipo
    if (!empty($equipo)) {
        $stmt_equipo = $conn->prepare("INSERT INTO equipo (marca_modelo, id_cliente, estado) VALUES (?, ?, 'en_reparacion')");
        $stmt_equipo->bind_param('si', $equipo, $id_cliente);
        $stmt_equipo->execute();
        $stmt_equipo->close();
        $equipo_creado = true;
    }
    
    // 4. Si se proporcionó código de orden, actualizar la orden para asociarla con el cliente
    if (!empty($codigo_orden)) {
        // Buscar la tabla de ordenes
        $orden_table = pick_table($conn, ['orden_reparacion', 'Orden_Reparacion', 'reparacion', 'Reparacion', 'orden']);
        if ($orden_table !== '') {
            $orden_cols = table_columns($conn, $orden_table);
            $id_col = 'id_orden';
            foreach (array_keys($orden_cols) as $k) {
                if (strcasecmp((string)$k, 'id_orden') === 0) {
                    $id_col = $k;
                    break;
                }
            }
            
            $codigo_col = repairly_pick_column($orden_cols, ['codigo_seguimiento', 'codigo']);
            if ($codigo_col) {
                $stmt_orden = $conn->prepare("UPDATE `{$orden_table}` SET id_cliente = ? WHERE `{$codigo_col}` = ?");
                $stmt_orden->bind_param('is', $id_cliente, $codigo_orden);
                $stmt_orden->execute();
                $stmt_orden->close();
            }
        }
    }
    
    $conn->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Cliente creado exitosamente',
        'equipo_creado' => $equipo_creado,
        'id_usuario' => $id_usuario,
        'id_cliente' => $id_cliente
    ]);
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'error' => 'Error al crear cliente: ' . $e->getMessage()]);
}
?>
