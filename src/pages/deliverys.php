<?php

/** @var mysqli $conn */
require_once __DIR__ . '/../../includes/ui_helper.php';

// Función para obtener coordenadas usando OpenStreetMap/Nominatim (gratis)
function getCoordinatesFromAddress($address) {
    $address = urlencode($address);
    $url = "https://nominatim.openstreetmap.org/search?format=json&q={$address}&limit=1";
    
    $options = [
        'http' => [
            'header' => "User-Agent: RepairlyRD/1.0\r\n",
            'method' => 'GET',
            'timeout' => 10
        ]
    ];
    
    $context = stream_context_create($options);
    $response = @file_get_contents($url, false, $context);
    
    if ($response) {
        $data = json_decode($response, true);
        if (!empty($data) && isset($data[0])) {
            return [
                'lat' => floatval($data[0]['lat']),
                'lon' => floatval($data[0]['lon'])
            ];
        }
    }
    
    return null;
}

// Función para enviar notificación por WhatsApp sobre cambios de delivery
function sendDeliveryNotification($codigoTracking, $estadoAnterior, $estadoNuevo) {
    $sid = getenv('TWILIO_SID') ?: 'YOUR_TWILIO_SID';
    $token = getenv('TWILIO_TOKEN') ?: 'YOUR_TWILIO_TOKEN';
    $adminPhone = getenv('ADMIN_WHATSAPP') ?: 'whatsapp:+18295921607';
    
    $mensaje = "🚚 Actualización de Delivery\n\n";
    $mensaje .= "📋 Código Tracking: $codigoTracking\n";
    $mensaje .= "📍 Estado anterior: $estadoAnterior\n";
    $mensaje .= "✅ Estado nuevo: $estadoNuevo\n";
    $mensaje .= "\n📢 El estado del delivery ha sido actualizado";
    
    $url = "https://api.twilio.com/2010-04-01/Accounts/$sid/Messages.json";
    
    $data = [
        'From' => 'whatsapp:+14155238886',
        'To' => $adminPhone,
        'Body' => $mensaje
    ];
    
    $options = [
        CURLOPT_URL => $url,
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USERPWD => "$sid:$token",
        CURLOPT_POSTFIELDS => http_build_query($data),
    ];
    
    $ch = curl_init();
    curl_setopt_array($ch, $options);
    curl_exec($ch);
    curl_close($ch);
}

$success = '';
$error = '';

// Manejo de acciones POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create_delivery') {
        $id_reparacion = (int)($_POST['id_reparacion'] ?? 0);
        $id_driver = !empty($_POST['id_driver']) ? (int)$_POST['id_driver'] : null;
        $estado = trim($_POST['estado'] ?? 'Pendiente');
        $codigo_tracking = trim($_POST['codigo_tracking'] ?? '');
        $fecha_salida = !empty($_POST['fecha_salida']) ? $_POST['fecha_salida'] : null;
        
        if ($id_reparacion > 0 && $codigo_tracking !== '') {
            $stmt = $conn->prepare("INSERT INTO Deliveries (IdReparacion, IdDriver, Estado, CodigoTracking, FechaCreacion, FechaSalida) VALUES (?, ?, ?, ?, NOW(), ?)");
            if ($stmt) {
                $stmt->bind_param('iisss', $id_reparacion, $id_driver, $estado, $codigo_tracking, $fecha_salida);
                if ($stmt->execute()) {
                    $success = 'Delivery creado exitosamente';
                } else {
                    $error = 'Error al crear delivery: ' . $stmt->error;
                }
                $stmt->close();
            }
        } else {
            $error = 'Debes completar los campos requeridos';
        }
    }
    
    if ($action === 'update_delivery') {
        $id_delivery = (int)($_POST['id_delivery'] ?? 0);
        $estado = trim($_POST['estado'] ?? '');
        $id_driver = !empty($_POST['id_driver']) ? (int)$_POST['id_driver'] : null;
        $fecha_entrega = !empty($_POST['fecha_entrega']) ? $_POST['fecha_entrega'] : null;
        
        if ($id_delivery > 0 && $estado !== '') {
            // Obtener estado anterior para enviar notificación si cambió
            $stmt_old = $conn->prepare("SELECT Estado, CodigoTracking FROM Deliveries WHERE IdDelivery = ?");
            $estado_anterior = '';
            $codigo_tracking = '';
            if ($stmt_old) {
                $stmt_old->bind_param('i', $id_delivery);
                $stmt_old->execute();
                $result_old = $stmt_old->get_result();
                if ($row_old = $result_old->fetch_assoc()) {
                    $estado_anterior = $row_old['Estado'];
                    $codigo_tracking = $row_old['CodigoTracking'];
                }
                $stmt_old->close();
            }
            
            $stmt = $conn->prepare("UPDATE Deliveries SET Estado = ?, IdDriver = ?, FechaEntrega = ? WHERE IdDelivery = ?");
            if ($stmt) {
                $stmt->bind_param('sisi', $estado, $id_driver, $fecha_entrega, $id_delivery);
                if ($stmt->execute()) {
                    $success = 'Delivery actualizado exitosamente';
                    
                    // Enviar notificación por WhatsApp si el estado cambió
                    if ($estado_anterior !== '' && $estado_anterior !== $estado) {
                        sendDeliveryNotification($codigo_tracking, $estado_anterior, $estado);
                    }
                } else {
                    $error = 'Error al actualizar delivery: ' . $stmt->error;
                }
                $stmt->close();
            }
        } else {
            $error = 'Debes completar los campos requeridos';
        }
    }
    
    if ($action === 'delete_delivery') {
        $id_delivery = (int)($_POST['id_delivery'] ?? 0);
        
        if ($id_delivery > 0) {
            $stmt = $conn->prepare("DELETE FROM Deliveries WHERE IdDelivery = ?");
            if ($stmt) {
                $stmt->bind_param('i', $id_delivery);
                if ($stmt->execute()) {
                    $success = 'Delivery eliminado exitosamente';
                } else {
                    $error = 'Error al eliminar delivery: ' . $stmt->error;
                }
                $stmt->close();
            }
        } else {
            $error = 'ID de delivery inválido';
        }
    }
    
    if ($action === 'create_driver') {
        $nombre = trim($_POST['nombre'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        
        if ($nombre !== '' && $telefono !== '') {
            $stmt = $conn->prepare("INSERT INTO DeliveryDrivers (Nombre, Telefono) VALUES (?, ?)");
            if ($stmt) {
                $stmt->bind_param('ss', $nombre, $telefono);
                if ($stmt->execute()) {
                    $success = 'Driver creado exitosamente';
                } else {
                    $error = 'Error al crear driver: ' . $stmt->error;
                }
                $stmt->close();
            }
        } else {
            $error = 'Debes completar los campos requeridos';
        }
    }
    
    if ($action === 'delete_driver') {
        $id_driver = (int)($_POST['id_driver'] ?? 0);
        
        if ($id_driver > 0) {
            $stmt = $conn->prepare("DELETE FROM DeliveryDrivers WHERE IdDriver = ?");
            if ($stmt) {
                $stmt->bind_param('i', $id_driver);
                if ($stmt->execute()) {
                    $success = 'Driver eliminado exitosamente';
                } else {
                    $error = 'Error al eliminar driver: ' . $stmt->error;
                }
                $stmt->close();
            }
        } else {
            $error = 'ID de driver inválido';
        }
    }
    
    if ($action === 'add_tracking') {
        $id_delivery = (int)($_POST['id_delivery'] ?? 0);
        $latitud = (float)($_POST['latitud'] ?? 0);
        $longitud = (float)($_POST['longitud'] ?? 0);
        
        if ($id_delivery > 0 && $latitud !== 0 && $longitud !== 0) {
            $stmt = $conn->prepare("INSERT INTO DeliveryTracking (IdDelivery, Latitud, Longitud, Fecha) VALUES (?, ?, ?, NOW())");
            if ($stmt) {
                $stmt->bind_param('idd', $id_delivery, $latitud, $longitud);
                if ($stmt->execute()) {
                    $success = 'Ubicación GPS registrada exitosamente';
                } else {
                    $error = 'Error al registrar ubicación: ' . $stmt->error;
                }
                $stmt->close();
            }
        } else {
            $error = 'Debes completar los campos requeridos';
        }
    }
}

// Cargar deliveries
$deliveries = [];
$query = "SELECT d.*, dr.Nombre as DriverNombre FROM Deliveries d LEFT JOIN DeliveryDrivers dr ON d.IdDriver = dr.IdDriver ORDER BY d.FechaCreacion DESC";
$result = $conn->query($query);
if ($result) {
    while($row = $result->fetch_assoc()) {
        $deliveries[] = $row;
    }
}

// Cargar drivers
$drivers = [];
$driver_query = "SELECT * FROM DeliveryDrivers ORDER BY Nombre ASC";
$driver_result = $conn->query($driver_query);
if ($driver_result) {
    while($row = $driver_result->fetch_assoc()) {
        $drivers[] = $row;
    }
}

// Cargar tracking GPS
$tracking_data = [];
if (!empty($deliveries)) {
    $delivery_ids = array_column($deliveries, 'IdDelivery');
    if (!empty($delivery_ids)) {
        $ids_string = implode(',', array_map('intval', $delivery_ids));
        $tracking_query = "SELECT dt.*, d.CodigoTracking FROM DeliveryTracking dt JOIN Deliveries d ON dt.IdDelivery = d.IdDelivery WHERE dt.IdDelivery IN ($ids_string) ORDER BY dt.Fecha DESC LIMIT 50";
        $tracking_result = $conn->query($tracking_query);
        if ($tracking_result) {
            while($row = $tracking_result->fetch_assoc()) {
                $tracking_data[] = $row;
            }
        }
    }
}

// Cargar órdenes de reparación para el dropdown
$ordenes_reparacion = [];
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
    $estado_col = repairly_pick_column($orden_cols, ['id_estado_actual', 'estado']);
    $equipo_col = repairly_pick_column($orden_cols, ['id_equipo']);
    
    // Obtener dirección del cliente
    $eq_tbl = pick_table($conn, ['equipo', 'Equipo']);
    $cli_tbl = pick_table($conn, ['cliente', 'Cliente']);
    
    $query_ordenes = "SELECT o.`{$id_col}` as id_orden";
    if ($codigo_col) $query_ordenes .= ", o.`{$codigo_col}` as codigo";
    if ($estado_col) $query_ordenes .= ", o.`{$estado_col}` as id_estado";
    
    if ($eq_tbl !== '' && $cli_tbl !== '' && $equipo_col) {
        $cli_cols = table_columns($conn, $cli_tbl);
        $cli_dir_col = repairly_pick_column($cli_cols, ['direccion', 'address', 'direccion_completa']);
        
        if ($cli_dir_col) {
            $query_ordenes .= ", c.`{$cli_dir_col}` as direccion";
            $query_ordenes .= " FROM `{$orden_table}` o";
            $query_ordenes .= " JOIN `{$eq_tbl}` e ON e.id_equipo = o.`{$equipo_col}`";
            $query_ordenes .= " JOIN `{$cli_tbl}` c ON c.id_cliente = e.id_cliente";
        } else {
            $query_ordenes .= " FROM `{$orden_table}` o";
        }
    } else {
        $query_ordenes .= " FROM `{$orden_table}` o";
    }
    
    $query_ordenes .= " ORDER BY o.`{$id_col}` DESC LIMIT 100";
    
    $result_ordenes = $conn->query($query_ordenes);
    if ($result_ordenes) {
        while($row = $result_ordenes->fetch_assoc()) {
            $ordenes_reparacion[] = $row;
        }
    }
}

// Contar estados
$total_deliveries = count($deliveries);
$en_transito = 0;
$completados = 0;

foreach ($deliveries as $d) {
    $estado = strtolower($d['Estado'] ?? '');
    if ($estado === 'en tránsito' || $estado === 'en transito') {
        $en_transito++;
    } elseif ($estado === 'completado' || $estado === 'entregado') {
        $completados++;
    }
}

?>

<!-- Mensajes de éxito/error -->
<?php if ($success): ?>
    <div style="background:#E8F5E9;color:#166534;padding:14px 16px;border-radius:14px;margin-bottom:16px;">
        <?= htmlspecialchars($success) ?>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div style="background:#FEE2E2;color:#991B1B;padding:14px 16px;border-radius:14px;margin-bottom:16px;">
        <?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<!-- Tarjetas de estadísticas - Estilo Dashboard -->
<div class="kpi-grid">
    <div class="kpi-card" style="background:#E3F2FD;--kpi-color:#2b7abc;">
        <div class="kpi-label" style="color:#2b7abc;">
            <i class="ti ti-truck" aria-hidden="true"></i>
            Total deliveries
        </div>
        <div class="kpi-valor" style="color:#2b7abc;">
            <?= $total_deliveries ?>
        </div>
        <div class="kpi-sub">Entregas registradas</div>
    </div>
    <div class="kpi-card" style="background:#FFF3E0;--kpi-color:#FF9500;">
        <div class="kpi-label" style="color:#FF9500;">
            <i class="ti ti-activity-heartbeat" aria-hidden="true"></i>
            En tránsito
        </div>
        <div class="kpi-valor" style="color:#FF9500;">
            <?= $en_transito ?>
        </div>
        <div class="kpi-sub">Entregas activas</div>
    </div>
    <div class="kpi-card" style="background:#E8F5E9;--kpi-color:#00AA44;">
        <div class="kpi-label" style="color:#00AA44;">
            <i class="ti ti-check-circle" aria-hidden="true"></i>
            Completadas
        </div>
        <div class="kpi-valor" style="color:#00AA44;">
            <?= $completados ?>
        </div>
        <div class="kpi-sub">Entregas exitosas</div>
    </div>
</div>

<!-- Formulario para crear nuevo delivery -->
<div class="card" style="margin-top:18px;">
    <div class="card-header">
        <div>
            <div class="card-title">➕ Nuevo Delivery</div>
            <div class="card-sub">Crear un nuevo registro de entrega</div>
        </div>
    </div>

    <form method="POST" style="padding:20px;">
        <input type="hidden" name="action" value="create_delivery">
        
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
            <div>
                <label style="font-size:11px;color:#6B6560;display:block;margin-bottom:6px;">Orden de Reparación *</label>
                <select name="id_reparacion" id="id_reparacion" required onchange="cargarDatosOrden()" style="width:100%;padding:10px;border-radius:8px;border:0.5px solid #D0CCC6;font-size:12px;color:#1C1A17;">
                    <option value="">Seleccionar orden...</option>
                    <?php foreach ($ordenes_reparacion as $orden): ?>
                        <option value="<?= htmlspecialchars($orden['id_orden']) ?>" 
                                data-codigo="<?= htmlspecialchars($orden['codigo'] ?? '') ?>"
                                data-estado="<?= htmlspecialchars($orden['id_estado'] ?? '') ?>">
                            #<?= htmlspecialchars($orden['id_orden']) ?> - <?= htmlspecialchars($orden['codigo'] ?? 'Sin código') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label style="font-size:11px;color:#6B6560;display:block;margin-bottom:6px;">Driver</label>
                <select name="id_driver" style="width:100%;padding:10px;border-radius:8px;border:0.5px solid #D0CCC6;font-size:12px;color:#1C1A17;">
                    <option value="">Sin asignar</option>
                    <?php foreach ($drivers as $driver): ?>
                        <option value="<?= htmlspecialchars($driver['IdDriver']) ?>">
                            <?= htmlspecialchars($driver['Nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
            <div>
                <label style="font-size:11px;color:#6B6560;display:block;margin-bottom:6px;">Código Tracking *</label>
                <input type="text" name="codigo_tracking" id="codigo_tracking" required style="width:100%;padding:10px;border-radius:8px;border:0.5px solid #D0CCC6;font-size:12px;color:#1C1A17;" placeholder="Ej: DEL-2024-001">
            </div>
            
            <div>
                <label style="font-size:11px;color:#6B6560;display:block;margin-bottom:6px;">Fecha Salida</label>
                <input type="datetime-local" name="fecha_salida" style="width:100%;padding:10px;border-radius:8px;border:0.5px solid #D0CCC6;font-size:12px;color:#1C1A17;">
            </div>
        </div>

        <div style="margin-bottom:16px;">
            <label style="font-size:11px;color:#6B6560;display:block;margin-bottom:6px;">Estado</label>
            <select name="estado" id="estado_delivery" style="width:100%;padding:10px;border-radius:8px;border:0.5px solid #D0CCC6;font-size:12px;color:#1C1A17;">
                <option value="Pendiente">Pendiente</option>
                <option value="En tránsito">En tránsito</option>
                <option value="Completado">Completado</option>
                <option value="Entregado">Entregado</option>
            </select>
        </div>

        <div style="background:#F5F5F5;padding:12px;border-radius:8px;margin-bottom:16px;">
            <div style="font-size:11px;color:#6B6560;margin-bottom:4px;">📋 Detalles de la orden seleccionada:</div>
            <div id="detalles_orden" style="font-size:12px;color:#1C1A17;">
                Selecciona una orden para ver los detalles
            </div>
        </div>

        <button type="submit" class="ordenes-ver-btn" style="background:#2b7abc;color:white;border-color:#2b7abc;">
            <i class="ti ti-plus"></i>
            Crear Delivery
        </button>
    </form>
</div>

<!-- Tabla de deliveries -->
<div class="card" style="margin-top:18px;">
    <div class="card-header">
        <div>
            <div class="card-title">🚚 Gestión de Deliveries</div>
            <div class="card-sub">Tracking y estado de entregas</div>
        </div>
    </div>

    <div class="table-head" style="margin-top:14px;grid-template-columns:70px 1.2fr 120px 100px 100px 120px;gap:12px;">
        <div>ID</div>
        <div>Código Tracking</div>
        <div>Estado</div>
        <div>Driver</div>
        <div>Fecha Salida</div>
        <div>Acciones</div>
    </div>

    <div style="max-height:400px;overflow-y:auto;">
    <?php foreach ($deliveries as $delivery): ?>
    <div class="table-row" style="grid-template-columns:70px 1.2fr 120px 100px 100px 120px;gap:12px;">
        <div style="font-size:11px;color:#8C8479;font-family:'Courier New';"><?= htmlspecialchars($delivery['IdDelivery']) ?></div>
        <div style="font-size:12px;color:#1C1A17;font-weight:500;"><?= htmlspecialchars($delivery['CodigoTracking']) ?></div>
        <div style="font-size:11px;">
            <?php 
                $estado = htmlspecialchars($delivery['Estado']);
                $estado_lower = strtolower($estado);
                $bg_color = '#E3F2FD';
                $text_color = '#2b7abc';
                
                if ($estado_lower === 'en tránsito' || $estado_lower === 'en transito') {
                    $bg_color = '#FFF3E0';
                    $text_color = '#FF9500';
                } elseif ($estado_lower === 'completado' || $estado_lower === 'entregado') {
                    $bg_color = '#E8F5E9';
                    $text_color = '#00AA44';
                }
            ?>
            <span style="background:<?= $bg_color ?>;color:<?= $text_color ?>;padding:4px 8px;border-radius:4px;display:inline-block;">
                <?= $estado ?>
            </span>
        </div>
        <div style="font-size:12px;color:#4D4841;"><?= htmlspecialchars($delivery['DriverNombre'] ?? 'Sin asignar') ?></div>
        <div style="font-size:12px;color:#4D4841;"><?= htmlspecialchars($delivery['FechaSalida'] ?? '-') ?></div>
        <div style="display:flex;gap:8px;">
            <form method="POST" style="display:inline;">
                <input type="hidden" name="action" value="delete_delivery">
                <input type="hidden" name="id_delivery" value="<?= htmlspecialchars($delivery['IdDelivery']) ?>">
                <button type="submit" onclick="return confirm('¿Eliminar este delivery?')" style="background:#FEE2E2;color:#991B1B;border:none;padding:4px 8px;border-radius:4px;font-size:11px;cursor:pointer;">
                    🗑️ Eliminar
                </button>
            </form>
        </div>
    </div>
    <?php endforeach; ?>

    <?php if (empty($deliveries)): ?>
    <div style="padding:20px;text-align:center;color:#6B6560;font-size:12px;">
        No hay deliveries registrados
    </div>
    <?php endif; ?>
    </div>
</div>

<!-- Gestión de Drivers -->
<div class="card" style="margin-top:18px;">
    <div class="card-header">
        <div>
            <div class="card-title">👨‍✈️ Gestión de Drivers</div>
            <div class="card-sub">Administrar conductores de delivery</div>
        </div>
    </div>

    <form method="POST" style="padding:20px;">
        <input type="hidden" name="action" value="create_driver">
        
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
            <div>
                <label style="font-size:11px;color:#6B6560;display:block;margin-bottom:6px;">Nombre del Driver *</label>
                <input type="text" name="nombre" required style="width:100%;padding:10px;border-radius:8px;border:0.5px solid #D0CCC6;font-size:12px;color:#1C1A17;" placeholder="Nombre completo">
            </div>
            
            <div>
                <label style="font-size:11px;color:#6B6560;display:block;margin-bottom:6px;">Teléfono *</label>
                <input type="text" name="telefono" required style="width:100%;padding:10px;border-radius:8px;border:0.5px solid #D0CCC6;font-size:12px;color:#1C1A17;" placeholder="809-123-4567">
            </div>
        </div>

        <button type="submit" class="ordenes-ver-btn" style="background:#FF9500;color:white;border-color:#FF9500;">
            <i class="ti ti-user-plus"></i>
            Agregar Driver
        </button>
    </form>

    <div class="table-head" style="margin-top:14px;grid-template-columns:80px 1fr 150px 100px;gap:12px;">
        <div>ID</div>
        <div>Nombre</div>
        <div>Teléfono</div>
        <div>Acciones</div>
    </div>

    <div style="max-height:200px;overflow-y:auto;">
    <?php foreach ($drivers as $driver): ?>
    <div class="table-row" style="grid-template-columns:80px 1fr 150px 100px;gap:12px;">
        <div style="font-size:11px;color:#8C8479;font-family:'Courier New';"><?= htmlspecialchars($driver['IdDriver']) ?></div>
        <div style="font-size:12px;color:#1C1A17;font-weight:500;"><?= htmlspecialchars($driver['Nombre']) ?></div>
        <div style="font-size:12px;color:#4D4841;"><?= htmlspecialchars($driver['Telefono']) ?></div>
        <div style="display:flex;gap:8px;">
            <form method="POST" style="display:inline;">
                <input type="hidden" name="action" value="delete_driver">
                <input type="hidden" name="id_driver" value="<?= htmlspecialchars($driver['IdDriver']) ?>">
                <button type="submit" onclick="return confirm('¿Eliminar este driver?')" style="background:#FEE2E2;color:#991B1B;border:none;padding:4px 8px;border-radius:4px;font-size:11px;cursor:pointer;">
                    🗑️ Eliminar
                </button>
            </form>
        </div>
    </div>
    <?php endforeach; ?>

    <?php if (empty($drivers)): ?>
    <div style="padding:20px;text-align:center;color:#6B6560;font-size:12px;">
        No hay drivers registrados
    </div>
    <?php endif; ?>
    </div>
</div>

<!-- Seguimiento GPS -->
<div class="card" style="margin-top:18px;">
    <div class="card-header">
        <div>
            <div class="card-title">📍 Seguimiento GPS</div>
            <div class="card-sub">Ubicaciones registradas de deliveries</div>
        </div>
    </div>

    <form method="POST" style="padding:20px;">
        <input type="hidden" name="action" value="add_tracking">
        
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;margin-bottom:16px;">
            <div>
                <label style="font-size:11px;color:#6B6560;display:block;margin-bottom:6px;">Delivery *</label>
                <select name="id_delivery" id="id_delivery_tracking" required onchange="obtenerDireccionDelivery()" style="width:100%;padding:10px;border-radius:8px;border:0.5px solid #D0CCC6;font-size:12px;color:#1C1A17;">
                    <option value="">Seleccionar delivery</option>
                    <?php foreach ($deliveries as $delivery): ?>
                        <option value="<?= htmlspecialchars($delivery['IdDelivery']) ?>" 
                                data-reparacion="<?= htmlspecialchars($delivery['IdReparacion'] ?? '') ?>">
                            <?= htmlspecialchars($delivery['CodigoTracking']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label style="font-size:11px;color:#6B6560;display:block;margin-bottom:6px;">Latitud *</label>
                <div style="display:flex;gap:8px;">
                    <input type="number" step="any" name="latitud" id="latitud_input" required style="flex:1;padding:10px;border-radius:8px;border:0.5px solid #D0CCC6;font-size:12px;color:#1C1A17;" placeholder="18.4861">
                    <button type="button" onclick="obtenerCoordenadas()" style="padding:10px 15px;border-radius:8px;border:0.5px solid #D0CCC6;background:#E3F2FD;color:#2b7abc;font-size:12px;cursor:pointer;" title="Obtener coordenadas automáticamente">
                        📍
                    </button>
                </div>
            </div>
            
            <div>
                <label style="font-size:11px;color:#6B6560;display:block;margin-bottom:6px;">Longitud *</label>
                <input type="number" step="any" name="longitud" id="longitud_input" required style="width:100%;padding:10px;border-radius:8px;border:0.5px solid #D0CCC6;font-size:12px;color:#1C1A17;" placeholder="-69.9312">
            </div>
        </div>

        <div id="direccion_info" style="background:#F5F5F5;padding:10px;border-radius:8px;margin-bottom:16px;font-size:11px;color:#6B6560;display:none;">
            <strong>Dirección del cliente:</strong> <span id="direccion_texto"></span>
        </div>

        <button type="submit" class="ordenes-ver-btn" style="background:#00AA44;color:white;border-color:#00AA44;">
            <i class="ti ti-map-pin"></i>
            Registrar Ubicación
        </button>
    </form>

    <div class="table-head" style="margin-top:14px;grid-template-columns:100px 1fr 100px 100px 150px;gap:12px;">
        <div>ID Tracking</div>
        <div>Código Delivery</div>
        <div>Latitud</div>
        <div>Longitud</div>
        <div>Fecha</div>
    </div>

    <div style="max-height:200px;overflow-y:auto;">
    <?php foreach ($tracking_data as $track): ?>
    <div class="table-row" style="grid-template-columns:100px 1fr 100px 100px 150px;gap:12px;">
        <div style="font-size:11px;color:#8C8479;font-family:'Courier New';"><?= htmlspecialchars($track['IdTracking']) ?></div>
        <div style="font-size:12px;color:#1C1A17;font-weight:500;"><?= htmlspecialchars($track['CodigoTracking']) ?></div>
        <div style="font-size:12px;color:#4D4841;"><?= htmlspecialchars($track['Latitud']) ?></div>
        <div style="font-size:12px;color:#4D4841;"><?= htmlspecialchars($track['Longitud']) ?></div>
        <div style="font-size:12px;color:#4D4841;"><?= htmlspecialchars($track['Fecha']) ?></div>
    </div>
    <?php endforeach; ?>

    <?php if (empty($tracking_data)): ?>
    <div style="padding:20px;text-align:center;color:#6B6560;font-size:12px;">
        No hay ubicaciones GPS registradas
    </div>
    <?php endif; ?>
    </div>
</div>

<script>
let direccionCliente = '';

function cargarDatosOrden() {
    const select = document.getElementById('id_reparacion');
    const selectedOption = select.options[select.selectedIndex];
    const codigoTracking = document.getElementById('codigo_tracking');
    const detallesDiv = document.getElementById('detalles_orden');
    
    if (select.value === '') {
        codigoTracking.value = '';
        detallesDiv.innerHTML = 'Selecciona una orden para ver los detalles';
        return;
    }
    
    // Obtener datos de la opción seleccionada
    const codigoOrden = selectedOption.getAttribute('data-codigo') || '';
    const estadoOrden = selectedOption.getAttribute('data-estado') || '';
    
    // Generar código de tracking automáticamente basado en el código de la orden
    const fecha = new Date().toISOString().split('T')[0];
    const codigoGenerado = 'DEL-' + fecha + '-' + codigoOrden.replace(/FC-/i, '');
    codigoTracking.value = codigoGenerado;
    
    // Mostrar detalles de la orden
    detallesDiv.innerHTML = `
        <strong>Código:</strong> ${codigoOrden || 'N/A'}<br>
        <strong>ID:</strong> #${select.value}<br>
        <strong>Estado:</strong> ${estadoOrden || 'N/A'}
    `;
}

function obtenerDireccionDelivery() {
    const select = document.getElementById('id_delivery_tracking');
    const selectedOption = select.options[select.selectedIndex];
    const idReparacion = selectedOption.getAttribute('data-reparacion');
    const direccionInfo = document.getElementById('direccion_info');
    const direccionTexto = document.getElementById('direccion_texto');
    
    if (!idReparacion) {
        direccionInfo.style.display = 'none';
        direccionCliente = '';
        return;
    }
    
    // Buscar la dirección en las órdenes cargadas
    const orden = <?= json_encode($ordenes_reparacion) ?>.find(o => o.id_orden == idReparacion);
    
    if (orden && orden.direccion) {
        direccionCliente = orden.direccion;
        direccionTexto.textContent = orden.direccion;
        direccionInfo.style.display = 'block';
    } else {
        direccionCliente = '';
        direccionInfo.style.display = 'none';
    }
}

async function obtenerCoordenadas() {
    if (!direccionCliente) {
        alert('Primero selecciona un delivery para obtener la dirección del cliente');
        return;
    }
    
    const latitudInput = document.getElementById('latitud_input');
    const longitudInput = document.getElementById('longitud_input');
    
    // Mostrar indicador de carga
    latitudInput.value = 'Cargando...';
    longitudInput.value = 'Cargando...';
    
    try {
        const response = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(direccionCliente)}&limit=1`, {
            headers: {
                'User-Agent': 'RepairlyRD/1.0'
            }
        });
        
        const data = await response.json();
        
        if (data && data.length > 0) {
            latitudInput.value = parseFloat(data[0].lat).toFixed(7);
            longitudInput.value = parseFloat(data[0].lon).toFixed(7);
        } else {
            alert('No se encontraron coordenadas para esta dirección. Por favor, ingrésalas manualmente.');
            latitudInput.value = '';
            longitudInput.value = '';
        }
    } catch (error) {
        console.error('Error al obtener coordenadas:', error);
        alert('Error al obtener coordenadas. Por favor, ingrésalas manualmente.');
        latitudInput.value = '';
        longitudInput.value = '';
    }
}
</script>