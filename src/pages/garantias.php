<?php

declare(strict_types=1);

/** @var mysqli $conn */

$t = $_GET['t'] ?? '';
$m = $_GET['m'] ?? '';
$action = $_GET['action'] ?? '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (empty($garantia_table_name)) {
    echo '<div class="charts-card">Tabla Garantia no encontrada.</div>';
    return;
}

$idField = 'id_garantia';
foreach (array_keys($garantia_cols) as $k) {
    if (strcasecmp((string)$k, 'id_garantia') === 0) {
        $idField = $k;
        break;
    }
}

$orden_tbl = pick_table($conn, ['orden_reparacion', 'Orden_Reparacion']);
$ordenes_opts = $orden_tbl !== '' ? db_rows($conn, "SELECT `id_orden`, COALESCE(`codigo_seguimiento`, CAST(`id_orden` AS CHAR)) AS lbl FROM `{$orden_tbl}` ORDER BY `id_orden` DESC LIMIT 300") : [];

$rows = db_rows($conn, "SELECT * FROM `{$garantia_table_name}` ORDER BY `{$idField}` DESC LIMIT 200");
$edit = null;
if ($action === 'edit' && $id > 0) {
    $st = $conn->prepare("SELECT * FROM `{$garantia_table_name}` WHERE `{$idField}`=? LIMIT 1");
    if ($st) {
        $st->bind_param('i', $id);
        $st->execute();
        $rs = $st->get_result();
        $edit = $rs ? $rs->fetch_assoc() : null;
        $st->close();
    }
}
?>
<div class="charts-row">
    <div class="charts-card">
        <div class="card-header">
            <div>
                <div class="card-title">Garantías</div>
                <div class="card-sub">Vinculadas a órdenes</div>
            </div>
            <a class="ordenes-ver-btn" href="?page=garantias&action=new">Nueva garantía</a>
        </div>
        <?php if ($t === 'ok' && is_string($m) && $m !== ''): ?>
            <div style="margin-top:8px;font-size:12px;color:#1B5E20;"><?= h($m) ?></div>
        <?php endif; ?>

        <?php if ($action === 'new' || $action === 'edit'): ?>
            <form method="post" style="margin-top:14px;display:grid;grid-template-columns:1fr 1fr;gap:10px;max-width:720px;">
                <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>">
                <input type="hidden" name="gar_action" value="<?= $action === 'edit' ? 'update' : 'create' ?>">
                <?php if ($action === 'edit'): ?>
                    <input type="hidden" name="id_garantia" value="<?= (int)($edit[$idField] ?? 0) ?>">
                <?php endif; ?>
                <?php if (isset($garantia_cols['id_orden'])): ?>
                    <div style="grid-column:1/-1;">
                        <label style="font-size:10px;color:#6B6560;">Orden *</label>
                        <select name="id_orden" required style="width:100%;padding:10px;border-radius:8px;border:0.5px solid #D0CCC6;">
                            <?php foreach ($ordenes_opts as $o): ?>
                                <option value="<?= (int)$o['id_orden'] ?>" <?= (int)($edit['id_orden'] ?? 0) === (int)$o['id_orden'] ? 'selected' : '' ?>><?= h((string)($o['lbl'] ?? $o['id_orden'])) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>
                <?php if (isset($garantia_cols['fecha_inicio'])): ?>
                    <div><label style="font-size:10px;color:#6B6560;">Inicio</label>
                        <input name="fecha_inicio" type="date" style="width:100%;padding:10px;border-radius:8px;border:0.5px solid #D0CCC6;" value="<?= h(substr((string)($edit['fecha_inicio'] ?? ''), 0, 10)) ?>"></div>
                <?php endif; ?>
                <?php if (isset($garantia_cols['fecha_fin'])): ?>
                    <div><label style="font-size:10px;color:#6B6560;">Fin</label>
                        <input name="fecha_fin" type="date" style="width:100%;padding:10px;border-radius:8px;border:0.5px solid #D0CCC6;" value="<?= h(substr((string)($edit['fecha_fin'] ?? ''), 0, 10)) ?>"></div>
                <?php endif; ?>
                <?php if (isset($garantia_cols['cobertura_dias'])): ?>
                    <div><label style="font-size:10px;color:#6B6560;">Cobertura (días)</label>
                        <input name="cobertura_dias" type="number" style="width:100%;padding:10px;border-radius:8px;border:0.5px solid #D0CCC6;" value="<?= h((string)($edit['cobertura_dias'] ?? '0')) ?>"></div>
                <?php endif; ?>
                <?php if (isset($garantia_cols['tipo'])): ?>
                    <div><label style="font-size:10px;color:#6B6560;">Tipo</label>
                        <input name="tipo" style="width:100%;padding:10px;border-radius:8px;border:0.5px solid #D0CCC6;" value="<?= h((string)($edit['tipo'] ?? '')) ?>"></div>
                <?php endif; ?>
                <?php if (isset($garantia_cols['estado'])): ?>
                    <div><label style="font-size:10px;color:#6B6560;">Estado</label>
                        <input name="estado" style="width:100%;padding:10px;border-radius:8px;border:0.5px solid #D0CCC6;" value="<?= h((string)($edit['estado'] ?? 'activa')) ?>"></div>
                <?php endif; ?>
                <div style="grid-column:1/-1;display:flex;gap:8px;justify-content:flex-end;">
                    <a class="ordenes-ver-btn" href="?page=garantias">Cancelar</a>
                    <button type="submit" class="ordenes-ver-btn" style="background:#1F5C8B;color:#fff;border-color:#1F5C8B;">Guardar</button>
                </div>
            </form>
        <?php endif; ?>

        <div style="margin-top:14px;border-top:0.5px solid #EDECEA;padding-top:12px;">
            <div class="table-head" style="grid-template-columns:52px 72px 1fr 100px 120px;">
                <div>ID</div><div>Orden</div><div>Tipo / Estado</div><div>Vencimiento</div><div style="text-align:right;">Acciones</div>
            </div>
            <?php foreach ($rows as $r): ?>
                <div class="table-row" style="grid-template-columns:52px 72px 1fr 100px 120px;">
                    <div class="order-id"><?= (int)($r[$idField] ?? 0) ?></div>
                    <div><?= (int)($r['id_orden'] ?? 0) ?></div>
                    <div class="order-tecnico"><?= h((string)($r['tipo'] ?? '')) ?> · <?= h((string)($r['estado'] ?? '')) ?></div>
                    <div style="font-size:11px;"><?= h(substr((string)($r['fecha_fin'] ?? ''), 0, 10)) ?></div>
                    <div style="display:flex;gap:6px;justify-content:flex-end;">
                        <a class="ordenes-ver-btn" href="?page=garantias&action=edit&id=<?= (int)($r[$idField] ?? 0) ?>">Editar</a>
                        <form method="post" style="display:inline;" onsubmit="return confirm('¿Eliminar?');">
                            <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>">
                            <input type="hidden" name="gar_action" value="delete">
                            <input type="hidden" name="id_garantia" value="<?= (int)($r[$idField] ?? 0) ?>">
                            <button type="submit" class="ordenes-ver-btn" style="background:#FDF0F0;color:#B83232;">Eliminar</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
