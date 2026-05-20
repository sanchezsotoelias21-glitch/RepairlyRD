<?php
// src/pages/reportes.php - Sistema integral de reportes profesionales

declare(strict_types=1);

// Reportes disponibles (resuelve tablas/columnas segÃºn el esquema real en MySQL).
$report_types = [
    'clientes' => [
        'label' => 'Reporte de Clientes',
        'desc' => 'Listado de clientes con información de contacto',
        'icon' => 'ti-users',
        'table_candidates' => ['cliente', 'Cliente'],
        'fields' => [
            ['label' => 'ID', 'candidates' => ['id_cliente', 'id']],
            ['label' => 'Nombre', 'candidates' => ['nombre', 'name']],
            ['label' => 'TelÃ©fono', 'candidates' => ['telefono', 'tel', 'phone']],
            ['label' => 'Email', 'candidates' => ['email', 'correo']],
            ['label' => 'Fecha Registro', 'candidates' => ['fecha_registro', 'fecha_creacion', 'created_at']],
        ],
    ],
    'tecnicos' => [
        'label' => 'Reporte de Técnicos',
        'desc' => 'Listado de técnicos con especialidades',
        'icon' => 'ti-user-check',
        'table_candidates' => ['tecnico', 'Tecnico'],
        'fields' => [
            ['label' => 'ID', 'candidates' => ['id_tecnico', 'id']],
            ['label' => 'Nombre', 'candidates' => ['nombre', 'name']],
            ['label' => 'Especialidad', 'candidates' => ['especialidad', 'area']],
            ['label' => 'Teléfono', 'candidates' => ['telefono', 'tel', 'phone']],
            ['label' => 'Estado', 'candidates' => ['estado', 'status']],
        ],
    ],
    'equipos' => [
        'label' => 'Reporte de Equipos',
        'desc' => 'Inventario de equipos ingresados',
        'icon' => 'ti-device-laptop',
        'table_candidates' => ['equipo', 'Equipo'],
        'fields' => [
            ['label' => 'ID', 'candidates' => ['id_equipo', 'id']],
            ['label' => 'Tipo', 'candidates' => ['tipo', 'type']],
            ['label' => 'Marca', 'candidates' => ['marca', 'brand']],
            ['label' => 'Modelo', 'candidates' => ['modelo', 'model']],
            ['label' => 'Cliente', 'candidates' => ['id_cliente', 'cliente_id']],
            ['label' => 'Estado', 'candidates' => ['estado', 'status']],
        ],
    ],
    'ordenes' => [
        'label' => 'Reporte de Órdenes de Reparación',
        'desc' => 'Órdenes completadas, pendientes y en proceso',
        'icon' => 'ti-clipboard-list',
        'table_candidates' => ['orden_reparacion', 'Orden_Reparacion', 'reparacion', 'Reparacion', 'orden'],
        'fields' => [
            ['label' => 'ID', 'candidates' => ['id_orden', 'id_reparacion', 'id']],
            ['label' => 'Código', 'candidates' => ['codigo_seguimiento', 'codigo']],
            ['label' => 'Equipo', 'candidates' => ['id_equipo', 'equipo_id']],
            ['label' => 'Técnico', 'candidates' => ['id_tecnico', 'tecnico_id']],
            ['label' => 'Estado', 'candidates' => ['estado', 'id_estado_actual', 'status']],
            ['label' => 'Total', 'candidates' => ['costo_total', 'costo', 'total']],
            ['label' => 'Ingreso', 'candidates' => ['fecha_ingreso', 'fecha_creacion', 'created_at']],
            ['label' => 'Salida', 'candidates' => ['fecha_entrega_real', 'fecha_salida', 'fecha_actualizacion', 'updated_at']],
        ],
        'has_date_range' => true,
        'has_status_filter' => true,
    ],
    'piezas' => [
        'label' => 'Reporte de Piezas e Inventario',
        'desc' => 'Movimientos y stock de piezas',
        'icon' => 'ti-package',
        'table_candidates' => ['pieza', 'Pieza'],
        'fields' => [
            ['label' => 'ID', 'candidates' => ['id_pieza', 'id']],
            ['label' => 'Nombre', 'candidates' => ['nombre', 'name']],
            ['label' => 'Referencia', 'candidates' => ['referencia', 'ref', 'sku']],
            ['label' => 'Stock', 'candidates' => ['stock', 'cantidad']],
            ['label' => 'P. Compra', 'candidates' => ['precio_compra', 'costo_compra']],
            ['label' => 'P. Venta', 'candidates' => ['precio_venta', 'precio']],
        ],
    ],
    'garantias' => [
        'label' => 'Reporte de Garantías',
        'desc' => 'Garantías activas y vencidas',
        'icon' => 'ti-shield-check',
        'table_candidates' => ['garantia', 'Garantia'],
        'fields' => [
            ['label' => 'ID', 'candidates' => ['id_garantia', 'id']],
            ['label' => 'Orden', 'candidates' => ['id_orden', 'id_reparacion', 'id_servicio']],
            ['label' => 'Tipo', 'candidates' => ['tipo']],
            ['label' => 'Estado', 'candidates' => ['estado', 'status']],
            ['label' => 'Inicio', 'candidates' => ['fecha_inicio', 'inicio']],
            ['label' => 'Vencimiento', 'candidates' => ['fecha_vencimiento', 'vencimiento', 'fin']],
        ],
    ],
];

/**
 * Convierte un reporte "lógico" a un reporte "resuelto" (tabla + columnas reales).
 *
 * @return array{label:string,desc:string,icon:string,table:string,columns:list<string>,display_cols:list<string>,has_date_range?:bool,has_status_filter?:bool,date_col?:string|null,status_col?:string|null,status_mode?:string|null}|null
 */
function repairly_resolve_report(mysqli $conn, array $cfg): ?array
{
    $table = pick_table($conn, $cfg['table_candidates'] ?? []);
    if ($table === '') {
        return null;
    }

    $cols = table_columns($conn, $table);
    $resolvedCols = [];
    $displayCols = [];
    foreach (($cfg['fields'] ?? []) as $f) {
        if (!is_array($f)) {
            continue;
        }
        $candidates = $f['candidates'] ?? [];
        if (!is_array($candidates)) {
            $candidates = [];
        }
        $col = repairly_pick_column($cols, array_values(array_map('strval', $candidates)));
        if ($col) {
            $resolvedCols[] = $col;
            $displayCols[] = (string)($f['label'] ?? $col);
        }
    }

    if (empty($resolvedCols)) {
        return null;
    }

    $dateCol = null;
    foreach (['fecha_ingreso', 'fecha_registro', 'fecha_creacion', 'fecha_inicio', 'created_at'] as $cand) {
        $pick = repairly_pick_column($cols, [$cand]);
        if ($pick) {
            $dateCol = $pick;
            break;
        }
    }

    $statusCol = null;
    $statusMode = null;
    $statusText = repairly_pick_column($cols, ['estado', 'status']);
    if ($statusText) {
        $statusCol = $statusText;
        $statusMode = 'text';
    } else {
        $statusId = repairly_pick_column($cols, ['id_estado_actual', 'estado_id', 'id_estado']);
        if ($statusId) {
            $statusCol = $statusId;
            $statusMode = 'id';
        }
    }

    return [
        'label' => (string)($cfg['label'] ?? 'Reporte'),
        'desc' => (string)($cfg['desc'] ?? ''),
        'icon' => (string)($cfg['icon'] ?? 'ti-file'),
        'table' => $table,
        'columns' => $resolvedCols,
        'display_cols' => $displayCols,
        'has_date_range' => (bool)($cfg['has_date_range'] ?? false),
        'has_status_filter' => (bool)($cfg['has_status_filter'] ?? false),
        'date_col' => $dateCol,
        'status_col' => $statusCol,
        'status_mode' => $statusMode,
    ];
}

// Obtener parámetros de la solicitud
$report_type = isset($_GET['type']) && is_string($_GET['type']) ? trim($_GET['type']) : '';
$action = isset($_GET['action']) && is_string($_GET['action']) ? trim($_GET['action']) : '';
$date_from = isset($_GET['date_from']) && is_string($_GET['date_from']) ? trim($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) && is_string($_GET['date_to']) ? trim($_GET['date_to']) : '';
$status_filter = isset($_GET['status']) && is_string($_GET['status']) ? trim($_GET['status']) : '';

// Validar tipo de reporte
$current_report = null;
if ($report_type && isset($report_types[$report_type])) {
    $current_report = repairly_resolve_report($conn, $report_types[$report_type]);
}


// ============================
// KPIs DEL DASHBOARD
// ============================
$stats = [
    'ordenes' => 0,
    'completadas' => 0,
    'pendientes' => 0,
    'ingresos' => 0,
];

try {
    $ordenTbl = pick_table($conn, ['orden_reparacion', 'Orden_Reparacion', 'reparacion', 'Reparacion', 'orden']);
    if ($ordenTbl !== '') {
        $stats['ordenes'] = (int)db_scalar($conn, "SELECT COUNT(*) FROM `{$ordenTbl}`", 0);

        $ordenCols = table_columns($conn, $ordenTbl);
        $estadoCol = repairly_pick_column($ordenCols, ['estado']);
        if ($estadoCol) {
            $estadoSafe = str_replace('`', '``', $estadoCol);
            $stats['completadas'] = (int)db_scalar($conn, "SELECT COUNT(*) FROM `{$ordenTbl}` WHERE `{$estadoSafe}`='Completado'", 0);
            $stats['pendientes'] = (int)db_scalar($conn, "SELECT COUNT(*) FROM `{$ordenTbl}` WHERE `{$estadoSafe}`='Pendiente'", 0);
        }

        $totalCol = repairly_pick_column($ordenCols, ['costo_total', 'costo', 'total']);
        if ($totalCol) {
            $totalSafe = str_replace('`', '``', $totalCol);
            $stats['ingresos'] = (float)db_scalar($conn, "SELECT COALESCE(SUM(`{$totalSafe}`),0) FROM `{$ordenTbl}`", 0);
        }
    }

} catch (Throwable $e) {
}


// Función auxiliar para obtener datos del reporte
function get_report_data(mysqli $conn, array $report_config, string $date_from, string $date_to, string $status_filter): array {
    $table = $report_config['table'] ?? '';
    if (!is_string($table) || $table === '') {
        return [];
    }

    $cols = $report_config['columns'] ?? [];
    if (!is_array($cols) || empty($cols)) {
        return [];
    }

    $select = implode(', ', array_map(static function ($c): string {
        $c = str_replace('`', '``', (string)$c);
        return "`{$c}`";
    }, $cols));

    $sql = "SELECT {$select} FROM `{$table}` WHERE 1=1";
    $params = [];
    $types = '';

    $dateCol = $report_config['date_col'] ?? null;
    if (($report_config['has_date_range'] ?? false) && is_string($dateCol) && $dateCol !== '') {
        $dateSafe = str_replace('`', '``', $dateCol);
        if ($date_from !== '') {
            $sql .= " AND `{$dateSafe}` >= ?";
            $params[] = $date_from . ' 00:00:00';
            $types .= 's';
        }
        if ($date_to !== '') {
            $sql .= " AND `{$dateSafe}` <= ?";
            $params[] = $date_to . ' 23:59:59';
            $types .= 's';
        }
    }

    $statusCol = $report_config['status_col'] ?? null;
    $statusMode = $report_config['status_mode'] ?? null;
    if (($report_config['has_status_filter'] ?? false) && $status_filter !== '' && is_string($statusCol) && $statusCol !== '') {
        $statusSafe = str_replace('`', '``', $statusCol);
        if ($statusMode === 'id') {
            $sql .= " AND `{$statusSafe}` = ?";
            $params[] = (int)$status_filter;
            $types .= 'i';
        } else {
            $sql .= " AND `{$statusSafe}` = ?";
            $params[] = $status_filter;
            $types .= 's';
        }
    }

    $orderBy = str_replace('`', '``', (string)$cols[0]);
    $sql .= " ORDER BY `{$orderBy}` DESC LIMIT 1000";

    if (empty($params)) {
        return db_rows($conn, $sql);
    }

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return [];
    }
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();
    $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    $stmt->close();
    return $rows;
}

// Procesar descarga de PDF
if ($action === 'generate_pdf' && $current_report) {
    // Evita que se mezclen HTML+PDF (index.php puede haber escrito markup al buffer).
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    $report_data = get_report_data($conn, $current_report, $date_from, $date_to, $status_filter);
    
    // Generar PDF simple
    require_once __DIR__ . '/../../includes/pdf_generator.php';
    
    $pdf = new SimplePDF();
    $pdf->addPage();
    
    // Encabezado
    $pdf->setFont('Arial', 'B', 16);
    $pdf->cell(0, 10, $current_report['label'], 0, 1);
    
    $pdf->setFont('Arial', '', 10);
    $pdf->cell(0, 5, 'Generado: ' . date('d/m/Y H:i:s'), 0, 1);
    
    if ($date_from) {
        $pdf->cell(0, 5, 'Desde: ' . $date_from, 0, 1);
    }
    if ($date_to) {
        $pdf->cell(0, 5, 'Hasta: ' . $date_to, 0, 1);
    }
    $pdf->ln(5);
    
    // Tabla
    $pdf->setFont('Arial', 'B', 9);
    $col_width = 180 / count($current_report['display_cols']);
    foreach ($current_report['display_cols'] as $col) {
        $pdf->cell($col_width, 7, $col, 1);
    }
    $pdf->ln();
    
    $pdf->setFont('Arial', '', 8);
    foreach ($report_data as $row) {
        foreach ($current_report['columns'] as $key) {
            $val = $row[$key] ?? '';
            $pdf->cell($col_width, 6, substr((string)$val, 0, 20), 1);
        }
        $pdf->ln();
    }
    
    $filename = 'Reporte_' . $report_type . '_' . date('Ymd_His') . '.pdf';
    $bytes = $pdf->output();

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($bytes));
    header('X-Content-Type-Options: nosniff');
    echo $bytes;
    exit;
}

// Obtener datos del reporte si está seleccionado
$report_data = [];
if ($current_report) {
    $report_data = get_report_data($conn, $current_report, $date_from, $date_to, $status_filter);
}

// Estados posibles para filtrado (se resuelve si la tabla usa id_estado_actual).
$status_options = [];
$status_options_id_to_name = [];
if ($current_report && ($current_report['has_status_filter'] ?? false)) {
    if (($current_report['status_mode'] ?? null) === 'id') {
        $estTbl = pick_table($conn, ['estado_servicio', 'estado', 'Estado_Servicio']);
        if ($estTbl !== '') {
            foreach (db_rows($conn, "SELECT * FROM `{$estTbl}` ORDER BY id_estado ASC LIMIT 500") as $r) {
                $idv = (int)($r['id_estado'] ?? 0);
                $nm = (string)($r['nombre_estado'] ?? $idv);
                if ($idv > 0) {
                    $status_options_id_to_name[(string)$idv] = $nm;
                }
            }
        }
    } else {
        $status_options = [
            'Pendiente', 'En proceso', 'Listo', 'Entregado', 'Completado',
            'Cancelado', 'En espera', 'Diagnóstico', 'Activo', 'Inactivo'
        ];
    }
}
?>

<div class="topbar" style="background:#fff;border-bottom:0.5px solid #EDECEA;padding:16px 20px;display:flex;align-items:center;justify-content:space-between;">
    <div>
        <div class="card-title" style="margin:0;">📊 Generador de Reportes Profesionales</div>
        <div class="card-sub">Crea reportes en PDF de cualquier módulo del sistema</div>
    </div>
</div>

<div style="padding:20px;max-width:1400px;margin:0 auto;">
    
    <!-- Panel de Selección de Reporte -->
    <div class="charts-card" style="margin-bottom:20px;padding:0;">
        <div style="padding:16px 20px;border-bottom:0.5px solid #EDECEA;">
            <div class="card-title">Paso 1: Selecciona el tipo de reporte</div>
        </div>
        
        <div style="padding:20px;display:grid;grid-template-columns:repeat(auto-fit, minmax(280px, 1fr));gap:12px;">
            <?php foreach ($report_types as $key => $config): ?>
            <a href="?page=reportes&type=<?= urlencode($key) ?>" 
               style="padding:16px;border:1.5px solid <?= ($report_type === $key ? '#2b7abc' : '#D0CCC6') ?>;border-radius:10px;text-decoration:none;transition:all 0.2s;background:<?= ($report_type === $key ? '#E3F2FD' : '#fff') ?>;cursor:pointer;"
               onmouseover="this.style.borderColor='#2b7abc';this.style.background='#E3F2FD';"
               onmouseout="this.style.borderColor='<?= ($report_type === $key ? '#2b7abc' : '#D0CCC6') ?>';this.style.background='<?= ($report_type === $key ? '#E3F2FD' : '#fff') ?>';">
                <div style="display:flex;align-items:center;gap:12px;margin-bottom:8px;">
                    <i class="ti <?= $config['icon'] ?>" style="font-size:24px;color:#2b7abc;"></i>
                    <div>
                        <div style="font-weight:600;color:#1C1A17;font-size:14px;"><?= h($config['label']) ?></div>
                        <div style="font-size:12px;color:#8C8479;"><?= h($config['desc']) ?></div>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>

    <?php if ($current_report): ?>
    
    <!-- Panel de Filtros -->
    <div class="charts-card" style="margin-bottom:20px;padding:0;">
        <div style="padding:16px 20px;border-bottom:0.5px solid #EDECEA;">
            <div class="card-title">Paso 2: Configura filtros (opcional)</div>
        </div>
        
        
<!-- KPI CARDS -->
<style>
.card-stat{
    background:#fff;
    border-radius:18px;
    padding:22px;
    border:1px solid #E5E7EB;
    box-shadow:0 4px 14px rgba(0,0,0,.05);
}
.card-stat div{
    color:#6B7280;
    font-size:14px;
    margin-bottom:8px;
}
.card-stat h2{
    margin:0;
    font-size:28px;
    color:#111827;
}
</style>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:18px;margin-bottom:22px;">
    <div class="card-stat">
        <div>Total Órdenes</div>
        <h2><?= number_format($stats['ordenes']) ?></h2>
    </div>

    <div class="card-stat">
        <div>Completadas</div>
        <h2><?= number_format($stats['completadas']) ?></h2>
    </div>

    <div class="card-stat">
        <div>Pendientes</div>
        <h2><?= number_format($stats['pendientes']) ?></h2>
    </div>

    <div class="card-stat">
        <div>Ingresos</div>
        <h2>RD$ <?= number_format($stats['ingresos'],2) ?></h2>
    </div>
</div>

<form method="get" style="padding:20px;display:grid;grid-template-columns:repeat(auto-fit, minmax(250px, 1fr));gap:16px;align-items:end;">
            <input type="hidden" name="page" value="reportes">
            <input type="hidden" name="type" value="<?= h($report_type) ?>">
            
            <?php if ($current_report['has_date_range'] ?? false): ?>
            <div>
                <label style="display:block;font-size:12px;font-weight:600;color:#4D4841;margin-bottom:6px;">Desde:</label>
                <input type="date" name="date_from" value="<?= h($date_from) ?>" 
                       style="width:100%;padding:10px;border:0.5px solid #D0CCC6;border-radius:8px;font-size:13px;">
            </div>
            
            <div>
                <label style="display:block;font-size:12px;font-weight:600;color:#4D4841;margin-bottom:6px;">Hasta:</label>
                <input type="date" name="date_to" value="<?= h($date_to) ?>" 
                       style="width:100%;padding:10px;border:0.5px solid #D0CCC6;border-radius:8px;font-size:13px;">
            </div>
            <?php endif; ?>
            
            <?php if ($current_report['has_status_filter'] ?? false): ?>
            <div>
                <label style="display:block;font-size:12px;font-weight:600;color:#4D4841;margin-bottom:6px;">Estado:</label>
                <select name="status" style="width:100%;padding:10px;border:0.5px solid #D0CCC6;border-radius:8px;font-size:13px;">
                    <option value="">-- Todos los estados --</option>
                    <?php if (($current_report['status_mode'] ?? null) === 'id'): ?>
                        <?php foreach ($status_options_id_to_name as $sid => $sname): ?>
                        <option value="<?= h((string)$sid) ?>" <?= ((string)$status_filter === (string)$sid ? 'selected' : '') ?>>
                            <?= h((string)$sname) ?>
                        </option>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <?php foreach ($status_options as $status): ?>
                        <option value="<?= h($status) ?>" <?= ($status_filter === $status ? 'selected' : '') ?>>
                            <?= h($status) ?>
                        </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <?php endif; ?>
            
            <div style="display:flex;gap:8px;">
                <button type="submit" style="padding:10px 16px;background:#1F5C8B;color:#fff;border:none;border-radius:8px;font-weight:600;cursor:pointer;flex:1;">
                    <i class="ti ti-search"></i> Filtrar
                </button>
                <a href="?page=reportes&type=<?= h($report_type) ?>" 
                   style="padding:10px 16px;background:#D0CCC6;color:#4D4841;text-decoration:none;border-radius:8px;font-weight:600;text-align:center;">
                    <i class="ti ti-x"></i> Limpiar
                </a>
            </div>
        </form>
    </div>

    <!-- Vista Previa de Datos -->
    <div class="charts-card" style="margin-bottom:20px;padding:0;">
        <div style="padding:16px 20px;border-bottom:0.5px solid #EDECEA;display:flex;justify-content:space-between;align-items:center;">
            <div>
                <div class="card-title">Paso 3: Previsualiza los datos</div>
                <div class="card-sub"><?= count($report_data) ?> registros encontrados</div>
            </div>
            <?php if (!empty($report_data)): ?>
            <a href="?page=reportes&type=<?= h($report_type) ?>&action=generate_pdf<?= ($date_from ? '&date_from=' . urlencode($date_from) : '') ?><?= ($date_to ? '&date_to=' . urlencode($date_to) : '') ?><?= ($status_filter ? '&status=' . urlencode($status_filter) : '') ?>"
               style="padding:12px 20px;background:#00AA44;color:#fff;text-decoration:none;border-radius:8px;font-weight:600;display:inline-flex;align-items:center;gap:8px;cursor:pointer;">
                <i class="ti ti-download"></i> Descargar PDF
            </a>
            <?php endif; ?>
        </div>
        
        <?php if (empty($report_data)): ?>
        <div style="padding:40px;text-align:center;color:#8C8479;">
            <i class="ti ti-inbox" style="font-size:48px;opacity:0.5;display:block;margin-bottom:12px;"></i>
            <p>No hay datos que mostrar con los filtros seleccionados</p>
        </div>
        <?php else: ?>
        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;font-size:12px;">
                <thead>
                    <tr style="background:#F5F5F5;border-bottom:0.5px solid #D0CCC6;">
                        <?php foreach ($current_report['display_cols'] as $col): ?>
                        <th style="padding:12px;text-align:left;font-weight:600;color:#4D4841;border:0.5px solid #D0CCC6;">
                            <?= h($col) ?>
                        </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php $row_count = 0; ?>
                    <?php foreach ($report_data as $row): ?>
                    <?php $row_count++; if ($row_count > 100) break; ?>
                    <tr style="border-bottom:0.5px solid #EDECEA;<?= ($row_count % 2 === 0 ? 'background:#FAFAF8;' : '') ?>">
                        <?php foreach ($current_report['columns'] as $col): ?>
                        <td style="padding:10px 12px;color:#1C1A17;">
                            <?= h((string)($row[$col] ?? '—')) ?>
                        </td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php if (count($report_data) > 100): ?>
            <div style="padding:12px 20px;background:#FFF3E0;color:#FF9500;font-size:12px;border-top:0.5px solid #D0CCC6;">
                ⚠️ Se muestran 100 primeros registros. El PDF descargado contiene todos (hasta 1000).
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <?php endif; ?>

</div>
