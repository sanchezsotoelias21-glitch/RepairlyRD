<?php



/** @var mysqli $conn */
require_once __DIR__ . '/../../includes/ui_helper.php';

$action = $_GET['action'] ?? '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$ord_q = isset($_GET['ord_q']) && is_string($_GET['ord_q']) ? trim($_GET['ord_q']) : '';

if (empty($orden_table_name)) {
    echo '<div class="charts-card">No se encontró la tabla de órdenes.</div>';
    return;
}

$idField = 'id_orden';
foreach (array_keys($orden_cols) as $k) {
    if (strcasecmp((string)$k, 'id_orden') === 0) {
        $idField = $k;
        break;
    }
}

$estados_tbl = pick_table($conn, ['estado_servicio', 'estado', 'Estado_Servicio']);
$estados = [];
if ($estados_tbl !== '') {
    $estados = db_rows($conn, "SELECT * FROM `{$estados_tbl}` ORDER BY id_estado ASC LIMIT 200");
}
$eq_tbl = pick_table($conn, ['equipo', 'Equipo']);
$equipos_list = $eq_tbl !== '' ? db_rows($conn, "SELECT `id_equipo`, CONCAT(COALESCE(tipo,''),' ',COALESCE(marca,''),' ',COALESCE(modelo,'')) AS label FROM `{$eq_tbl}` ORDER BY id_equipo DESC LIMIT 300") : [];
$tec_tbl = pick_table($conn, ['tecnico', 'Tecnico']);
$tecs = $tec_tbl !== '' ? db_rows($conn, "SELECT `id_tecnico`, `nombre` FROM `{$tec_tbl}` ORDER BY nombre ASC LIMIT 200") : [];

$ordenes_list = [];
$where = '';
$types = '';
$par = [];
if ($ord_q !== '') {
    if (isset($orden_cols['codigo_seguimiento'])) {
        $where = " WHERE `codigo_seguimiento` LIKE ? OR CAST(`{$idField}` AS CHAR) = ?";
        $types = 'ss';
        $like = '%' . $ord_q . '%';
        $par = [$like, $ord_q];
    } else {
        $where = " WHERE CAST(`{$idField}` AS CHAR) = ?";
        $types = 's';
        $par = [$ord_q];
    }
}
$sql = "SELECT * FROM `{$orden_table_name}`{$where} ORDER BY `{$idField}` DESC LIMIT 200";
if ($types !== '') {
    $st = $conn->prepare($sql);
    if ($st) {
        $st->bind_param($types, ...$par);
        $st->execute();
        $res = $st->get_result();
        $ordenes_list = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
        $st->close();
    } else {
        $ordenes_list = [];
    }
} else {
    $ordenes_list = db_rows($conn, $sql);
}

$edit = null;
if ($action === 'edit' && $id > 0) {
    $st = $conn->prepare("SELECT * FROM `{$orden_table_name}` WHERE `{$idField}`=? LIMIT 1");
    if ($st) {
        $st->bind_param('i', $id);
        $st->execute();
        $rs = $st->get_result();
        $edit = $rs ? $rs->fetch_assoc() : null;
        $st->close();
    }
}

$estado_name = function (int $eid) use ($estados): string {
    foreach ($estados as $e) {
        if ((int)($e['id_estado'] ?? 0) === $eid) {
            return (string)($e['nombre_estado'] ?? $eid);
        }
    }
    return (string)$eid;
};
?>
<!-- Tarjetas de estadísticas - Estilo Dashboard -->
<div class="kpi-grid">
    <div class="kpi-card" style="background:#E3F2FD;--kpi-color:#2b7abc;">
        <div class="kpi-label" style="color:#2b7abc;">
            <i class="ti ti-checklist" aria-hidden="true"></i>
            Total órdenes
        </div>
        <div class="kpi-valor" style="color:#2b7abc;">
            <?= count($ordenes_list) ?>
        </div>
        <div class="kpi-sub">Órdenes en el sistema</div>
    </div>
    <div class="kpi-card" style="background:#FFF3E0;--kpi-color:#FF9500;">
        <div class="kpi-label" style="color:#FF9500;">
            <i class="ti ti-activity-heartbeat" aria-hidden="true"></i>
            En proceso
        </div>
        <div class="kpi-valor" style="color:#FF9500;">
            0
        </div>
        <div class="kpi-sub">Reparaciones activas</div>
    </div>
    <div class="kpi-card" style="background:#E8F5E9;--kpi-color:#00AA44;">
        <div class="kpi-label" style="color:#00AA44;">
            <i class="ti ti-check-circle" aria-hidden="true"></i>
            Completadas
        </div>
        <div class="kpi-valor" style="color:#00AA44;">
            0
        </div>
        <div class="kpi-sub">Órdenes finalizadas</div>
    </div>
</div>

<div class="charts-row" style="margin-top:18px;">
    <div class="charts-card">
        <div class="card-header">
            <div>
                <div class="card-title">Órdenes de reparación</div>
                <div class="card-sub">Código, equipo, técnico y estado</div>
            </div>
            <a class="ordenes-ver-btn" href="?page=ordenes&action=new">Nueva orden</a>
        </div>
        <form method="get" style="display:flex;gap:8px;align-items:center;margin-top:10px;flex-wrap:wrap;">
            <input type="hidden" name="page" value="ordenes">
            <div class="topbar-search" style="flex:1;min-width:200px;">
                <i class="ti ti-search"></i>
                <input name="ord_q" value="<?= h($ord_q) ?>" placeholder="Código o ID" style="border:0;background:transparent;outline:none;width:100%;">
            </div>
            <button class="ordenes-ver-btn" type="submit">Filtrar</button>
            <?php if ($ord_q !== ''): ?><a class="ordenes-ver-btn" href="?page=ordenes">Limpiar</a><?php endif; ?>
        </form>

        <?php if (empty($estados)): ?>
            <div style="margin-top:12px;padding:12px;border:0.5px solid #EDECEA;border-radius:10px;max-width:800px;">
                <div style="font-size:12px;color:#6B6560;">No hay estados disponibles para las órdenes. Crea los estados por defecto para poder guardar una orden.</div>
                <form method="post" style="margin-top:10px;display:flex;gap:8px;justify-content:flex-end;">
                    <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>">
                    <input type="hidden" name="orden_action" value="seed_estados">
                    <button type="submit" class="ordenes-ver-btn" style="background:#1F5C8B;color:#fff;border-color:#1F5C8B;">Crear estados</button>
                </form>
            </div>
        <?php endif; ?>

        <?php if ($action === 'new' || $action === 'edit'): ?>
            <form method="post" style="margin-top:14px;display:grid;grid-template-columns:1fr 1fr;gap:10px;max-width:800px;">
                <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>">
                <input type="hidden" name="orden_action" value="<?= $action === 'edit' ? 'update' : 'create' ?>">
                <?php if ($action === 'edit'): ?>
                    <input type="hidden" name="id_orden" value="<?= (int)($edit[$idField] ?? 0) ?>">
                <?php endif; ?>
                <?php if (isset($orden_cols['codigo_seguimiento'])): ?>
                    <div><label style="font-size:10px;color:#6B6560;">Código seguimiento</label>
                        <input name="codigo_seguimiento" style="width:100%;padding:10px;border-radius:8px;border:0.5px solid #D0CCC6;" value="<?= h((string)($edit['codigo_seguimiento'] ?? '')) ?>"></div>
                <?php endif; ?>
                <?php if (isset($orden_cols['id_equipo'])): ?>
                    <div style="grid-column:1/-1;">
                        <label style="font-size:10px;color:#6B6560;">Equipo</label>
                        <input type="text" name="id_equipo_text" list="equipo_list" required placeholder="Buscar o escribir equipo..." style="width:100%;padding:10px;border-radius:8px;border:0.5px solid #D0CCC6;" value="<?= (int)($edit['id_equipo'] ?? 0) > 0 ? h((string)($edit['equipo_label'] ?? '')) : '' ?>">
                        <input type="hidden" name="id_equipo" id="id_equipo_hidden" value="<?= (int)($edit['id_equipo'] ?? 0) ?>">
                        <datalist id="equipo_list">
                            <?php foreach ($equipos_list as $e): ?>
                                <option value="<?= h((string)($e['label'] ?? $e['id_equipo'])) ?>" data-id="<?= (int)$e['id_equipo'] ?>"></option>
                            <?php endforeach; ?>
                        </datalist>
                    </div>
                <?php endif; ?>
                <?php if (isset($orden_cols['id_tecnico'])): ?>
                    <div>
                        <label style="font-size:10px;color:#6B6560;">Técnico</label>
                        <input type="text" name="id_tecnico_text" list="tecnico_list" placeholder="Buscar o escribir técnico..." style="width:100%;padding:10px;border-radius:8px;border:0.5px solid #D0CCC6;" value="<?= (int)($edit['id_tecnico'] ?? 0) > 0 ? h((string)($edit['nombre_tecnico'] ?? '')) : '' ?>">
                        <input type="hidden" name="id_tecnico" id="id_tecnico_hidden" value="<?= (int)($edit['id_tecnico'] ?? 0) ?>">
                        <datalist id="tecnico_list">
                            <?php foreach ($tecs as $te): ?>
                                <option value="<?= h((string)$te['nombre']) ?>" data-id="<?= (int)$te['id_tecnico'] ?>"></option>
                            <?php endforeach; ?>
                        </datalist>
                    </div>
                <?php endif; ?>
                <?php if (isset($orden_cols['id_estado_actual'])): ?>
                    <div>
                        <label style="font-size:10px;color:#6B6560;">Estado</label>
                        <select name="id_estado_actual" required style="width:100%;padding:10px;border-radius:8px;border:0.5px solid #D0CCC6;">
                            <option value="0">—</option>
                            <?php foreach ($estados as $e): ?>
                                <option value="<?= (int)($e['id_estado'] ?? 0) ?>" <?= (int)($edit['id_estado_actual'] ?? 0) === (int)($e['id_estado'] ?? 0) ? 'selected' : '' ?>><?= h((string)($e['nombre_estado'] ?? '')) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>
                <?php if (isset($orden_cols['mano_obra'])): ?>
                    <div><label style="font-size:10px;color:#6B6560;">Mano de obra</label>
                        <input name="mano_obra" type="number" step="0.01" style="width:100%;padding:10px;border-radius:8px;border:0.5px solid #D0CCC6;" value="<?= h((string)($edit['mano_obra'] ?? '0')) ?>"></div>
                <?php endif; ?>
                <?php if (isset($orden_cols['costo_total'])): ?>
                    <div><label style="font-size:10px;color:#6B6560;">Costo total</label>
                        <input name="costo_total" type="number" step="0.01" style="width:100%;padding:10px;border-radius:8px;border:0.5px solid #D0CCC6;" value="<?= h((string)($edit['costo_total'] ?? '0')) ?>"></div>
                <?php endif; ?>
                <?php if (isset($orden_cols['fecha_ingreso'])): ?>
                    <div><label style="font-size:10px;color:#6B6560;">Fecha ingreso</label>
                        <input name="fecha_ingreso" type="date" style="width:100%;padding:10px;border-radius:8px;border:0.5px solid #D0CCC6;" value="<?= h(substr((string)($edit['fecha_ingreso'] ?? ''), 0, 10)) ?>"></div>
                <?php endif; ?>
                <?php if (isset($orden_cols['fecha_estimada_entrega'])): ?>
                    <div><label style="font-size:10px;color:#6B6560;">Fecha est. entrega</label>
                        <input name="fecha_estimada_entrega" type="date" style="width:100%;padding:10px;border-radius:8px;border:0.5px solid #D0CCC6;" value="<?= h(substr((string)($edit['fecha_estimada_entrega'] ?? ''), 0, 10)) ?>"></div>
                <?php endif; ?>
                <?php if (isset($orden_cols['fecha_entrega_real'])): ?>
                    <div><label style="font-size:10px;color:#6B6560;">Entrega real</label>
                        <input name="fecha_entrega_real" type="date" style="width:100%;padding:10px;border-radius:8px;border:0.5px solid #D0CCC6;" value="<?= h(substr((string)($edit['fecha_entrega_real'] ?? ''), 0, 10)) ?>"></div>
                <?php endif; ?>
                <div style="grid-column:1/-1;display:flex;gap:8px;justify-content:flex-end;">
                    <a class="ordenes-ver-btn" href="?page=ordenes">Cancelar</a>
                    <button type="submit" class="ordenes-ver-btn" style="background:#1F5C8B;color:#fff;border-color:#1F5C8B;">Guardar</button>
                </div>
            </form>
        <?php endif; ?>

        <div style="margin-top:14px;border-top:0.5px solid #EDECEA;padding-top:12px;">
            <div class="table-head" style="grid-template-columns:52px 1fr 90px 90px 120px;">
                <div>ID</div><div>Código</div><div>Estado</div><div>Total</div><div style="text-align:right;">Acciones</div>
            </div>
            <div style="max-height:400px;overflow-y:auto;">
            <?php foreach ($ordenes_list as $o): ?>
                <?php $eid = (int)($o['id_estado_actual'] ?? 0); ?>
                <div class="table-row" style="grid-template-columns:52px 1fr 90px 90px 120px;">
                    <div class="order-id"><?= (int)($o[$idField] ?? 0) ?></div>
                    <div class="order-cliente"><?= h((string)($o['codigo_seguimiento'] ?? '—')) ?></div>
                    <div class="order-tecnico" style="font-size:11px;"><?= h($estado_name($eid)) ?></div>
                    <div class="order-valor" style="text-align:left;">$<?= number_format((float)($o['costo_total'] ?? 0), 2) ?></div>
                    <div style="display:flex;gap:6px;justify-content:flex-end;">
                        <a class="ordenes-ver-btn" href="?page=ordenes&action=edit&id=<?= (int)($o[$idField] ?? 0) ?>">Editar</a>
                        <form method="post" style="display:inline;" onsubmit="return confirm('¿Eliminar orden?');">
                            <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>">
                            <input type="hidden" name="orden_action" value="delete">
                            <input type="hidden" name="id_orden" value="<?= (int)($o[$idField] ?? 0) ?>">
                            <button type="submit" class="ordenes-ver-btn" style="background:#FDF0F0;color:#B83232;">Eliminar</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<script>
// Script para actualizar los IDs cuando se seleccionan del datalist
document.addEventListener('DOMContentLoaded', function() {
    // Equipo
    const equipoInput = document.querySelector('input[name="id_equipo_text"]');
    const equipoHidden = document.getElementById('id_equipo_hidden');
    const equipoList = document.getElementById('equipo_list');

    if (equipoInput && equipoHidden && equipoList) {
        equipoInput.addEventListener('input', function() {
            const selectedOption = Array.from(equipoList.options).find(option => option.value === this.value);
            if (selectedOption) {
                equipoHidden.value = selectedOption.getAttribute('data-id');
            } else {
                equipoHidden.value = '';
            }
        });
    }

    // Técnico
    const tecnicoInput = document.querySelector('input[name="id_tecnico_text"]');
    const tecnicoHidden = document.getElementById('id_tecnico_hidden');
    const tecnicoList = document.getElementById('tecnico_list');

    if (tecnicoInput && tecnicoHidden && tecnicoList) {
        tecnicoInput.addEventListener('input', function() {
            const selectedOption = Array.from(tecnicoList.options).find(option => option.value === this.value);
            if (selectedOption) {
                tecnicoHidden.value = selectedOption.getAttribute('data-id');
            } else {
                tecnicoHidden.value = '';
            }
        });
    }
});
</script>
