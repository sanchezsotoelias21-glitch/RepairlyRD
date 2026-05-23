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
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            margin-bottom: 20px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .header h1 {
            color: #333;
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .codigo-badge {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 10px 25px;
            border-radius: 30px;
            font-size: 18px;
            font-weight: bold;
            margin-top: 10px;
        }
        
        .error {
            background: #fee;
            border: 1px solid #fcc;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            color: #c33;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .info-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
        }
        
        .info-label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
        }
        
        .info-value {
            font-size: 16px;
            color: #333;
            font-weight: 500;
        }
        
        .progress-container {
            margin: 30px 0;
        }
        
        .progress-title {
            text-align: center;
            font-size: 18px;
            color: #333;
            margin-bottom: 20px;
            font-weight: 600;
        }
        
        .progress-steps {
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            margin: 40px 20px;
        }
        
        .progress-line {
            position: absolute;
            top: 25px;
            left: 0;
            right: 0;
            height: 4px;
            background: #e0e0e0;
            z-index: 1;
        }
        
        .progress-line-filled {
            position: absolute;
            top: 25px;
            left: 0;
            height: 4px;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            z-index: 2;
            transition: width 0.5s ease;
        }
        
        .step {
            position: relative;
            z-index: 3;
            text-align: center;
            flex: 1;
        }
        
        .step-circle {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: #e0e0e0;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            font-size: 24px;
            transition: all 0.3s ease;
        }
        
        .step.active .step-circle {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            transform: scale(1.1);
        }
        
        .step.completed .step-circle {
            background: #4caf50;
        }
        
        .step-label {
            font-size: 12px;
            color: #666;
            font-weight: 500;
        }
        
        .step.active .step-label {
            color: #667eea;
            font-weight: 700;
        }
        
        .step.completed .step-label {
            color: #4caf50;
        }
        
        .observaciones {
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 10px;
            padding: 15px;
            margin-top: 20px;
        }
        
        .observaciones-title {
            font-weight: 600;
            color: #856404;
            margin-bottom: 10px;
        }
        
        .observaciones-text {
            color: #856404;
            line-height: 1.6;
        }
        
        .footer {
            text-align: center;
            margin-top: 30px;
            color: white;
            font-size: 14px;
        }
        
        .search-form {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .search-input {
            flex: 1;
            padding: 12px 20px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 16px;
        }
        
        .search-input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .search-button {
            padding: 12px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }
        
        .search-button:hover {
            transform: translateY(-2px);
        }
        
        @media (max-width: 600px) {
            .progress-steps {
                margin: 40px 10px;
            }
            
            .step-circle {
                width: 40px;
                height: 40px;
                font-size: 18px;
            }
            
            .step-label {
                font-size: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if (isset($error)): ?>
            <div class="card">
                <div class="error">
                    <h2>❌ Error</h2>
                    <p><?php echo htmlspecialchars($error); ?></p>
                </div>
                <div style="margin-top: 30px;">
                    <form method="get" class="search-form">
                        <input type="text" name="codigo" class="search-input" placeholder="Ingresa tu código (ej: FC-001)" required>
                        <button type="submit" class="search-button">Buscar</button>
                    </form>
                </div>
            </div>
        <?php elseif (isset($orden_info)): ?>
            <div class="card">
                <div class="header">
                    <h1>🔧 Seguimiento de Reparación</h1>
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
                        <div class="observaciones-title">📝 Observaciones</div>
                        <div class="observaciones-text">
                            <?php echo nl2br(htmlspecialchars($orden_info['orden'][$obs_col])); ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="footer">
                <p>¿Tienes preguntas? Contáctanos al taller</p>
                <p style="margin-top: 10px;">© 2024 RepairlyRD - Sistema de Gestión de Reparaciones</p>
            </div>
        <?php else: ?>
            <div class="card">
                <div class="header">
                    <h1>🔧 Seguimiento de Reparación</h1>
                    <p style="color: #666; margin-top: 10px;">Ingresa tu código de seguimiento para ver el estado de tu reparación</p>
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
