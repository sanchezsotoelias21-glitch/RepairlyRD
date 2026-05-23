<?php
// src/pages/reportes.php - Sistema integral de reportes profesionales



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
$date_from = !empty($_GET['date_from']) ? $_GET['date_from'] : null;
$date_to = !empty($_GET['date_to']) ? $_GET['date_to'] : null;
$status_filter = isset($_GET['status']) && is_string($_GET['status']) ? trim($_GET['status']) : '';
$status_filter = mysqli_real_escape_string($conn, $status_filter);

if ($action === 'csv' && $current_report) {

    $data = get_report_data(
        $conn,
        $current_report,
        $date_from,
        $date_to,
        $status_filter
    );

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="reporte.csv"');

    $output = fopen('php://output', 'w');

    fputcsv($output, $current_report['display_cols']);

    foreach ($data as $row) {
        fputcsv($output, $row);
    }

    fclose($output);
    exit;
}

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
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="reporte.pdf"');
    // Evita que se mezclen HTML+PDF (index.php puede haber escrito markup al buffer).
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    try {
        $report_data = get_report_data($conn, $current_report, $date_from, $date_to, $status_filter);

        // ---- Generar PDF profesional con RepairlyPDF (FPDF) ----
        require_once __DIR__ . '/../../includes/pdf_generator.php';

        $pdf = new RepairlyPDF('P', 'mm', 'A4');
        $pdf->AliasNbPages();

        // Subtítulo dinámico con filtros y cantidad
        $subtitleParts = [];
        if ($date_from || $date_to) {
            $range = trim(($date_from ? 'Desde ' . $date_from : '') . ($date_to ? '  Hasta ' . $date_to : ''));
            $subtitleParts[] = $range;
        }
        $subtitleParts[] = count($report_data) . ' registros encontrados';
        $subtitle = implode('  ·  ', $subtitleParts);

        $pdf->setReportMeta($current_report['label'], $subtitle);
        $pdf->AddPage();

        // ---- Bloque de KPIs (solo para órdenes, siempre mostramos resumen básico) ----
        $kpiStats = [];
        if ($report_type === 'ordenes') {
            $total      = count($report_data);
            $completadas = 0;
            $pendientes  = 0;
            $ingresos    = 0.0;
            foreach ($report_data as $r) {
                $est = strtolower((string)($r['estado'] ?? $r['id_estado_actual'] ?? ''));
                if (strpos($est, 'complet') !== false || strpos($est, 'entrega') !== false || strpos($est, 'listo') !== false) {
                    $completadas++;
                }
                if (strpos($est, 'pendient') !== false || strpos($est, 'espera') !== false) {
                    $pendientes++;
                }
                $ingresos += (float)($r['costo_total'] ?? $r['costo'] ?? $r['total'] ?? 0);
            }
            $kpiStats = [
                ['label' => 'Total órdenes',  'value' => (string)$total,                       'color' => 'primary'],
                ['label' => 'Completadas',    'value' => (string)$completadas,                  'color' => 'green'],
                ['label' => 'Pendientes',     'value' => (string)$pendientes,                   'color' => 'orange'],
                ['label' => 'Ingresos',       'value' => 'RD$ ' . number_format($ingresos, 0, '.', ','), 'color' => 'primary'],
            ];
        } elseif ($report_type === 'piezas') {
            $total     = count($report_data);
            $stockBajo = 0;
            $stockOk   = 0;
            foreach ($report_data as $r) {
                $stock = (int)($r['stock'] ?? $r['cantidad'] ?? 0);
                if ($stock <= 5) {
                    $stockBajo++;
                } else {
                    $stockOk++;
                }
            }
            $kpiStats = [
                ['label' => 'Total piezas',  'value' => (string)$total,     'color' => 'primary'],
                ['label' => 'Stock OK',      'value' => (string)$stockOk,   'color' => 'green'],
                ['label' => 'Stock bajo',    'value' => (string)$stockBajo,  'color' => 'orange'],
            ];
        } else {
            $kpiStats = [
                ['label' => 'Total registros', 'value' => (string)count($report_data), 'color' => 'primary'],
            ];
        }
        $pdf->addKpiRow($kpiStats);

        // ---- Filtros activos ----
        $statusDisplay = $status_filter;
        if (($current_report['status_mode'] ?? null) === 'id' && isset($status_options_id_to_name[$status_filter])) {
            $statusDisplay = (string)$status_options_id_to_name[$status_filter];
        }
        $pdf->addFilterBadges($date_from, $date_to, $statusDisplay);

        // ---- Preparar filas para la tabla ----
        $headers   = $current_report['display_cols'];
        $colKeys   = $current_report['columns'];
        $tableRows = [];
        foreach ($report_data as $row) {
            $tableRow = [];
            foreach ($colKeys as $key) {
                $tableRow[] = (string)($row[$key] ?? '—');
            }
            $tableRows[] = $tableRow;
        }

        // Calcular anchos de columna inteligentes (columnas pequeñas: ID, Stock, etc.)
        $narrowCols = ['id', 'id ', 'stock', 'estado', 'tipo', 'p. compra', 'p. venta'];
        $usable     = 186;
        $colWidths  = [];
        $narrowW    = 22;
        $narrowCount = 0;
        foreach ($headers as $h) {
            if (in_array(strtolower($h), $narrowCols, true)) {
                $narrowCount++;
            }
        }
        $wideCount = count($headers) - $narrowCount;
        $wideW     = $wideCount > 0
            ? (int)round(($usable - $narrowCount * $narrowW) / $wideCount)
            : (int)round($usable / count($headers));
        foreach ($headers as $h) {
            $colWidths[] = in_array(strtolower($h), $narrowCols, true) ? $narrowW : $wideW;
        }

        $pdf->addDataTable($headers, $tableRows, $colWidths);

        // ---- Entregar PDF al navegador ----
        $filename = 'Repairly_' . ucfirst($report_type) . '_' . date('Ymd_His') . '.pdf';
        
        // Generar PDF en memoria
        $bytes = $pdf->Output('S');
        
        // Validar que Output() retornó datos válidos
        if (!is_string($bytes) || strlen($bytes) === 0) {
            throw new Exception('La generación del PDF no produjo datos válidos');
        }

        // Enviar headers de descarga
        header('Content-Type: application/pdf; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
        header('Content-Length: ' . strlen($bytes));
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        header('X-Content-Type-Options: nosniff');
        
        // Enviar datos
        echo $bytes;
        exit;
    } catch (Exception $e) {
        // Log del error
        error_log('Error generando PDF: ' . $e->getMessage());
        
        // Mostrar error amigable
        header('Content-Type: text/html; charset=utf-8');
        http_response_code(500);
        echo '<div style="padding:20px;font-family:Arial;color:#B83232;">';
        echo '<h2>Error al generar PDF</h2>';
        echo '<p>Hubo un problema al procesar tu solicitud.</p>';
        echo '<p style="font-size:12px;color:#666;">Detalles: ' . htmlspecialchars($e->getMessage()) . '</p>';
        echo '<a href="?page=reportes" style="color:#1F5C8B;">Volver a reportes</a>';
        echo '</div>';
        exit;
    }
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

<style>
/* Variables de color y estilos globales */
:root {
    --primary: #1F5C8B;
    --primary-light: #E3F2FD;
    --primary-dark: #1a3f5f;
    --success: #00AA44;
    --warning: #FF9500;
    --text-dark: #1C1A17;
    --text-muted: #8C8479;
    --border-light: #EDECEA;
    --border-gray: #D0CCC6;
    --bg-light: #F5F5F5;
    --bg-lighter: #FAFAF8;
}

.charts-card {
    background: #fff;
    border-radius: 14px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
    border: 1px solid var(--border-light);
    overflow: hidden;
    margin-bottom: 24px;
}

.card-title {
    color: var(--text-dark);
    font-weight: 700;
    font-size: 16px;
    margin: 0;
}

.card-sub {
    color: var(--text-muted);
    font-size: 13px;
    margin-top: 4px;
}

/* SELECTOR DE REPORTES */
.report-selector {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 16px;
    padding: 24px;
}

.report-item {
    padding: 20px;
    text-decoration: none;
    background: #fff;
    border: 2px solid var(--border-gray);
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 16px;
    position: relative;
    overflow: hidden;
}

.report-item::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, transparent 0%, rgba(31, 92, 139, 0.05) 100%);
    opacity: 0;
    transition: opacity 0.3s ease;
    pointer-events: none;
}

.report-item:hover {
    border-color: var(--primary);
    background: var(--primary-light);
    transform: translateY(-2px);
    box-shadow: 0 4px 16px rgba(31, 92, 139, 0.12);
}

.report-item:hover::before {
    opacity: 1;
}

.report-item.active {
    border-color: var(--primary);
    background: var(--primary-light);
    box-shadow: 0 4px 20px rgba(31, 92, 139, 0.15);
}

.report-item-icon {
    min-width: 48px;
    width: 48px;
    height: 48px;
    background: var(--primary-light);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    color: var(--primary);
    font-weight: 600;
}

.report-item.active .report-item-icon {
    background: var(--primary);
    color: #fff;
}

.report-item-content {
    flex: 1;
}

.report-item-label {
    font-weight: 600;
    color: var(--text-dark);
    font-size: 14px;
    margin-bottom: 4px;
}

.report-item-desc {
    font-size: 13px;
    color: var(--text-muted);
    line-height: 1.4;
}

/* FILTROS */
.filters-section {
    padding: 24px;
    background: linear-gradient(135deg, #f8f7f5 0%, #fafaf8 100%);
    border-bottom: 1px solid var(--border-light);
}

.filter-form {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    align-items: end;
}

.filter-group {
    display: flex;
    flex-direction: column;
}

.filter-label {
    display: block;
    font-size: 12px;
    font-weight: 700;
    color: var(--text-dark);
    margin-bottom: 8px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.filter-input, .filter-select {
    width: 100%;
    padding: 11px 14px;
    border: 1px solid var(--border-gray);
    border-radius: 8px;
    font-size: 13px;
    color: var(--text-dark);
    background: #fff;
    transition: all 0.2s ease;
}

.filter-input:focus, .filter-select:focus {
    outline: none;
    border-color: var(--primary);
    background: #fff;
    box-shadow: 0 0 0 3px rgba(31, 92, 139, 0.1);
}

.filter-buttons {
    display: flex;
    gap: 10px;
    grid-column: 1 / -1;
}

.btn-primary {
    padding: 11px 20px;
    background: var(--primary);
    color: #fff;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    font-size: 13px;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    flex: 1;
}

.btn-primary:hover {
    background: var(--primary-dark);
    box-shadow: 0 4px 12px rgba(31, 92, 139, 0.25);
}

.btn-secondary {
    padding: 11px 20px;
    background: #f0f0f0;
    color: var(--text-dark);
    border: 1px solid var(--border-gray);
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    font-size: 13px;
    text-decoration: none;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    flex: 1;
    justify-content: center;
}

.btn-secondary:hover {
    background: #e8e8e8;
    border-color: var(--text-muted);
}

/* FILTROS ACTIVOS */
.active-filters {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 16px;
    padding-top: 16px;
    border-top: 1px solid var(--border-light);
    grid-column: 1 / -1;
}

.filter-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: var(--primary);
    color: #fff;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 500;
}

/* ESTADÍSTICAS */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    gap: 16px;
    padding: 24px;
    background: #fff;
}

.stat-card {
    background: linear-gradient(135deg, #f8f7f5 0%, #fafaf8 100%);
    border-radius: 10px;
    padding: 18px;
    border: 1px solid var(--border-light);
    text-align: center;
    transition: all 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

.stat-label {
    color: var(--text-muted);
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 8px;
}

.stat-value {
    margin: 0;
    font-size: 24px;
    color: var(--primary);
    font-weight: 700;
}

/* TABLA DE DATOS */
.data-section {
    padding: 24px;
}

.data-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 20px;
    flex-wrap: wrap;
    gap: 16px;
}

.data-info {
    flex: 1;
}

.data-count {
    font-size: 28px;
    font-weight: 700;
    color: var(--primary);
    margin: 0;
}

.data-subtitle {
    color: var(--text-muted);
    font-size: 13px;
    margin-top: 4px;
}

.btn-download {
    padding: 12px 20px;
    background: var(--success);
    color: #fff;
    text-decoration: none;
    border-radius: 8px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 13px;
}

.btn-download:hover {
    background: #009938;
    box-shadow: 0 4px 12px rgba(0, 170, 68, 0.25);
}

/* TABLA */
.table-wrapper {
    overflow-x: auto;
    margin-bottom: 16px;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
}

.data-table thead tr {
    background: var(--bg-light);
    border-bottom: 2px solid var(--border-gray);
}

.data-table th {
    padding: 14px 16px;
    text-align: left;
    font-weight: 700;
    color: var(--text-dark);
    text-transform: uppercase;
    font-size: 11px;
    letter-spacing: 0.5px;
}

.data-table tbody tr {
    border-bottom: 1px solid var(--border-light);
    transition: background 0.2s ease;
}

.data-table tbody tr:nth-child(even) {
    background: var(--bg-lighter);
}

.data-table tbody tr:hover {
    background: var(--primary-light);
}

.data-table td {
    padding: 14px 16px;
    color: var(--text-dark);
}

/* ESTADO VACÍO */
.empty-state {
    padding: 60px 40px;
    text-align: center;
    color: var(--text-muted);
}

.empty-icon {
    font-size: 56px;
    opacity: 0.4;
    display: block;
    margin-bottom: 16px;
}

.empty-title {
    font-size: 16px;
    font-weight: 600;
    color: var(--text-dark);
    margin-bottom: 8px;
}

.empty-text {
    font-size: 13px;
    color: var(--text-muted);
    margin: 0;
}

/* ADVERTENCIA */
.warning-banner {
    padding: 14px 20px;
    background: #FFF3E0;
    color: var(--warning);
    font-size: 12px;
    border-top: 1px solid var(--border-light);
    display: flex;
    align-items: center;
    gap: 10px;
}

/* RESPONSIVE */
@media (max-width: 768px) {
    .report-selector {
        grid-template-columns: 1fr;
    }

    .filter-form {
        grid-template-columns: 1fr;
    }

    .filter-buttons {
        flex-direction: column;
    }

    .data-header {
        flex-direction: column;
    }

    .stats-grid {
        grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
        gap: 12px;
    }
}
</style>

<div class="main-container" style="padding: 20px; max-width: 1400px; margin: 0 auto;">
    
    <!-- SELECTOR DE REPORTES -->
    <div class="charts-card">
        <div style="padding: 24px; border-bottom: 1px solid var(--border-light);">
            <div class="card-title">
                <i class="ti ti-file-report" style="margin-right: 8px; color: var(--primary);"></i>
                Reportes Disponibles
            </div>
            <div class="card-sub">Selecciona el tipo de reporte que deseas consultar</div>
        </div>
        
        <div class="report-selector">
            <?php foreach ($report_types as $key => $config): ?>
            <a href="?page=reportes&type=<?= h($key) ?>" class="report-item <?= ($report_type === $key ? 'active' : '') ?>">
                <div class="report-item-icon">
                    <i class="ti <?= $config['icon'] ?>"></i>
                </div>
                <div class="report-item-content">
                    <div class="report-item-label"><?= h($config['label']) ?></div>
                    <div class="report-item-desc"><?= h($config['desc']) ?></div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>

    <?php if ($current_report): ?>
    
    <!-- PANEL DE FILTROS -->
    <div class="charts-card">
        <div style="padding: 24px; border-bottom: 1px solid var(--border-light);">
            <div class="card-title">
                <i class="ti ti-filter" style="margin-right: 8px; color: var(--primary);"></i>
                Filtros Avanzados
            </div>
            <div class="card-sub">Personaliza tu búsqueda para obtener resultados exactos</div>
        </div>
        
        <form method="get" class="filter-form" style="padding: 24px;">
            <input type="hidden" name="page" value="reportes">
            <input type="hidden" name="type" value="<?= h($report_type) ?>">
            
            <?php if ($current_report['has_date_range'] ?? false): ?>
            <div class="filter-group">
                <label class="filter-label">Desde</label>
                <input type="date" name="date_from" value="<?= h($date_from) ?>" class="filter-input">
            </div>
            
            <div class="filter-group">
                <label class="filter-label">Hasta</label>
                <input type="date" name="date_to" value="<?= h($date_to) ?>" class="filter-input">
            </div>
            <?php endif; ?>
            
            <?php if ($current_report['has_status_filter'] ?? false): ?>
            <div class="filter-group">
                <label class="filter-label">Estado</label>
                <select name="status" class="filter-select">
                    <option value="">Todos los estados</option>
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
            
            <div class="filter-buttons">
                <button type="submit" class="btn-primary">
                    <i class="ti ti-search"></i>
                    Aplicar Filtros
                </button>
                <a href="?page=reportes&type=<?= h($report_type) ?>" class="btn-secondary">
                    <i class="ti ti-x"></i>
                    Limpiar
                </a>
            </div>

            <!-- FILTROS ACTIVOS -->
            <?php 
            $has_filters = ($date_from || $date_to || $status_filter);
            if ($has_filters): 
            ?>
            <div class="active-filters">
                <?php if ($date_from): ?>
                <div class="filter-badge">
                    <i class="ti ti-calendar"></i>
                    Desde: <?= h($date_from) ?>
                </div>
                <?php endif; ?>
                <?php if ($date_to): ?>
                <div class="filter-badge">
                    <i class="ti ti-calendar"></i>
                    Hasta: <?= h($date_to) ?>
                </div>
                <?php endif; ?>
                <?php if ($status_filter): ?>
                <div class="filter-badge">
                    <i class="ti ti-status"></i>
                    <?php 
                    if (($current_report['status_mode'] ?? null) === 'id' && isset($status_options_id_to_name[$status_filter])) {
                        echo h((string)$status_options_id_to_name[$status_filter]);
                    } else {
                        echo h($status_filter);
                    }
                    ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </form>
    </div>

    <!-- ESTADÍSTICAS -->
    <div class="charts-card">
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Total Órdenes</div>
                <h2 class="stat-value"><?= number_format($stats['ordenes']) ?></h2>
            </div>
            <div class="stat-card">
                <div class="stat-label">Completadas</div>
                <h2 class="stat-value" style="color: var(--success);"><?= number_format($stats['completadas']) ?></h2>
            </div>
            <div class="stat-card">
                <div class="stat-label">Pendientes</div>
                <h2 class="stat-value" style="color: var(--warning);"><?= number_format($stats['pendientes']) ?></h2>
            </div>
            <div class="stat-card">
                <div class="stat-label">Ingresos</div>
                <h2 class="stat-value" style="color: #2b7abc;">RD$ <?= number_format($stats['ingresos'], 2) ?></h2>
            </div>
        </div>
    </div>

    <!-- TABLA DE RESULTADOS -->
    <div class="charts-card">
        <div class="data-section">
            <div class="data-header">
                <div class="data-info">
                    <h3 class="data-count"><?= count($report_data) ?></h3>
                    <p class="data-subtitle">
                        <?php 
                        if ($has_filters) {
                            echo 'Registros encontrados con filtros aplicados';
                        } else {
                            echo 'Registros totales';
                        }
                        ?>
                    </p>
                </div>
                <?php if (!empty($report_data)): ?>
                <a href="src/pages/reportes.php?type=<?= urlencode($report_type) ?>&action=generate_pdf<?= ($date_from ? '&date_from=' . urlencode($date_from) : '') ?><?= ($date_to ? '&date_to=' . urlencode($date_to) : '') ?><?= ($status_filter ? '&status=' . urlencode($status_filter) : '') ?>" 
                
                class="btn-download">
                    <i class="ti ti-download"></i>
                    Descargar PDF
                </a>
                <a href="?page=reportes&type=<?= h($report_type) ?>&action=csv"
                class="btn-download">
                Exportar CSV
                </a>
                <?php endif; ?>
            </div>
            
            <?php if (empty($report_data)): ?>
            <div class="empty-state">
                <i class="ti ti-inbox empty-icon"></i>
                <div class="empty-title">Sin resultados</div>
                <p class="empty-text">
                    <?php 
                    if ($has_filters) {
                        echo 'No hay datos que coincidan con los filtros aplicados. Intenta modificar tus criterios de búsqueda.';
                    } else {
                        echo 'No hay registros disponibles en esta categoría.';
                    }
                    ?>
                </p>
            </div>
            <?php else: ?>
            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <?php foreach ($current_report['display_cols'] as $col): ?>
                            <th><?= h($col) ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $row_count = 0; ?>
                        <?php foreach ($report_data as $row): ?>
                        <?php $row_count++; if ($row_count > 100) break; ?>
                        <tr>
                            <?php foreach ($current_report['columns'] as $col): ?>
                            <td><?= h((string)($row[$col] ?? '—')) ?></td>
                            <?php endforeach; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if (count($report_data) > 100): ?>
            <div class="warning-banner">
                <i class="ti ti-alert-triangle"></i>
                Se muestran 100 primeros registros. El PDF descargado contiene todos (hasta 1000).
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <?php endif; ?>

</div>
