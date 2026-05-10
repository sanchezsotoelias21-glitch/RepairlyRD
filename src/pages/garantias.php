<?php

declare(strict_types=1);

/** @var mysqli $conn */

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

$orden_tbl = pick_table($conn, ['orden_reparacion', 'Orden_Reparacion', 'orden']);
$orden_cols_g = $orden_tbl !== '' ? table_columns($conn, $orden_tbl) : [];
$orden_id_col = 'id_orden';
foreach (array_keys($orden_cols_g) as $ok) {
    if (strcasecmp((string)$ok, 'id_orden') === 0) {
        $orden_id_col = $ok;
        break;
    }
}
$ordenes_opts = [];
if ($orden_tbl !== '') {
    if (isset($orden_cols_g['codigo_seguimiento'])) {
        $ordenes_opts = db_rows($conn, "SELECT `{$orden_id_col}` AS id_orden, COALESCE(`codigo_seguimiento`, CAST(`{$orden_id_col}` AS CHAR)) AS lbl FROM `{$orden_tbl}` ORDER BY `{$orden_id_col}` DESC LIMIT 300");
    } else {
        $ordenes_opts = db_rows($conn, "SELECT `{$orden_id_col}` AS id_orden, CAST(`{$orden_id_col}` AS CHAR) AS lbl FROM `{$orden_tbl}` ORDER BY `{$orden_id_col}` DESC LIMIT 300");
    }
}

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

$eid_ord = $gar_col_orden !== null && $edit ? (int)($edit[$gar_col_orden] ?? 0) : 0;
$efi = $gar_col_fi !== null && $edit ? substr((string)($edit[$gar_col_fi] ?? ''), 0, 10) : '';
$eff = $gar_col_ff !== null && $edit ? substr((string)($edit[$gar_col_ff] ?? ''), 0, 10) : '';
$ecob = $gar_col_cob !== null && $edit ? (string)($edit[$gar_col_cob] ?? '0') : '0';
$etipo = $gar_col_tipo !== null && $edit ? (string)($edit[$gar_col_tipo] ?? '') : '';
$eest = $gar_col_est !== null && $edit ? (string)($edit[$gar_col_est] ?? 'activa') : 'activa';
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

        <?php if ($action === 'new' || $action === 'edit'): ?>
            <?php if ($gar_col_orden === null): ?>
                <p style="padding:12px;color:#B83232;font-size:12px;">La tabla de garantías no tiene columna de orden (id_orden).</p>
            <?php else: ?>
            <form method="post" style="margin-top:14px;display:grid;grid-template-columns:1fr 1fr;gap:10px;max-width:720px;">
                <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>">
                <input type="hidden" name="gar_action" value="<?= $action === 'edit' ? 'update' : 'create' ?>">
                <?php if ($action === 'edit'): ?>
                    <input type="hidden" name="id_garantia" value="<?= (int)($edit[$idField] ?? 0) ?>">
                <?php endif; ?>
                <div style="grid-column:1/-1;">
                    <label style="font-size:10px;color:#6B6560;">Orden *</label>
                    <select name="id_orden" required style="width:100%;padding:10px;border-radius:8px;border:0.5px solid #D0CCC6;">
                        <?php foreach ($ordenes_opts as $o): ?>
                            <option value="<?= (int)$o['id_orden'] ?>" <?= $eid_ord === (int)$o['id_orden'] ? 'selected' : '' ?>><?= h((string)($o['lbl'] ?? $o['id_orden'])) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php if ($gar_col_fi !== null): ?>
                    <div><label style="font-size:10px;color:#6B6560;">Inicio</label>
                        <input name="fecha_inicio" type="date" style="width:100%;padding:10px;border-radius:8px;border:0.5px solid #D0CCC6;" value="<?= h($action === 'edit' ? $efi : '') ?>"></div>
                <?php endif; ?>
                <?php if ($gar_col_ff !== null): ?>
                    <div><label style="font-size:10px;color:#6B6560;">Fin</label>
                        <input name="fecha_fin" type="date" style="width:100%;padding:10px;border-radius:8px;border:0.5px solid #D0CCC6;" value="<?= h($action === 'edit' ? $eff : '') ?>"></div>
                <?php endif; ?>
                <?php if ($gar_col_cob !== null): ?>
                    <div><label style="font-size:10px;color:#6B6560;">Cobertura (días)</label>
                        <input name="cobertura_dias" type="number" style="width:100%;padding:10px;border-radius:8px;border:0.5px solid #D0CCC6;" value="<?= h($action === 'edit' ? $ecob : '0') ?>"></div>
                <?php endif; ?>
                <?php if ($gar_col_tipo !== null): ?>
                    <div><label style="font-size:10px;color:#6B6560;">Tipo</label>
                        <input name="tipo" style="width:100%;padding:10px;border-radius:8px;border:0.5px solid #D0CCC6;" value="<?= h($action === 'edit' ? $etipo : '') ?>"></div>
                <?php endif; ?>
                <?php if ($gar_col_est !== null): ?>
                    <div><label style="font-size:10px;color:#6B6560;">Estado</label>
                        <input name="estado" style="width:100%;padding:10px;border-radius:8px;border:0.5px solid #D0CCC6;" value="<?= h($action === 'edit' ? $eest : 'activa') ?>"></div>
                <?php endif; ?>
                <p style="grid-column:1/-1;font-size:10px;color:#6B6560;margin:0;">Si dejas “Fin” vacío pero indicas días de cobertura, se calculará la fecha de fin a partir del inicio.</p>
                <div style="grid-column:1/-1;display:flex;gap:8px;justify-content:flex-end;">
                    <a class="ordenes-ver-btn" href="?page=garantias">Cancelar</a>
                    <button type="submit" class="ordenes-ver-btn" style="background:#1F5C8B;color:#fff;border-color:#1F5C8B;">Guardar</button>
                </div>
            </form>
            <?php endif; ?>
        <?php endif; ?>

        <div style="margin-top:14px;border-top:0.5px solid #EDECEA;padding-top:12px;">
            <div class="table-head" style="grid-template-columns:52px 72px 1fr 100px 120px;">
                <div>ID</div><div>Orden</div><div>Tipo / Estado</div><div>Vencimiento</div><div style="text-align:right;">Acciones</div>
            </div>
            <?php foreach ($rows as $r): ?>
                <?php
                $roid = $gar_col_orden !== null ? (int)($r[$gar_col_orden] ?? 0) : 0;
                $rtipo = $gar_col_tipo !== null ? (string)($r[$gar_col_tipo] ?? '') : '';
                $rest = $gar_col_est !== null ? (string)($r[$gar_col_est] ?? '') : '';
                $rff = $gar_col_ff !== null ? (string)($r[$gar_col_ff] ?? '') : '';
                ?>
                <div class="table-row" style="grid-template-columns:52px 72px 1fr 100px 120px;">
                    <div class="order-id"><?= (int)($r[$idField] ?? 0) ?></div>
                    <div><?= $roid ?></div>
                    <div class="order-tecnico"><?= h($rtipo) ?> · <?= h($rest) ?></div>
                    <div style="font-size:11px;"><?= h(substr($rff, 0, 10)) ?></div>
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
