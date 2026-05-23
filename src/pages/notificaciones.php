<?php



/** @var mysqli $conn */
require_once __DIR__ . '/../../includes/ui_helper.php';

$nt = pick_table($conn, ['notificacion', 'Notificacion']);
$search_q = isset($_GET['q']) && is_string($_GET['q']) ? trim($_GET['q']) : '';
$rows = [];
$total_notif = 0;
$pending_notif = 0;

if ($nt !== '') {
    $nc = table_columns($conn, $nt);
    $ord = isset($nc['fecha_envio']) ? 'fecha_envio' : 'id_notificacion';
    $sql = "SELECT * FROM `{$nt}`";
    $types = '';
    $params = [];
    if ($search_q !== '') {
        $like = '%' . $search_q . '%';
        $ors = [];
        foreach (['tipo', 'mensaje', 'estado'] as $c) {
            if (isset($nc[$c])) {
                $ors[] = "`{$c}` LIKE ?";
                $types .= 's';
                $params[] = $like;
            }
        }
        if (isset($nc['id_orden'])) {
            if (ctype_digit($search_q)) {
                $ors[] = "`id_orden` = ?";
                $types .= 'i';
                $params[] = (int)$search_q;
            } else {
                $ors[] = "CAST(`id_orden` AS CHAR) LIKE ?";
                $types .= 's';
                $params[] = $like;
            }
        }
        if (isset($nc['id_notificacion'])) {
            $ors[] = "CAST(`id_notificacion` AS CHAR) = ?";
            $types .= 's';
            $params[] = $search_q;
        }
        if ($ors !== []) {
            $sql .= ' WHERE ' . implode(' OR ', $ors);
        }
    }
    $sql .= " ORDER BY `{$ord}` DESC LIMIT 200";
    if ($types !== '') {
        $st = $conn->prepare($sql);
        if ($st) {
            $st->bind_param($types, ...$params);
            $st->execute();
            $res = $st->get_result();
            $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
            $st->close();
        }
    } else {
        $rows = db_rows($conn, $sql);
    }
    
    // Contar totales
    $total_notif = count($rows);
    $pending_notif = 0;
    foreach ($rows as $r) {
        if (isset($r['estado']) && strtolower($r['estado']) === 'pendiente') {
            $pending_notif++;
        }
    }
}

// Tarjetas de estadísticas - Estilo Dashboard
?>
<div class="kpi-grid">
    <div class="kpi-card" style="background:#FFF3E0;--kpi-color:#FF9500;">
        <div class="kpi-label" style="color:#FF9500;">
            <i class="ti ti-bell" aria-hidden="true"></i>
            Total notificaciones
        </div>
        <div class="kpi-valor" style="color:#FF9500;">
            <?= $total_notif ?>
        </div>
        <div class="kpi-sub">Registros en el sistema</div>
    </div>
    <div class="kpi-card" style="background:#FFEBEE;--kpi-color:#FF4444;">
        <div class="kpi-label" style="color:#FF4444;">
            <i class="ti ti-alert-circle" aria-hidden="true"></i>
            Pendientes
        </div>
        <div class="kpi-valor" style="color:#FF4444;">
            <?= $pending_notif ?>
        </div>
        <div class="kpi-sub">Requieren atención</div>
    </div>
    <div class="kpi-card" style="background:#E8F5E9;--kpi-color:#00AA44;">
        <div class="kpi-label" style="color:#00AA44;">
            <i class="ti ti-activity-heartbeat" aria-hidden="true"></i>
            Estado sistema
        </div>
        <div class="kpi-valor" style="color:#00AA44;">
            Activo
        </div>
        <div class="kpi-sub">Monitoreando activamente</div>
    </div>
</div>

<!-- Listado de notificaciones -->
<div class="charts-row" style="margin-top:0;">
    <div class="charts-card">
        <div class="card-header">
            <div>
                <div class="card-title">Notificaciones</div>
                <div class="card-sub">Historial reciente del sistema</div>
            </div>
        </div>
        <div style="display:flex;gap:8px;align-items:center;margin-top:10px;flex-wrap:wrap;">
            <form method="get" style="display:flex;gap:8px;align-items:center;flex:1;min-width:240px;">
                <input type="hidden" name="page" value="notificaciones">
                <div class="topbar-search" style="flex:1;min-width:220px;">
                    <i class="ti ti-search" aria-hidden="true"></i>
                    <input name="q" value="<?= h($search_q) ?>" placeholder="Buscar por tipo, mensaje u orden" style="border:0;background:transparent;outline:none;font:inherit;color:#4D4841;width:100%;">
                </div>
                <button class="ordenes-ver-btn" type="submit">Buscar</button>
                <?php if ($search_q !== ''): ?>
                    <a class="ordenes-ver-btn" href="?page=notificaciones">Limpiar</a>
                <?php endif; ?>
            </form>
        </div>
        <?php if ($nt === ''): ?>
            <p style="padding:12px;color:#B83232;">Tabla Notificacion no encontrada.</p>
        <?php else: ?>
            <div style="margin-top:12px;max-height:400px;overflow-y:auto;">
                <?php foreach ($rows as $r): ?>
                    <div style="padding:16px;border-bottom:1px solid #EDECEA;font-size:13px;background:#fff;border-radius:8px;margin-bottom:8px;box-shadow:0 1px 3px rgba(0,0,0,0.05);">
                        <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:8px;">
                            <div style="font-weight:700;color:#1C1A17;font-size:14px;"><?= h((string)($r['tipo'] ?? 'aviso')) ?></div>
                            <div style="font-size:11px;padding:4px 8px;border-radius:12px;background:<?= strtolower($r['estado'] ?? '') === 'pendiente' ? '#FFF3E0' : '#E8F5E9' ?>;color:<?= strtolower($r['estado'] ?? '') === 'pendiente' ? '#FF9500' : '#00AA44' ?>;font-weight:600;"><?= h((string)($r['estado'] ?? 'N/A')) ?></div>
                        </div>
                        <div style="margin-top:8px;color:#4D4841;line-height:1.5;"><?= h((string)($r['mensaje'] ?? '')) ?></div>
                        <div style="margin-top:12px;padding-top:12px;border-top:1px solid #F0EFE9;font-size:11px;color:#8C8479;display:grid;grid-template-columns:repeat(auto-fit,minmax(120px,1fr));gap:8px;">
                            <div>
                                <span style="color:#6B6560;font-weight:500;">📅 Fecha:</span>
                                <span style="color:#4D4841;"><?= h((string)($r['fecha_envio'] ?? '')) ?></span>
                            </div>
                            <?php if (isset($r['id_orden'])): ?>
                            <div>
                                <span style="color:#6B6560;font-weight:500;">📋 Orden:</span>
                                <span style="color:#4D4841;">#<?= (int)$r['id_orden'] ?></span>
                            </div>
                            <?php endif; ?>
                            <?php if (isset($r['id_notificacion'])): ?>
                            <div>
                                <span style="color:#6B6560;font-weight:500;">🔔 ID:</span>
                                <span style="color:#4D4841;"><?= (int)$r['id_notificacion'] ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($rows)): ?>
                    <p style="padding:12px;color:#6B6560;">Sin notificaciones registradas.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
