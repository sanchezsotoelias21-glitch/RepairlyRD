<?php
// src/pages/reportes.php - Sistema integral de reportes profesionales

declare(strict_types=1);

// Tabla de metadatos de reportes disponibles
$report_types = [
    'clientes' => [
        'label' => 'Reporte de Clientes',
        'desc' => 'Listado de clientes con información de contacto',
        'icon' => 'ti-users',
        'table' => 'Cliente',
        'columns' => ['id_cliente', 'nombre', 'telefono', 'email', 'fecha_registro'],
        'display_cols' => ['ID', 'Nombre', 'Teléfono', 'Email', 'Fecha Registro'],
    ],
    'tecnicos' => [
        'label' => 'Reporte de Técnicos',
        'desc' => 'Listado de técnicos con especialidades',
        'icon' => 'ti-user-check',
        'table' => 'Tecnico',
        'columns' => ['id_tecnico', 'nombre', 'especialidad', 'telefono', 'estado'],
        'display_cols' => ['ID', 'Nombre', 'Especialidad', 'Teléfono', 'Estado'],
    ],
    'equipos' => [
        'label' => 'Reporte de Equipos',
        'desc' => 'Inventario de equipos ingresados',
        'icon' => 'ti-device-laptop',
        'table' => 'Equipo',
        'columns' => ['id_equipo', 'tipo', 'marca', 'modelo', 'cliente_id', 'estado'],
        'display_cols' => ['ID', 'Tipo', 'Marca', 'Modelo', 'Cliente', 'Estado'],
    ],
    'ordenes' => [
        'label' => 'Reporte de Órdenes de Reparación',
        'desc' => 'Órdenes completadas, pendientes y en proceso',
        'icon' => 'ti-clipboard-list',
        'table' => 'Reparacion',
        'columns' => ['id_reparacion', 'codigo', 'cliente_id', 'equipo_id', 'estado', 'costo', 'fecha_ingreso', 'fecha_salida'],
        'display_cols' => ['ID', 'Código', 'Cliente', 'Equipo', 'Estado', 'Costo', 'Ingreso', 'Salida'],
        'has_date_range' => true,
        'has_status_filter' => true,
    ],
    'piezas' => [
        'label' => 'Reporte de Piezas e Inventario',
        'desc' => 'Movimientos y stock de piezas',
        'icon' => 'ti-package',
        'table' => 'Pieza',
        'columns' => ['id_pieza', 'nombre', 'referencia', 'stock', 'precio_compra', 'precio_venta'],
        'display_cols' => ['ID', 'Nombre', 'Referencia', 'Stock', 'P. Compra', 'P. Venta'],
    ],
    'garantias' => [
        'label' => 'Reporte de Garantías',
        'desc' => 'Garantías activas y vencidas',
        'icon' => 'ti-shield-check',
        'table' => 'Garantia',
        'columns' => ['id_garantia', 'id_reparacion', 'tipo', 'estado', 'fecha_inicio', 'fecha_vencimiento'],
        'display_cols' => ['ID', 'Reparación', 'Tipo', 'Estado', 'Inicio', 'Vencimiento'],
    ],
];

// Obtener parámetros de la solicitud
$report_type = isset($_GET['type']) && is_string($_GET['type']) ? trim($_GET['type']) : '';
$action = isset($_GET['action']) && is_string($_GET['action']) ? trim($_GET['action']) : '';
$date_from = isset($_GET['date_from']) && is_string($_GET['date_from']) ? trim($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) && is_string($_GET['date_to']) ? trim($_GET['date_to']) : '';
$status_filter = isset($_GET['status']) && is_string($_GET['status']) ? trim($_GET['status']) : '';

// Validar tipo de reporte
$current_report = null;
if ($report_type && isset($report_types[$report_type])) {
    $current_report = $report_types[$report_type];
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
    $stats['ordenes'] = (int)$conn->query("SELECT COUNT(*) FROM Reparacion")->fetch_row()[0];

    $stats['completadas'] = (int)$conn->query(
        "SELECT COUNT(*) FROM Reparacion WHERE estado='Completado'"
    )->fetch_row()[0];

    $stats['pendientes'] = (int)$conn->query(
        "SELECT COUNT(*) FROM Reparacion WHERE estado='Pendiente'"
    )->fetch_row()[0];

    $stats['ingresos'] = (float)$conn->query(
        "SELECT COALESCE(SUM(costo),0) FROM Reparacion"
    )->fetch_row()[0];

} catch (Throwable $e) {
}


// Función auxiliar para obtener datos del reporte
function get_report_data($conn, $report_type_key, $report_config, $date_from, $date_to, $status_filter) {
    $table = $report_config['table'] ?? '';
    if (!$table) return [];

    // Construir query SQL
    $sql = "SELECT * FROM `{$table}` WHERE 1=1";
    $params = [];
    $types = '';

    // Filtros de fecha
    if ($date_from && $report_config['has_date_range'] ?? false) {
        // Buscar columnas de fecha
        $date_columns = ['fecha_ingreso', 'fecha_creacion', 'fecha_inicio', 'fecha_registro'];
        $date_col = null;
        foreach ($date_columns as $dc) {
            if (in_array($dc, $report_config['columns'] ?? [])) {
                $date_col = $dc;
                break;
            }
        }
        if ($date_col) {
            $sql .= " AND `{$date_col}` >= ?";
            $params[] = $date_from . ' 00:00:00';
            $types .= 's';
        }
    }

    if ($date_to && $report_config['has_date_range'] ?? false) {
        $date_columns = ['fecha_ingreso', 'fecha_creacion', 'fecha_inicio', 'fecha_registro'];
        $date_col = null;
        foreach ($date_columns as $dc) {
            if (in_array($dc, $report_config['columns'] ?? [])) {
                $date_col = $dc;
                break;
            }
        }
        if ($date_col) {
            $sql .= " AND `{$date_col}` <= ?";
            $params[] = $date_to . ' 23:59:59';
            $types .= 's';
        }
    }

    // Filtro de estado
    if ($status_filter && $report_config['has_status_filter'] ?? false) {
        if (in_array('estado', $report_config['columns'] ?? [])) {
            $sql .= " AND `estado` = ?";
            $params[] = $status_filter;
            $types .= 's';
        }
    }

    $sql .= " ORDER BY " . ($report_config['columns'][0] ?? 'id') . " DESC LIMIT 1000";

    $stmt = $conn->prepare($sql);
    if (!$stmt || !$params && $types === '') {
        return $conn->query($sql)->fetch_all(MYSQLI_ASSOC) ?? [];
    }

    if ($params) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $data ?? [];
}

// Procesar descarga de PDF
if ($action === 'generate_pdf' && $current_report) {
    $report_data = get_report_data($conn, $report_type, $current_report, $date_from, $date_to, $status_filter);
    
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
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo $pdf->output();
    exit;
}

// Obtener datos del reporte si está seleccionado
$report_data = [];
if ($current_report) {
    $report_data = get_report_data($conn, $report_type, $current_report, $date_from, $date_to, $status_filter);
}

// Estados posibles para filtrado
$status_options = [
    'Pendiente', 'En proceso', 'Listo', 'Entregado', 'Completado',
    'Cancelado', 'En espera', 'Diagnóstico', 'Activo', 'Inactivo'
];
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
                    <?php foreach ($status_options as $status): ?>
                    <option value="<?= h($status) ?>" <?= ($status_filter === $status ? 'selected' : '') ?>>
                        <?= h($status) ?>
                    </option>
                    <?php endforeach; ?>
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
