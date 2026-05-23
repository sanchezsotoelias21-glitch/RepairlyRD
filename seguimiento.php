<?php
/**
 * Página pública de seguimiento de órdenes de reparación
 * Permite a los clientes ver el estado de su reparación en tiempo real
 */

require_once 'includes/conexion.php';
require_once 'includes/sql_helpers.php';

// Obtener código de seguimiento
$codigo = isset($_GET['codigo']) ? trim($_GET['codigo']) : '';

if (empty($codigo)) {
    $error = 'No se proporcionó código de seguimiento';
} else {
    // Buscar la orden por código de seguimiento
    $orden_tbl = pick_table($conn, ['orden_reparacion', 'Orden_Reparacion', 'reparacion', 'Reparacion', 'orden']);
    
    if ($orden_tbl === '') {
        $error = 'No se encontró la tabla de órdenes';
    } else {
        $orden_cols = table_columns($conn, $orden_tbl);
        $codigoCol = null;
        foreach (array_keys($orden_cols) as $k) {
            if (strcasecmp((string)$k, 'codigo_seguimiento') === 0) {
                $codigoCol = $k;
                break;
            }
        }
        
        if ($codigoCol === null) {
            $error = 'La tabla de órdenes no tiene columna de código de seguimiento';
        } else {
            $stmt = $conn->prepare("SELECT * FROM `{$orden_tbl}` WHERE `{$codigoCol}` = ? LIMIT 1");
            if ($stmt) {
                $stmt->bind_param('s', $codigo);
                $stmt->execute();
                $res = $stmt->get_result();
                $orden = $res ? $res->fetch_assoc() : null;
                $stmt->close();
                
                if (!$orden) {
                    $error = 'No se encontró ninguna orden con ese código de seguimiento';
                }
            } else {
                $error = 'Error al buscar la orden';
            }
        }
    }
}

// Si hay orden, obtener información relacionada
$orden_info = null;
if (isset($orden) && is_array($orden)) {
    // Obtener nombre del estado
    $estados_tbl = pick_table($conn, ['estado_servicio', 'estado', 'Estado_Servicio']);
    $estado_nombre = 'Desconocido';
    if ($estados_tbl !== '') {
        $id_estado_col = null;
        foreach (array_keys($orden_cols) as $k) {
            if (strcasecmp((string)$k, 'id_estado_actual') === 0) {
                $id_estado_col = $k;
                break;
            }
        }
        if ($id_estado_col !== null) {
            $estado_id = (int)($orden[$id_estado_col] ?? 0);
            $stmt = $conn->prepare("SELECT nombre_estado FROM `{$estados_tbl}` WHERE id_estado = ? LIMIT 1");
            if ($stmt) {
                $stmt->bind_param('i', $estado_id);
                $stmt->execute();
                $res = $stmt->get_result();
                $row = $res ? $res->fetch_assoc() : null;
                if ($row) {
                    $estado_nombre = $row['nombre_estado'] ?? 'Desconocido';
                }
                $stmt->close();
            }
        }
    }
    
    // Obtener información del equipo y cliente
    $equipo_tbl = pick_table($conn, ['equipo', 'Equipo']);
    $cliente_tbl = pick_table($conn, ['cliente', 'Cliente']);
    $tecnico_tbl = pick_table($conn, ['tecnico', 'Tecnico']);
    
    $equipo_info = null;
    $cliente_info = null;
    $tecnico_info = null;
    
    // Buscar equipo
    $id_equipo_col = null;
    foreach (array_keys($orden_cols) as $k) {
        if (strcasecmp((string)$k, 'id_equipo') === 0) {
            $id_equipo_col = $k;
            break;
        }
    }
    
    if ($equipo_tbl !== '' && $id_equipo_col !== null) {
        $equipo_id = (int)($orden[$id_equipo_col] ?? 0);
        if ($equipo_id > 0) {
            $eq_cols = table_columns($conn, $equipo_tbl);
            $eq_id_col = 'id_equipo';
            foreach (array_keys($eq_cols) as $k) {
                if (strcasecmp((string)$k, 'id_equipo') === 0) {
                    $eq_id_col = $k;
                    break;
                }
            }
            
            $stmt = $conn->prepare("SELECT * FROM `{$equipo_tbl}` WHERE `{$eq_id_col}` = ? LIMIT 1");
            if ($stmt) {
                $stmt->bind_param('i', $equipo_id);
                $stmt->execute();
                $res = $stmt->get_result();
                $equipo_info = $res ? $res->fetch_assoc() : null;
                $stmt->close();
                
                // Buscar cliente desde el equipo
                if ($equipo_info && $cliente_tbl !== '') {
                    $cli_cols = table_columns($conn, $cliente_tbl);
                    $cli_id_col = 'id_cliente';
                    foreach (array_keys($cli_cols) as $k) {
                        if (strcasecmp((string)$k, 'id_cliente') === 0) {
                            $cli_id_col = $k;
                            break;
                        }
                    }
                    
                    $eq_cli_col = null;
                    foreach (array_keys($eq_cols) as $k) {
                        if (strcasecmp((string)$k, 'id_cliente') === 0) {
                            $eq_cli_col = $k;
                            break;
                        }
                    }
                    
                    if ($eq_cli_col !== null) {
                        $cliente_id = (int)($equipo_info[$eq_cli_col] ?? 0);
                        if ($cliente_id > 0) {
                            $stmt = $conn->prepare("SELECT * FROM `{$cliente_tbl}` WHERE `{$cli_id_col}` = ? LIMIT 1");
                            if ($stmt) {
                                $stmt->bind_param('i', $cliente_id);
                                $stmt->execute();
                                $res = $stmt->get_result();
                                $cliente_info = $res ? $res->fetch_assoc() : null;
                                $stmt->close();
                            }
                        }
                    }
                }
            }
        }
    }
    
    // Buscar técnico
    $id_tecnico_col = null;
    foreach (array_keys($orden_cols) as $k) {
        if (strcasecmp((string)$k, 'id_tecnico') === 0) {
            $id_tecnico_col = $k;
            break;
        }
    }
    
    if ($tecnico_tbl !== '' && $id_tecnico_col !== null) {
        $tecnico_id = (int)($orden[$id_tecnico_col] ?? 0);
        if ($tecnico_id > 0) {
            $tec_cols = table_columns($conn, $tecnico_tbl);
            $tec_id_col = 'id_tecnico';
            foreach (array_keys($tec_cols) as $k) {
                if (strcasecmp((string)$k, 'id_tecnico') === 0) {
                    $tec_id_col = $k;
                    break;
                }
            }
            
            $stmt = $conn->prepare("SELECT * FROM `{$tecnico_tbl}` WHERE `{$tec_id_col}` = ? LIMIT 1");
            if ($stmt) {
                $stmt->bind_param('i', $tecnico_id);
                $stmt->execute();
                $res = $stmt->get_result();
                $tecnico_info = $res ? $res->fetch_assoc() : null;
                $stmt->close();
            }
        }
    }
    
    $orden_info = [
        'orden' => $orden,
        'estado_nombre' => $estado_nombre,
        'equipo' => $equipo_info,
        'cliente' => $cliente_info,
        'tecnico' => $tecnico_info,
    ];
}

// Función para determinar el paso actual en la línea de progreso
function get_paso_progreso($estado_nombre) {
    $estados_map = [
        'recibido' => 1,
        'en diagnóstico' => 2,
        'diagnóstico' => 2,
        'en reparación' => 3,
        'reparación' => 3,
        'en espera de piezas' => 3,
        'en pruebas' => 4,
        'pruebas' => 4,
        'listo' => 5,
        'listo para entrega' => 5,
        'entregado' => 6,
    ];
    
    $estado_lower = strtolower(trim($estado_nombre));
    foreach ($estados_map as $key => $value) {
        if (strpos($estado_lower, $key) !== false) {
            return $value;
        }
    }
    return 1; // Por defecto: recibido
}

// Pasos de la línea de progreso
$pasos = [
    1 => ['nombre' => 'Recibido', 'icono' => '📥'],
    2 => ['nombre' => 'Diagnóstico', 'icono' => '🔍'],
    3 => ['nombre' => 'Reparación', 'icono' => '🔧'],
    4 => ['nombre' => 'Pruebas', 'icono' => '✅'],
    5 => ['nombre' => 'Listo', 'icono' => '📦'],
    6 => ['nombre' => 'Entregado', 'icono' => '🚚'],
];

$paso_actual = isset($orden_info) ? get_paso_progreso($orden_info['estado_nombre']) : 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seguimiento de Orden - RepairlyRD</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.19.0/dist/tabler-icons.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
            background: #F8F7F5;
            color: #1C1A17;
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 900px;
            margin: 0 auto;
        }
        
        .card {
            background: #fff;
            border: 0.5px solid #D0CCC6;
            border-radius: 8px;
            padding: 24px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            margin-bottom: 16px;
        }
        
        .card-header {
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 0.5px solid #EDECEA;
        }
        
        .card-title {
            font-size: 18px;
            font-weight: 600;
            color: #1C1A17;
            margin-bottom: 4px;
        }
        
        .card-sub {
            font-size: 13px;
            color: #6B6560;
        }
        
        .header {
            text-align: center;
            margin-bottom: 24px;
        }
        
        .header h1 {
            color: #1C1A17;
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 12px;
        }
        
        .codigo-badge {
            display: inline-block;
            background: #2b7abc;
            color: white;
            padding: 8px 20px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
        }
        
        .error {
            background: #FFEBEE;
            border: 0.5px solid #FFCDD2;
            border-radius: 6px;
            padding: 16px;
            text-align: center;
            color: #C62828;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 12px;
            margin-bottom: 20px;
        }
        
        .info-item {
            background: #F8F7F5;
            padding: 12px;
            border-radius: 6px;
            border: 0.5px solid #EDECEA;
        }
        
        .info-label {
            font-size: 11px;
            color: #6B6560;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
            font-weight: 500;
        }
        
        .info-value {
            font-size: 14px;
            color: #1C1A17;
            font-weight: 500;
        }
        
        .progress-container {
            margin: 24px 0;
        }
        
        .progress-title {
            text-align: center;
            font-size: 14px;
            color: #1C1A17;
            margin-bottom: 16px;
            font-weight: 500;
        }
        
        .progress-steps {
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            margin: 32px 16px;
        }
        
        .progress-line {
            position: absolute;
            top: 20px;
            left: 0;
            right: 0;
            height: 3px;
            background: #EDECEA;
            z-index: 1;
        }
        
        .progress-line-filled {
            position: absolute;
            top: 20px;
            left: 0;
            height: 3px;
            background: #2b7abc;
            z-index: 2;
            transition: width 0.3s ease;
        }
        
        .step {
            position: relative;
            z-index: 3;
            text-align: center;
            flex: 1;
        }
        
        .step-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #EDECEA;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 8px;
            font-size: 18px;
            transition: all 0.2s ease;
        }
        
        .step.active .step-circle {
            background: #2b7abc;
            color: #fff;
        }
        
        .step.completed .step-circle {
            background: #00AA44;
            color: #fff;
        }
        
        .step-label {
            font-size: 11px;
            color: #6B6560;
            font-weight: 500;
        }
        
        .step.active .step-label {
            color: #2b7abc;
            font-weight: 600;
        }
        
        .step.completed .step-label {
            color: #00AA44;
            font-weight: 600;
        }
        
        .observaciones {
            background: #FFF3E0;
            border: 0.5px solid #FFE0B2;
            border-radius: 6px;
            padding: 12px;
            margin-top: 16px;
        }
        
        .observaciones-title {
            font-weight: 600;
            color: #E65100;
            margin-bottom: 8px;
            font-size: 13px;
        }
        
        .observaciones-text {
            color: #BF360C;
            line-height: 1.5;
            font-size: 13px;
        }
        
        .footer {
            text-align: center;
            margin-top: 24px;
            color: #6B6560;
            font-size: 12px;
        }
        
        .search-form {
            display: flex;
            gap: 8px;
            margin-bottom: 16px;
        }
        
        .search-input {
            flex: 1;
            padding: 10px 16px;
            border: 0.5px solid #D0CCC6;
            border-radius: 6px;
            font-size: 14px;
            background: #fff;
        }
        
        .search-input:focus {
            outline: none;
            border-color: #2b7abc;
        }
        
        .search-button {
            padding: 10px 24px;
            background: #2b7abc;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        .search-button:hover {
            background: #1a5a9e;
        }
        
        @media (max-width: 600px) {
            .progress-steps {
                margin: 32px 8px;
            }
            
            .step-circle {
                width: 32px;
                height: 32px;
                font-size: 14px;
            }
            
            .step-label {
                font-size: 10px;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if (isset($error)): ?>
            <div class="card">
                <div class="card-header">
                    <div class="card-title">Seguimiento de Reparación</div>
                    <div class="card-sub">RepairlyRD - Sistema de Gestión</div>
                </div>
                <div class="error">
                    <i class="ti ti-alert-circle" style="font-size: 24px; margin-bottom: 8px; display: block;"></i>
                    <p><?php echo htmlspecialchars($error); ?></p>
                </div>
                <div style="margin-top: 20px;">
                    <form method="get" class="search-form">
                        <input type="text" name="codigo" class="search-input" placeholder="Ingresa tu código (ej: FC-001)" required>
                        <button type="submit" class="search-button">Buscar</button>
                    </form>
                </div>
            </div>
        <?php elseif (isset($orden_info)): ?>
            <div class="card">
                <div class="card-header">
                    <div class="card-title">Seguimiento de Reparación</div>
                    <div class="card-sub">RepairlyRD - Sistema de Gestión</div>
                </div>
                <div class="header">
                    <div class="codigo-badge">
                        <?php echo htmlspecialchars($codigo); ?>
                    </div>
                </div>
                
                <div class="progress-container">
                    <div class="progress-title">
                        Estado actual: <strong><?php echo htmlspecialchars($orden_info['estado_nombre']); ?></strong>
                    </div>
                    
                    <div class="progress-steps">
                        <div class="progress-line"></div>
                        <div class="progress-line-filled" style="width: <?php echo (($paso_actual - 1) / 5) * 100; ?>%;"></div>
                        
                        <?php foreach ($pasos as $num => $paso): ?>
                            <div class="step <?php 
                                echo $num < $paso_actual ? 'completed' : ($num == $paso_actual ? 'active' : ''); 
                            ?>">
                                <div class="step-circle">
                                    <?php echo $paso['icono']; ?>
                                </div>
                                <div class="step-label">
                                    <?php echo htmlspecialchars($paso['nombre']); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="info-grid">
                    <?php if ($orden_info['cliente']): ?>
                        <div class="info-item">
                            <div class="info-label">Cliente</div>
                            <div class="info-value">
                                <?php 
                                    $nombre_col = repairly_pick_column(table_columns($conn, $cliente_tbl), ['nombre', 'name', 'nombre_cliente']);
                                    echo htmlspecialchars($orden_info['cliente'][$nombre_col] ?? 'N/A');
                                ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($orden_info['equipo']): ?>
                        <div class="info-item">
                            <div class="info-label">Equipo</div>
                            <div class="info-value">
                                <?php 
                                    $tipo = $orden_info['equipo']['tipo'] ?? '';
                                    $marca = $orden_info['equipo']['marca'] ?? '';
                                    $modelo = $orden_info['equipo']['modelo'] ?? '';
                                    echo htmlspecialchars(trim("$tipo $marca $modelo"));
                                ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($orden_info['tecnico']): ?>
                        <div class="info-item">
                            <div class="info-label">Técnico</div>
                            <div class="info-value">
                                <?php 
                                    $nombre_col = repairly_pick_column(table_columns($conn, $tecnico_tbl), ['nombre', 'name', 'nombre_tecnico']);
                                    echo htmlspecialchars($orden_info['tecnico'][$nombre_col] ?? 'N/A');
                                ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php 
                        $fecha_ingreso_col = null;
                        foreach (array_keys($orden_cols) as $k) {
                            if (strcasecmp((string)$k, 'fecha_ingreso') === 0) {
                                $fecha_ingreso_col = $k;
                                break;
                            }
                        }
                        if ($fecha_ingreso_col && !empty($orden_info['orden'][$fecha_ingreso_col])): 
                    ?>
                        <div class="info-item">
                            <div class="info-label">Fecha de Ingreso</div>
                            <div class="info-value">
                                <?php echo htmlspecialchars(substr($orden_info['orden'][$fecha_ingreso_col], 0, 10)); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php 
                        $fecha_est_col = null;
                        foreach (array_keys($orden_cols) as $k) {
                            if (strcasecmp((string)$k, 'fecha_estimada_entrega') === 0) {
                                $fecha_est_col = $k;
                                break;
                            }
                        }
                        if ($fecha_est_col && !empty($orden_info['orden'][$fecha_est_col])): 
                    ?>
                        <div class="info-item">
                            <div class="info-label">Fecha Estimada</div>
                            <div class="info-value">
                                <?php echo htmlspecialchars(substr($orden_info['orden'][$fecha_est_col], 0, 10)); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php 
                        $costo_col = null;
                        foreach (array_keys($orden_cols) as $k) {
                            if (strcasecmp((string)$k, 'costo_total') === 0) {
                                $costo_col = $k;
                                break;
                            }
                        }
                        if ($costo_col): 
                    ?>
                        <div class="info-item">
                            <div class="info-label">Costo Total</div>
                            <div class="info-value">
                                $<?php echo number_format((float)($orden_info['orden'][$costo_col] ?? 0), 2); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php 
                    $obs_col = null;
                    foreach (array_keys($orden_cols) as $k) {
                        if (strcasecmp((string)$k, 'observaciones') === 0) {
                            $obs_col = $k;
                            break;
                        }
                    }
                    if ($obs_col && !empty($orden_info['orden'][$obs_col])): 
                ?>
                    <div class="observaciones">
                        <div class="observaciones-title"><i class="ti ti-note"></i> Observaciones</div>
                        <div class="observaciones-text">
                            <?php echo nl2br(htmlspecialchars($orden_info['orden'][$obs_col])); ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="footer">
                <p>¿Tienes preguntas? Contáctanos al taller</p>
                <p style="margin-top: 8px;">© 2024 RepairlyRD - Sistema de Gestión de Reparaciones</p>
            </div>
        <?php else: ?>
            <div class="card">
                <div class="card-header">
                    <div class="card-title">Seguimiento de Reparación</div>
                    <div class="card-sub">RepairlyRD - Sistema de Gestión</div>
                </div>
                <div class="header">
                    <h1><i class="ti ti-clipboard-list"></i> Seguimiento de Reparación</h1>
                    <p style="color: #6B6560; margin-top: 8px; font-size: 14px;">Ingresa tu código de seguimiento para ver el estado de tu reparación</p>
                </div>
                
                <form method="get" class="search-form">
                    <input type="text" name="codigo" class="search-input" placeholder="Ingresa tu código (ej: FC-001)" required>
                    <button type="submit" class="search-button">Buscar</button>
                </form>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
