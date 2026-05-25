<?php

/** @var mysqli $conn */
require_once __DIR__ . '/../../includes/ui_helper.php';

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
                <label style="font-size:11px;color:#6B6560;display:block;margin-bottom:6px;">ID Reparación *</label>
                <input type="number" name="id_reparacion" required style="width:100%;padding:10px;border-radius:8px;border:0.5px solid #D0CCC6;font-size:12px;color:#1C1A17;" placeholder="ID de la reparación">
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
                <input type="text" name="codigo_tracking" required style="width:100%;padding:10px;border-radius:8px;border:0.5px solid #D0CCC6;font-size:12px;color:#1C1A17;" placeholder="Ej: DEL-2024-001">
            </div>
            
            <div>
                <label style="font-size:11px;color:#6B6560;display:block;margin-bottom:6px;">Fecha Salida</label>
                <input type="datetime-local" name="fecha_salida" style="width:100%;padding:10px;border-radius:8px;border:0.5px solid #D0CCC6;font-size:12px;color:#1C1A17;">
            </div>
        </div>

        <div style="margin-bottom:16px;">
            <label style="font-size:11px;color:#6B6560;display:block;margin-bottom:6px;">Estado</label>
            <select name="estado" style="width:100%;padding:10px;border-radius:8px;border:0.5px solid #D0CCC6;font-size:12px;color:#1C1A17;">
                <option value="Pendiente">Pendiente</option>
                <option value="En tránsito">En tránsito</option>
                <option value="Completado">Completado</option>
                <option value="Entregado">Entregado</option>
            </select>
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
                <select name="id_delivery" required style="width:100%;padding:10px;border-radius:8px;border:0.5px solid #D0CCC6;font-size:12px;color:#1C1A17;">
                    <option value="">Seleccionar delivery</option>
                    <?php foreach ($deliveries as $delivery): ?>
                        <option value="<?= htmlspecialchars($delivery['IdDelivery']) ?>">
                            <?= htmlspecialchars($delivery['CodigoTracking']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label style="font-size:11px;color:#6B6560;display:block;margin-bottom:6px;">Latitud *</label>
                <input type="number" step="any" name="latitud" required style="width:100%;padding:10px;border-radius:8px;border:0.5px solid #D0CCC6;font-size:12px;color:#1C1A17;" placeholder="18.4861">
            </div>
            
            <div>
                <label style="font-size:11px;color:#6B6560;display:block;margin-bottom:6px;">Longitud *</label>
                <input type="number" step="any" name="longitud" required style="width:100%;padding:10px;border-radius:8px;border:0.5px solid #D0CCC6;font-size:12px;color:#1C1A17;" placeholder="-69.9312">
            </div>
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