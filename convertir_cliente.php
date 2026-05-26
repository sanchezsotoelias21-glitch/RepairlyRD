<?php
require_once __DIR__ . '/src/config/database.php';
require_once __DIR__ . '/includes/sql_helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

$nombre = trim($_POST['nombre'] ?? '');
$telefono = trim($_POST['telefono'] ?? '');
$email = trim($_POST['email'] ?? '');
$direccion = trim($_POST['direccion'] ?? '');
$equipo = trim($_POST['equipo'] ?? '');
$tipo_equipo = trim($_POST['tipo_equipo'] ?? '');
$problema = trim($_POST['problema'] ?? '');
$id_orden = trim($_POST['id_orden'] ?? '');

if (empty($nombre) || empty($telefono)) {
    echo json_encode(['success' => false, 'error' => 'Nombre y teléfono son requeridos']);
    exit;
}

// Verificar si el teléfono ya existe en clientes
$cliente_table = pick_table($conn, ['cliente', 'Cliente']);
if ($cliente_table === '') {
    echo json_encode(['success' => false, 'error' => 'No se encontró la tabla de clientes']);
    exit;
}

$check_telefono = $conn->prepare("SELECT id_cliente FROM `{$cliente_table}` WHERE telefono = ?");
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
    // 1. Crear registro en tabla cliente
    $cols = table_columns($conn, $cliente_table);
    $nombre_col = repairly_pick_column($cols, ['nombre', 'Nombre']);
    $telefono_col = repairly_pick_column($cols, ['telefono', 'Telefono']);
    $email_col = repairly_pick_column($cols, ['email', 'correo']);
    $direccion_col = repairly_pick_column($cols, ['direccion', 'address']);
    
    if (!$nombre_col || !$telefono_col) {
        echo json_encode(['success' => false, 'error' => 'Faltan columnas requeridas en la tabla cliente']);
        exit;
    }
    
    $insert_cols = [$nombre_col, $telefono_col];
    $insert_values = [$nombre, $telefono];
    $insert_types = 'ss';
    
    if ($email_col) {
        $insert_cols[] = $email_col;
        $insert_values[] = $email;
        $insert_types .= 's';
    }
    
    if ($direccion_col) {
        $insert_cols[] = $direccion_col;
        $insert_values[] = $direccion;
        $insert_types .= 's';
    }
    
    $sql = "INSERT INTO `{$cliente_table}` (`" . implode('`,`', $insert_cols) . "`) VALUES (" . str_repeat(',', count($insert_values) - 1) . ")";
    $sql = str_replace(',', ',?', $sql);
    
    $stmt_cliente = $conn->prepare($sql);
    $stmt_cliente->bind_param($insert_types, ...$insert_values);
    $stmt_cliente->execute();
    $id_cliente = $conn->insert_id;
    $stmt_cliente->close();
    
    $equipo_creado = false;
    
    // 3. Si se proporcionó equipo, crear registro en tabla equipo
    if (!empty($equipo)) {
        $stmt_equipo = $conn->prepare("INSERT INTO equipo (marca_modelo, tipo, id_cliente, estado, problema) VALUES (?, ?, ?, 'en_reparacion', ?)");
        $stmt_equipo->bind_param('ssis', $equipo, $tipo_equipo, $id_cliente, $problema);
        $stmt_equipo->execute();
        $stmt_equipo->close();
        $equipo_creado = true;
    }
    
    // 4. Si se proporcionó id_orden, actualizar la orden para asociarla con el cliente
    if (!empty($id_orden)) {
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
            
            $stmt_orden = $conn->prepare("UPDATE `{$orden_table}` SET id_cliente = ? WHERE `{$id_col}` = ?");
            $stmt_orden->bind_param('ii', $id_cliente, $id_orden);
            $stmt_orden->execute();
            $stmt_orden->close();
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
