<?php

// Función para enviar mensaje por Twilio
function sendTwilioNotification($ordenDetails) {
    $sid = getenv('TWILIO_SID') ?: 'YOUR_TWILIO_SID';
    $token = getenv('TWILIO_TOKEN') ?: 'YOUR_TWILIO_TOKEN';
    
    // Número de WhatsApp del administrador (debe configurarse)
    $adminPhone = getenv('ADMIN_WHATSAPP') ?: 'whatsapp:+18295921607';
    
    // Formatear el mensaje con detalles de la orden
    $mensaje = "🔔 Nueva Orden de Reparación\n\n";
    $mensaje .= "📋 Código: " . ($ordenDetails['codigo'] ?? 'N/A') . "\n";
    $mensaje .= "👤 Cliente: " . ($ordenDetails['cliente_nombre'] ?? 'N/A') . "\n";
    $mensaje .= "🔧 Equipo: " . ($ordenDetails['equipo'] ?? 'N/A') . "\n";
    $mensaje .= "💰 Costo: RD$ " . number_format($ordenDetails['costo_total'] ?? 0) . "\n";
    $mensaje .= "📍 Estado: " . ($ordenDetails['estado'] ?? 'N/A') . "\n";
    $mensaje .= "\n✅ Orden creada exitosamente";
    
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
    $response = curl_exec($ch);
    curl_close($ch);
    
    return $response;
}


$orden_table_name = pick_table($conn, ['orden_reparacion', 'Orden_Reparacion', 'reparacion', 'Reparacion', 'orden']);
$orden_cols = $orden_table_name ? table_columns($conn, $orden_table_name) : [];

if ($current_page === 'ordenes' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $post_action = $_POST['orden_action'] ?? '';
    if (!is_string($post_action)) {
        $post_action = '';
    }

    if (!$orden_table_name) {
        header('Location: ?page=ordenes&t=err&m=Tabla+orden+no+encontrada');
        exit;
    }

    // Sembrar estados por defecto (cuando el selector sale vacío).
    if ($post_action === 'seed_estados') {
        $estados_tbl = pick_table($conn, ['estado_servicio', 'estado', 'Estado_Servicio']);
        $target = $estados_tbl !== '' ? $estados_tbl : 'estado_servicio';

        $ok = true;
        $sqlCreate = "
            CREATE TABLE IF NOT EXISTS `{$target}` (
                `id_estado` INT AUTO_INCREMENT PRIMARY KEY,
                `nombre_estado` VARCHAR(60) NOT NULL UNIQUE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        if (!$conn->query($sqlCreate)) {
            $ok = false;
        } else {
            $defaults = ['Recibido', 'En diagnóstico', 'En reparación', 'En espera de piezas', 'Listo para entrega', 'Entregado'];
            $ins = $conn->prepare("INSERT IGNORE INTO `{$target}` (`nombre_estado`) VALUES (?)");
            if (!$ins) {
                $ok = false;
            } else {
                foreach ($defaults as $name) {
                    $ins->bind_param('s', $name);
                    if (!$ins->execute()) {
                        $ok = false;
                        break;
                    }
                }
                $ins->close();
            }
        }

        header('Location: ?page=ordenes&t=' . ($ok ? 'ok' : 'err') . '&m=' . ($ok ? 'Estados+creados' : 'No+se+pudieron+crear+los+estados'));
        exit;
    }

    $idField = 'id_orden';
    foreach (array_keys($orden_cols) as $k) {
        if (strcasecmp((string)$k, 'id_orden') === 0) {
            $idField = $k;
            break;
        }
    }

    $codigoCol = null;
    $equipoCol = null;
    $tecnicoCol = null;
    $estadoCol = null;
    $manoObraCol = null;
    $costoTotalCol = null;
    $fechaIngresoCol = null;
    $fechaEstCol = null;
    $fechaEntCol = null;
    foreach (array_keys($orden_cols) as $k) {
        if ($codigoCol === null && strcasecmp((string)$k, 'codigo_seguimiento') === 0) {
            $codigoCol = $k;
            continue;
        }
        if ($equipoCol === null && strcasecmp((string)$k, 'id_equipo') === 0) {
            $equipoCol = $k;
            continue;
        }
        if ($tecnicoCol === null && strcasecmp((string)$k, 'id_tecnico') === 0) {
            $tecnicoCol = $k;
            continue;
        }
        if ($estadoCol === null && strcasecmp((string)$k, 'id_estado_actual') === 0) {
            $estadoCol = $k;
            continue;
        }
        if ($manoObraCol === null && strcasecmp((string)$k, 'mano_obra') === 0) {
            $manoObraCol = $k;
            continue;
        }
        if ($costoTotalCol === null && strcasecmp((string)$k, 'costo_total') === 0) {
            $costoTotalCol = $k;
            continue;
        }
        if ($fechaIngresoCol === null && strcasecmp((string)$k, 'fecha_ingreso') === 0) {
            $fechaIngresoCol = $k;
            continue;
        }
        if ($fechaEstCol === null && strcasecmp((string)$k, 'fecha_estimada_entrega') === 0) {
            $fechaEstCol = $k;
            continue;
        }
        if ($fechaEntCol === null && strcasecmp((string)$k, 'fecha_entrega_real') === 0) {
            $fechaEntCol = $k;
            continue;
        }
    }

    $codigo = trim((string)($_POST['codigo_seguimiento'] ?? ''));
    $id_equipo = (int)($_POST['id_equipo'] ?? 0);
    $id_tecnico = (int)($_POST['id_tecnico'] ?? 0);
    $id_estado = (int)($_POST['id_estado_actual'] ?? 0);
    $mano_obra_raw = trim((string)($_POST['mano_obra'] ?? ''));
    $costo_total_raw = trim((string)($_POST['costo_total'] ?? ''));
    $mano_obra = (float)str_replace(',', '.', ($mano_obra_raw !== '' ? $mano_obra_raw : '0'));
    $costo_total = (float)str_replace(',', '.', ($costo_total_raw !== '' ? $costo_total_raw : '0'));
    $fecha_ingreso = trim((string)($_POST['fecha_ingreso'] ?? ''));
    $fecha_est = trim((string)($_POST['fecha_estimada_entrega'] ?? ''));
    $fecha_ent_real = trim((string)($_POST['fecha_entrega_real'] ?? ''));

    if ($post_action === 'create' || $post_action === 'update') {
        $back = $post_action === 'update'
            ? ('?page=ordenes&action=edit&id=' . (int)($_POST['id_orden'] ?? 0))
            : '?page=ordenes&action=new';

        // Avisar si hay textbox vacío.
        if ($post_action === 'update' && $codigoCol !== null && $codigo === '') {
            header('Location: ' . $back . '&t=err&m=El+c%C3%B3digo+de+seguimiento+es+obligatorio');
            exit;
        }
        if ($manoObraCol !== null && $mano_obra_raw === '') {
            header('Location: ' . $back . '&t=err&m=Mano+de+obra+obligatoria');
            exit;
        }
        if ($costoTotalCol !== null && $costo_total_raw === '') {
            header('Location: ' . $back . '&t=err&m=Costo+total+obligatorio');
            exit;
        }
        if ($fechaIngresoCol !== null && $fecha_ingreso === '') {
            header('Location: ' . $back . '&t=err&m=Fecha+de+ingreso+obligatoria');
            exit;
        }

        // Validaciones mínimas (alineado con otros CRUDs)
        if (isset($orden_cols['id_equipo']) && $id_equipo <= 0) {
            header('Location: ' . $back . '&t=err&m=Debes+seleccionar+un+equipo');
            exit;
        }
        if (isset($orden_cols['id_estado_actual']) && $id_estado <= 0) {
            header('Location: ' . $back . '&t=err&m=Debes+seleccionar+un+estado');
            exit;
        }

        // CÃ³digo de seguimiento automÃ¡tico (FC-001, FC-002, ...).
        if ($post_action === 'create' && $codigoCol !== null && $codigo === '') {
            $colSafe = str_replace('`', '``', (string)$codigoCol);
            $sqlMax = "SELECT MAX(CAST(SUBSTRING(`{$colSafe}`, 4) AS UNSIGNED)) AS mx
                       FROM `{$orden_table_name}`
                       WHERE `{$colSafe}` LIKE 'FC-%'";
            $resMax = $conn->query($sqlMax);
            $mx = 0;
            if ($resMax) {
                $row = $resMax->fetch_assoc();
                $mx = (int)($row['mx'] ?? 0);
                $resMax->free();
            }
            $codigo = 'FC-' . str_pad((string)($mx + 1), 3, '0', STR_PAD_LEFT);
        }

        $data = [];
        if (isset($orden_cols['codigo_seguimiento'])) {
            $data['codigo_seguimiento'] = $codigo;
        }
        if (isset($orden_cols['id_equipo'])) {
            $data['id_equipo'] = $id_equipo;
        }
        if (isset($orden_cols['id_tecnico'])) {
            $data['id_tecnico'] = $id_tecnico;
        }
        if (isset($orden_cols['id_estado_actual'])) {
            $data['id_estado_actual'] = $id_estado;
        }
        if (isset($orden_cols['mano_obra'])) {
            $data['mano_obra'] = $mano_obra;
        }
        if (isset($orden_cols['costo_total'])) {
            $data['costo_total'] = $costo_total;
        }
        if (isset($orden_cols['fecha_ingreso'])) {
            $data['fecha_ingreso'] = $fecha_ingreso;
        }
        if (isset($orden_cols['fecha_estimada_entrega']) && $fecha_est !== '') {
            $data['fecha_estimada_entrega'] = $fecha_est;
        }
        if (isset($orden_cols['fecha_entrega_real']) && $fecha_ent_real !== '') {
            $data['fecha_entrega_real'] = $fecha_ent_real;
        }

        if ($post_action === 'create') {
            $fields = [];
            $types = '';
            $vals = [];
            foreach ($data as $col => $val) {
                $fields[] = "`{$col}`";
                if (is_int($val)) {
                    $types .= 'i';
                    $vals[] = $val;
                } elseif (is_float($val)) {
                    $types .= 'd';
                    $vals[] = $val;
                } else {
                    $types .= 's';
                    $vals[] = (string)$val;
                }
            }
            if (isset($orden_cols['fecha_creacion'])) {
                $fields[] = '`fecha_creacion`';
                $types .= 's';
                $vals[] = date('Y-m-d H:i:s');
            }
            if (isset($orden_cols['fecha_actualizacion'])) {
                $fields[] = '`fecha_actualizacion`';
                $types .= 's';
                $vals[] = date('Y-m-d H:i:s');
            }
            if (empty($fields)) {
                header('Location: ?page=ordenes&action=new&t=err&m=Datos+insuficientes');
                exit;
            }
            $sql = "INSERT INTO `{$orden_table_name}` (" . implode(',', $fields) . ') VALUES (' . implode(',', array_fill(0, count($fields), '?')) . ')';
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                header('Location: ?page=ordenes&t=err&m=No+se+pudo+crear+la+orden');
                exit;
            }
            if ($types !== '') {
                $stmt->bind_param($types, ...$vals);
            }
            $ok = $stmt->execute();
            $newOrderId = (int)$stmt->insert_id;

            // Notificar a n8n solo si la orden se creó correctamente.
            if ($ok) {
                $codigoWebhook = '';
                if (isset($data['codigo_seguimiento']) && is_string($data['codigo_seguimiento'])) {
                    $codigoWebhook = $data['codigo_seguimiento'];
                } elseif ($codigo !== '') {
                    $codigoWebhook = $codigo;
                } elseif ($newOrderId > 0) {
                    $codigoWebhook = (string)$newOrderId;
                }

                // Generar link de seguimiento
                $linkSeguimiento = '';
                if ($codigoWebhook !== '') {
                    $linkSeguimiento = "https://repairlyrd-production.up.railway.app/seguimiento.php?codigo=" . urlencode($codigoWebhook);
                }

                // Intentar resolver el email del cliente desde la orden -> equipo -> cliente.
                $clienteEmail = '';
                $clienteNombre = '';
                $clienteId = 0;
                if ($id_equipo > 0) {
                    $eqTbl = pick_table($conn, ['equipo', 'Equipo']);
                    $cliTbl = pick_table($conn, ['cliente', 'Cliente']);
                    if ($eqTbl !== '' && $cliTbl !== '') {
                        $eqCols = table_columns($conn, $eqTbl);
                        $cliCols = table_columns($conn, $cliTbl);

                        $eqIdCol = 'id_equipo';
                        foreach (array_keys($eqCols) as $k) {
                            if (strcasecmp((string)$k, 'id_equipo') === 0) {
                                $eqIdCol = $k;
                                break;
                            }
                        }
                        $cliIdCol = 'id_cliente';
                        foreach (array_keys($cliCols) as $k) {
                            if (strcasecmp((string)$k, 'id_cliente') === 0) {
                                $cliIdCol = $k;
                                break;
                            }
                        }

                        $eqCliCol = null;
                        foreach (array_keys($eqCols) as $k) {
                            if (strcasecmp((string)$k, 'id_cliente') === 0) {
                                $eqCliCol = $k;
                                break;
                            }
                        }

                        $cliEmailCol = repairly_pick_column($cliCols, ['email', 'correo', 'correo_electronico', 'mail']);
                        $cliNombreCol = repairly_pick_column($cliCols, ['nombre', 'name', 'nombre_cliente']);

                        if ($eqCliCol !== null && $cliEmailCol !== null) {
                            $sqlCli = "SELECT c.`{$cliIdCol}` AS id_cliente"
                                . ($cliNombreCol !== null ? ", c.`{$cliNombreCol}` AS nombre" : ", '' AS nombre")
                                . ", c.`{$cliEmailCol}` AS email"
                                . " FROM `{$eqTbl}` e"
                                . " JOIN `{$cliTbl}` c ON c.`{$cliIdCol}` = e.`{$eqCliCol}`"
                                . " WHERE e.`{$eqIdCol}`=? LIMIT 1";
                            $stCli = $conn->prepare($sqlCli);
                            if ($stCli) {
                                $stCli->bind_param('i', $id_equipo);
                                $stCli->execute();
                                $resCli = $stCli->get_result();
                                $rowCli = $resCli ? ($resCli->fetch_assoc() ?: null) : null;
                                $stCli->close();
                                if ($rowCli) {
                                    $clienteId = (int)($rowCli['id_cliente'] ?? 0);
                                    $clienteNombre = (string)($rowCli['nombre'] ?? '');
                                    $clienteEmail = (string)($rowCli['email'] ?? '');
                                }
                            }
                        }
                    }
                }
                // Obtener nombres reales
$equipoNombre = '';
$tecnicoNombre = '';
$estadoNombre = '';

// ===== EQUIPO =====
if ($id_equipo > 0) {
    $eqTbl = pick_table($conn, ['equipo', 'Equipo']);

    if ($eqTbl !== '') {
        $sqlEq = "SELECT * FROM `{$eqTbl}` WHERE id_equipo=? LIMIT 1";
        $stEq = $conn->prepare($sqlEq);

        if ($stEq) {
            $stEq->bind_param('i', $id_equipo);
            $stEq->execute();

            $resEq = $stEq->get_result();
            $rowEq = $resEq ? $resEq->fetch_assoc() : null;

            if ($rowEq) {
                $equipoNombre =
                    $rowEq['nombre_equipo']
                    ?? $rowEq['nombre']
                    ?? $rowEq['modelo']
                    ?? 'Equipo';
            }

            $stEq->close();
        }
    }
}

// ===== TECNICO =====
if ($id_tecnico > 0) {
    $tecTbl = pick_table($conn, ['tecnico', 'Tecnico', 'empleado', 'Empleado']);

    if ($tecTbl !== '') {
        $sqlTec = "SELECT * FROM `{$tecTbl}` WHERE id_tecnico=? LIMIT 1";
        $stTec = $conn->prepare($sqlTec);

        if ($stTec) {
            $stTec->bind_param('i', $id_tecnico);
            $stTec->execute();

            $resTec = $stTec->get_result();
            $rowTec = $resTec ? $resTec->fetch_assoc() : null;

            if ($rowTec) {
                $tecnicoNombre =
                    $rowTec['nombre']
                    ?? $rowTec['nombre_tecnico']
                    ?? $rowTec['name']
                    ?? 'Técnico';
            }

            $stTec->close();
        }
    }
}

// ===== ESTADO =====
if ($id_estado > 0) {
    $estTbl = pick_table($conn, ['estado_servicio', 'estado', 'Estado_Servicio']);

    if ($estTbl !== '') {
        $sqlEst = "SELECT * FROM `{$estTbl}` WHERE id_estado=? LIMIT 1";
        $stEst = $conn->prepare($sqlEst);

        if ($stEst) {
            $stEst->bind_param('i', $id_estado);
            $stEst->execute();

            $resEst = $stEst->get_result();
            $rowEst = $resEst ? $resEst->fetch_assoc() : null;

            if ($rowEst) {
                $estadoNombre =
                    $rowEst['nombre_estado']
                    ?? $rowEst['nombre']
                    ?? 'Estado';
            }

            $stEst->close();
        }
    }
}

                $webhookData = [
    'id_orden' => $newOrderId,
    'codigo' => $codigoWebhook,

    'equipo_nombre' => $equipoNombre,
    'tecnico_nombre' => $tecnicoNombre,
    'estado_nombre' => $estadoNombre,

    'mano_obra' => $mano_obra,
    'costo_total' => $costo_total,
    'id_cliente' => $clienteId,
    'cliente_nombre' => $clienteNombre,
    'cliente_email' => $clienteEmail,
    'link_seguimiento' => $linkSeguimiento,
];
                $options = [
                    'http' => [
                        'header'  => "Content-type: application/json",
                        'method'  => 'POST',
                        'content' => json_encode($webhookData),
                        'ignore_errors' => true,
                    ],
                ];

                $context = stream_context_create($options);

                @file_get_contents(
                'https://repairlyrdoficial.app.n8n.cloud/webhook/nueva-reparacion',
                 false,
                 $context
                );

                // Enviar notificación por Twilio al administrador
                $ordenDetails = [
                    'codigo' => $codigoWebhook,
                    'cliente_nombre' => $clienteNombre,
                    'equipo' => $id_equipo,
                    'costo_total' => $costo_total,
                    'estado' => $id_estado,
                ];
                sendTwilioNotification($ordenDetails);

                // Crear delivery automáticamente para la orden
                $codigoTracking = 'DEL-' . date('Y-m-d') . '-' . str_pad($newOrderId, 4, '0', STR_PAD_LEFT);
                $deliveryStmt = $conn->prepare("INSERT INTO Deliveries (IdReparacion, Estado, CodigoTracking, FechaCreacion) VALUES (?, 'Pendiente', ?, NOW())");
                if ($deliveryStmt) {
                    $deliveryStmt->bind_param('is', $newOrderId, $codigoTracking);
                    $deliveryStmt->execute();
                    $deliveryStmt->close();
                }
            }

            $stmt->close();
            header('Location: ?page=ordenes&t=' . ($ok ? 'ok' : 'err') . '&m=' . ($ok ? 'Orden+creada' : 'Error+al+crear'));
            exit;
        }

        $id = (int)($_POST['id_orden'] ?? 0);
        if ($id <= 0) {
            header('Location: ?page=ordenes&t=err&m=ID+inv%C3%A1lido');
            exit;
        }

        // Cargar estado previo para detectar cambios (y notificar vÃ­a n8n).
        $before = null;
        $stBefore = $conn->prepare("SELECT * FROM `{$orden_table_name}` WHERE `{$idField}`=? LIMIT 1");
        if ($stBefore) {
            $stBefore->bind_param('i', $id);
            $stBefore->execute();
            $rsBefore = $stBefore->get_result();
            $before = $rsBefore ? ($rsBefore->fetch_assoc() ?: null) : null;
            $stBefore->close();
        }

        $changes = [];
        if (is_array($before)) {
            foreach ($data as $col => $val) {
                $beforeVal = $before[$col] ?? null;
                $afterVal = $val;
                // ComparaciÃ³n flexible (string) para evitar falsos negativos por tipos.
                if ((string)$beforeVal !== (string)$afterVal) {
                    $changes[$col] = ['before' => $beforeVal, 'after' => $afterVal];
                }
            }
        }

        $sets = [];
        $typesU = '';
        $valsU = [];
        foreach ($data as $col => $val) {
            $sets[] = "`{$col}`=?";
            if (is_int($val)) {
                $typesU .= 'i';
                $valsU[] = $val;
            } elseif (is_float($val)) {
                $typesU .= 'd';
                $valsU[] = $val;
            } else {
                $typesU .= 's';
                $valsU[] = (string)$val;
            }
        }
        if (isset($orden_cols['fecha_actualizacion'])) {
            $sets[] = '`fecha_actualizacion`=?';
            $typesU .= 's';
            $valsU[] = date('Y-m-d H:i:s');
        }
        if (empty($sets)) {
            header('Location: ?page=ordenes&t=err&m=Nada+que+actualizar');
            exit;
        }
        $sql = "UPDATE `{$orden_table_name}` SET " . implode(',', $sets) . " WHERE `{$idField}`=? LIMIT 1";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            header('Location: ?page=ordenes&t=err&m=No+se+pudo+actualizar');
            exit;
        }
        $typesU .= 'i';
        $valsU[] = $id;
        $stmt->bind_param($typesU, ...$valsU);
        $ok = $stmt->execute();
        $stmt->close();

        // Notificar cambios a n8n (p. ej. cambio de estado) para email al cliente.
        if ($ok && $changes !== []) {
            $codigoWebhook = '';
            if (isset($data['codigo_seguimiento']) && is_string($data['codigo_seguimiento'])) {
                $codigoWebhook = $data['codigo_seguimiento'];
            } elseif (is_array($before) && isset($before['codigo_seguimiento'])) {
                $codigoWebhook = (string)$before['codigo_seguimiento'];
            } else {
                $codigoWebhook = (string)$id;
            }

            $equipoForLookup = $id_equipo > 0 ? $id_equipo : (int)($before['id_equipo'] ?? 0);
            $clienteEmail = '';
            $clienteNombre = '';
            $clienteId = 0;
            if ($equipoForLookup > 0) {
                $eqTbl = pick_table($conn, ['equipo', 'Equipo']);
                $cliTbl = pick_table($conn, ['cliente', 'Cliente']);
                if ($eqTbl !== '' && $cliTbl !== '') {
                    $eqCols = table_columns($conn, $eqTbl);
                    $cliCols = table_columns($conn, $cliTbl);

                    $eqIdCol = 'id_equipo';
                    foreach (array_keys($eqCols) as $k) {
                        if (strcasecmp((string)$k, 'id_equipo') === 0) {
                            $eqIdCol = $k;
                            break;
                        }
                    }
                    $cliIdCol = 'id_cliente';
                    foreach (array_keys($cliCols) as $k) {
                        if (strcasecmp((string)$k, 'id_cliente') === 0) {
                            $cliIdCol = $k;
                            break;
                        }
                    }

                    $eqCliCol = null;
                    foreach (array_keys($eqCols) as $k) {
                        if (strcasecmp((string)$k, 'id_cliente') === 0) {
                            $eqCliCol = $k;
                            break;
                        }
                    }

                    $cliEmailCol = repairly_pick_column($cliCols, ['email', 'correo', 'correo_electronico', 'mail']);
                    $cliNombreCol = repairly_pick_column($cliCols, ['nombre', 'name', 'nombre_cliente']);

                    if ($eqCliCol !== null && $cliEmailCol !== null) {
                        $sqlCli = "SELECT c.`{$cliIdCol}` AS id_cliente"
                            . ($cliNombreCol !== null ? ", c.`{$cliNombreCol}` AS nombre" : ", '' AS nombre")
                            . ", c.`{$cliEmailCol}` AS email"
                            . " FROM `{$eqTbl}` e"
                            . " JOIN `{$cliTbl}` c ON c.`{$cliIdCol}` = e.`{$eqCliCol}`"
                            . " WHERE e.`{$eqIdCol}`=? LIMIT 1";
                        $stCli = $conn->prepare($sqlCli);
                        if ($stCli) {
                            $stCli->bind_param('i', $equipoForLookup);
                            $stCli->execute();
                            $resCli = $stCli->get_result();
                            $rowCli = $resCli ? ($resCli->fetch_assoc() ?: null) : null;
                            $stCli->close();
                            if ($rowCli) {
                                $clienteId = (int)($rowCli['id_cliente'] ?? 0);
                                $clienteNombre = (string)($rowCli['nombre'] ?? '');
                                $clienteEmail = (string)($rowCli['email'] ?? '');
                            }
                        }
                    }
                }
            }

            $beforeEstado = is_array($before) ? (int)($before['id_estado_actual'] ?? 0) : 0;
            $afterEstado = $id_estado;

            $webhookData = [
                'event' => 'orden_actualizada',
                'id_orden' => $id,
                'codigo' => $codigoWebhook,
                'equipo' => $equipoForLookup,
                'tecnico' => $id_tecnico > 0 ? $id_tecnico : (int)($before['id_tecnico'] ?? 0),
                'estado_before' => $beforeEstado,
                'estado_after' => $afterEstado,
                'changes' => $changes,
                'id_cliente' => $clienteId,
                'cliente_nombre' => $clienteNombre,
                'cliente_email' => $clienteEmail,
            ];

            $options = [
                'http' => [
                    'header'  => "Content-type: application/json",
                    'method'  => 'POST',
                    'content' => json_encode($webhookData),
                    'ignore_errors' => true,
                ],
            ];
            $context = stream_context_create($options);
            @file_get_contents(
                'https://simple-n8n-production-edc5.up.railway.app/webhook/nueva-reparacion',
                false,
                $context
            );
        }

        header('Location: ?page=ordenes&t=' . ($ok ? 'ok' : 'err') . '&m=' . ($ok ? 'Orden+actualizada' : 'Error+al+actualizar'));
        exit;
    }

    if ($post_action === 'delete') {
        $id = (int)($_POST['id_orden'] ?? 0);
        if ($id <= 0) {
            header('Location: ?page=ordenes&t=err&m=ID+inv%C3%A1lido');
            exit;
        }
        $stmt = $conn->prepare("DELETE FROM `{$orden_table_name}` WHERE `{$idField}`=? LIMIT 1");
        if (!$stmt) {
            header('Location: ?page=ordenes&t=err&m=No+se+pudo+eliminar');
            exit;
        }
        $stmt->bind_param('i', $id);
        $ok = $stmt->execute();
        $stmt->close();
        header('Location: ?page=ordenes&t=' . ($ok ? 'ok' : 'err') . '&m=' . ($ok ? 'Orden+eliminada' : 'Error+al+eliminar'));
        exit;
    }
}
