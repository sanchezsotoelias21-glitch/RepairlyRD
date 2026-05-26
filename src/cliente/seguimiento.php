<?php
require_once __DIR__ . '/../config/database.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="shortcut icon" href="/logo.ico" type="image/x-icon">
</head>
<body>
<?php

$codigo = $_GET['codigo'] ?? '';

if (empty($codigo)) {
    echo '<p style="color:#dc2626;text-align:center;">Por favor ingresa un código de orden.</p>';
    exit;
}

// Buscar la tabla de ordenes
$orden_table = pick_table($conn, ['orden_reparacion', 'Orden_Reparacion', 'reparacion', 'Reparacion', 'orden']);
if ($orden_table === '') {
    echo '<p style="color:#dc2626;text-align:center;">No se encontró la tabla de órdenes.</p>';
    exit;
}

$orden_cols = table_columns($conn, $orden_table);
$id_col = 'id_orden';
foreach (array_keys($orden_cols) as $k) {
    if (strcasecmp((string)$k, 'id_orden') === 0) {
        $id_col = $k;
        break;
    }
}

$codigo_col = repairly_pick_column($orden_cols, ['codigo_seguimiento', 'codigo']);
$estado_col = repairly_pick_column($orden_cols, ['id_estado_actual', 'estado']);
$equipo_col = repairly_pick_column($orden_cols, ['id_equipo']);

if (!$codigo_col) {
    echo '<p style="color:#dc2626;text-align:center;">No se encontró la columna de código en la tabla de órdenes.</p>';
    exit;
}

// Buscar la orden por código
$stmt = $conn->prepare("SELECT * FROM `{$orden_table}` WHERE `{$codigo_col}` = ? LIMIT 1");
$stmt->bind_param('s', $codigo);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo '<p style="color:#dc2626;text-align:center;">No se encontró ninguna orden con el código: ' . htmlspecialchars($codigo) . '</p>';
    exit;
}

$orden = $result->fetch_assoc();

// Obtener información del equipo
$equipo_info = '';
$equipo_table = pick_table($conn, ['equipo', 'Equipo']);
if ($equipo_table !== '' && $equipo_col) {
    $eq_stmt = $conn->prepare("SELECT * FROM `{$equipo_table}` WHERE id_equipo = ?");
    $eq_stmt->bind_param('i', $orden[$equipo_col]);
    $eq_stmt->execute();
    $eq_result = $eq_stmt->get_result();
    if ($eq_result->num_rows > 0) {
        $equipo = $eq_result->fetch_assoc();
        $equipo_info = $equipo['tipo'] . ' ' . $equipo['marca'] . ' ' . $equipo['modelo'];
    }
}

// Obtener información del estado
$estado_nombre = $orden[$estado_col] ?? 'Desconocido';

// Obtener información del cliente
$cliente_info = '';
$cliente_table = pick_table($conn, ['cliente', 'Cliente']);
if ($equipo_table !== '' && $cliente_table !== '') {
    $eq_cols = table_columns($conn, $equipo_table);
    $cli_col = repairly_pick_column($eq_cols, ['id_cliente']);
    if ($cli_col && isset($equipo[$cli_col])) {
        $cli_stmt = $conn->prepare("SELECT * FROM `{$cliente_table}` WHERE id_cliente = ?");
        $cli_stmt->bind_param('i', $equipo[$cli_col]);
        $cli_stmt->execute();
        $cli_result = $cli_stmt->get_result();
        if ($cli_result->num_rows > 0) {
            $cliente = $cli_result->fetch_assoc();
            $cliente_info = $cliente['nombre'];
        }
    }
}

// Determinar color del estado
$status_lower = strtolower($estado_nombre);
$bg_color = '#E3F2FD';
$text_color = '#2b7abc';
$border_color = '#2b7abc';

if (strpos($status_lower, 'falla') !== false) {
    $bg_color = '#FFEBEE';
    $text_color = '#FF4444';
    $border_color = '#FF4444';
} elseif (strpos($status_lower, 'garant') !== false) {
    $bg_color = '#F3E5F5';
    $text_color = '#7B4EC4';
    $border_color = '#7B4EC4';
} elseif (strpos($status_lower, 'listo') !== false || strpos($status_lower, 'entreg') !== false || strpos($status_lower, 'complet') !== false) {
    $bg_color = '#E8F5E9';
    $text_color = '#00AA44';
    $border_color = '#00AA44';
} elseif (strpos($status_lower, 'pend') !== false || strpos($status_lower, 'recib') !== false) {
    $bg_color = '#FFF3E0';
    $text_color = '#FF9500';
    $border_color = '#FF9500';
} elseif (strpos($status_lower, 'diagn') !== false) {
    $bg_color = '#F5F5F5';
    $text_color = '#424242';
    $border_color = '#424242';
}

// Mostrar resultado
?>
<div style="background:#fff;padding:24px;border-radius:8px;border:1px solid #e8eaef;box-shadow:0 2px 8px rgba(0,0,0,0.08);">
    <div style="margin-bottom:16px;padding-bottom:16px;border-bottom:1px solid #e8eaef;">
        <h4 style="margin:0 0 8px 0;color:#1a2744;font-size:16px;">Orden #<?= htmlspecialchars($orden[$id_col]) ?></h4>
        <div style="color:#6b7280;font-size:13px;">Código: <strong><?= htmlspecialchars($codigo) ?></strong></div>
    </div>
    
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
        <div>
            <div style="font-size:12px;color:#6b7280;margin-bottom:4px;">Cliente</div>
            <div style="font-weight:600;color:#1a2744;"><?= htmlspecialchars($cliente_info ?: 'No disponible') ?></div>
        </div>
        <div>
            <div style="font-size:12px;color:#6b7280;margin-bottom:4px;">Equipo</div>
            <div style="font-weight:600;color:#1a2744;"><?= htmlspecialchars($equipo_info ?: 'No disponible') ?></div>
        </div>
    </div>
    
    <div style="margin-bottom:16px;">
        <div style="font-size:12px;color:#6b7280;margin-bottom:4px;">Estado actual</div>
        <span style="display:inline-block;padding:6px 12px;background:<?= $bg_color ?>;color:<?= $text_color ?>;border:1px solid <?= $border_color ?>;border-radius:4px;font-size:13px;font-weight:600;">
            <?= htmlspecialchars($estado_nombre) ?>
        </span>
    </div>
    
    <?php if (isset($orden['fecha_ingreso'])): ?>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
        <div>
            <div style="font-size:12px;color:#6b7280;margin-bottom:4px;">Fecha de ingreso</div>
            <div style="color:#1a2744;"><?= date('d/m/Y', strtotime($orden['fecha_ingreso'])) ?></div>
        </div>
        <?php if (isset($orden['fecha_estimada_entrega'])): ?>
        <div>
            <div style="font-size:12px;color:#6b7280;margin-bottom:4px;">Fecha estimada de entrega</div>
            <div style="color:#1a2744;"><?= date('d/m/Y', strtotime($orden['fecha_estimada_entrega'])) ?></div>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>
