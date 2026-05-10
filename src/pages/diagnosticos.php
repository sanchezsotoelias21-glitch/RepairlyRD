<?php

declare(strict_types=1);

/** @var mysqli $conn */

$t = $_GET['t'] ?? '';
$m = $_GET['m'] ?? '';
$action = $_GET['action'] ?? '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (empty($diag_table)) {
    echo '<div class="charts-card">Tabla Diagnostico no encontrada.</div>';
    return;
}

$idField = 'id_diagnostico';
foreach (array_keys($diag_cols) as $k) {
    if (strcasecmp((string)$k, 'id_diagnostico') === 0) {
        $idField = $k;
        break;
    }
}

$orden_tbl = pick_table($conn, ['orden_reparacion', 'Orden_Reparacion', 'orden']);
$orden_cols_for_join = $orden_tbl !== '' ? table_columns($conn, $orden_tbl) : [];
$eq_tbl_diag = pick_table($conn, ['equipo', 'Equipo']);
$orden_id_col = 'id_orden';
foreach (array_keys($orden_cols_for_join) as $ok) {
    if (strcasecmp((string)$ok, 'id_orden') === 0) {
        $orden_id_col = $ok;
        break;
    }
}

$ordenes_opts = [];
if ($orden_tbl !== '') {
    $sel = ["o.`{$orden_id_col}` AS id_orden"];
    if (isset($orden_cols_for_join['codigo_seguimiento'])) {
        $sel[] = "COALESCE(o.`codigo_seguimiento`, CAST(o.`{$orden_id_col}` AS CHAR)) AS lbl";
    } else {
        $sel[] = "CAST(o.`{$orden_id_col}` AS CHAR) AS lbl";
    }
    $tipo_expr = "''";
    if ($eq_tbl_diag !== '' && isset($orden_cols_for_join['id_equipo'])) {
        $tipo_expr = "TRIM(CONCAT(COALESCE(e.`tipo`,''),' ',COALESCE(e.`marca`,''),' ',COALESCE(e.`modelo`,'')))";
    }
    $sel[] = "{$tipo_expr} AS orden_tipo";
    $precio_parts = [];
    if (isset($orden_cols_for_join['costo_total'])) {
        $precio_parts[] = 'o.`costo_total`';
    }
    if (isset($orden_cols_for_join['mano_obra'])) {
        $precio_parts[] = 'o.`mano_obra`';
    }
    $sel[] = $precio_parts !== []
        ? ('COALESCE(' . implode(',', $precio_parts) . ',0) AS orden_precio')
        : '0 AS orden_precio';
    if (isset($orden_cols_for_join['fecha_ingreso'])) {
        $sel[] = 'COALESCE(DATE(o.`fecha_ingreso`), CURDATE()) AS orden_fecha';
    } elseif (isset($orden_cols_for_join['fecha_creacion'])) {
        $sel[] = 'COALESCE(DATE(o.`fecha_creacion`), CURDATE()) AS orden_fecha';
    } else {
        $sel[] = 'CURDATE() AS orden_fecha';
    }
    $cod_ref = isset($orden_cols_for_join['codigo_seguimiento'])
        ? "CONCAT('Orden ', COALESCE(o.`codigo_seguimiento`, CAST(o.`{$orden_id_col}` AS CHAR)))"
        : "CONCAT('Orden #', o.`{$orden_id_col}`)";
    if ($eq_tbl_diag !== '' && isset($orden_cols_for_join['id_equipo'])) {
        $sel[] = "TRIM(CONCAT_WS(' · ', {$cod_ref}, NULLIF({$tipo_expr}, ''))) AS orden_descripcion";
    } else {
        $sel[] = "{$cod_ref} AS orden_descripcion";
    }
    $from = "FROM `{$orden_tbl}` o";
    $join = ($eq_tbl_diag !== '' && isset($orden_cols_for_join['id_equipo']))
        ? " LEFT JOIN `{$eq_tbl_diag}` e ON e.`id_equipo` = o.`id_equipo`"
        : '';
    $sql_ord = 'SELECT ' . implode(', ', $sel) . " {$from}{$join} ORDER BY o.`{$orden_id_col}` DESC LIMIT 300";
    $ordenes_opts = db_rows($conn, $sql_ord);
}

$rows = db_rows($conn, "SELECT * FROM `{$diag_table}` ORDER BY `{$idField}` DESC LIMIT 200");
$edit = null;
if ($action === 'edit' && $id > 0) {
    $st = $conn->prepare("SELECT * FROM `{$diag_table}` WHERE `{$idField}`=? LIMIT 1");
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
                <div class="card-title">Diagnósticos</div>
                <div class="card-sub">Por orden de reparación</div>
            </div>
            <a class="ordenes-ver-btn" href="?page=diagnosticos&action=new">Nuevo diagnóstico</a>
        </div>
        <?php if ($t === 'ok' && is_string($m) && $m !== ''): ?>
            <div style="margin-top:8px;font-size:12px;color:#1B5E20;"><?= h($m) ?></div>
        <?php endif; ?>

        <?php if ($action === 'new' || $action === 'edit'): ?>
            <form method="post" style="margin-top:14px;display:grid;grid-template-columns:1fr 1fr;gap:10px;max-width:720px;">
                <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>">
                <input type="hidden" name="diag_action" value="<?= $action === 'edit' ? 'update' : 'create' ?>">
                <?php if ($action === 'edit'): ?>
                    <input type="hidden" name="id_diagnostico" value="<?= (int)($edit[$idField] ?? 0) ?>">
                <?php endif; ?>
                <?php if (isset($diag_cols['id_orden'])): ?>
                    <div style="grid-column:1/-1;">
                        <label style="font-size:10px;color:#6B6560;">Orden</label>
                        <select id="diag-id-orden" name="id_orden" required style="width:100%;padding:10px;border-radius:8px;border:0.5px solid #D0CCC6;">
                            <?php foreach ($ordenes_opts as $o): ?>
                                <?php
                                $ot = (string)($o['orden_tipo'] ?? '');
                                $op = isset($o['orden_precio']) ? (float)$o['orden_precio'] : 0.0;
                                $of = (string)($o['orden_fecha'] ?? '');
                                if ($of !== '' && strlen($of) > 10) {
                                    $of = substr($of, 0, 10);
                                }
                                $od = (string)($o['orden_descripcion'] ?? '');
                                ?>
                                <option
                                    value="<?= (int)$o['id_orden'] ?>"
                                    data-orden-tipo="<?= h($ot) ?>"
                                    data-orden-precio="<?= h((string)$op) ?>"
                                    data-orden-fecha="<?= h($of) ?>"
                                    data-orden-desc="<?= h($od) ?>"
                                    <?= (int)($edit['id_orden'] ?? 0) === (int)$o['id_orden'] ? 'selected' : '' ?>
                                ><?= h((string)($o['lbl'] ?? $o['id_orden'])) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p style="font-size:10px;color:#6B6560;margin-top:6px;">Al elegir una orden se rellenan tipo, costo estimado, fecha y descripción con los datos de la orden y el equipo vinculado (puedes editarlos antes de guardar).</p>
                    </div>
                <?php endif; ?>
                <?php if (isset($diag_cols['tipo'])): ?>
                    <div><label style="font-size:10px;color:#6B6560;">Tipo</label>
                        <input name="tipo" style="width:100%;padding:10px;border-radius:8px;border:0.5px solid #D0CCC6;" value="<?= h((string)($edit['tipo'] ?? '')) ?>"></div>
                <?php endif; ?>
                <?php if (isset($diag_cols['costo_estimado'])): ?>
                    <div><label style="font-size:10px;color:#6B6560;">Costo estimado</label>
                        <input name="costo_estimado" type="number" step="0.01" style="width:100%;padding:10px;border-radius:8px;border:0.5px solid #D0CCC6;" value="<?= h((string)($edit['costo_estimado'] ?? '0')) ?>"></div>
                <?php endif; ?>
                <?php if (isset($diag_cols['fecha'])): ?>
                    <div><label style="font-size:10px;color:#6B6560;">Fecha</label>
                        <input name="fecha" type="date" style="width:100%;padding:10px;border-radius:8px;border:0.5px solid #D0CCC6;" value="<?= h(substr((string)($edit['fecha'] ?? date('Y-m-d')), 0, 10)) ?>"></div>
                <?php endif; ?>
                <?php if (isset($diag_cols['descripcion'])): ?>
                    <div style="grid-column:1/-1;"><label style="font-size:10px;color:#6B6560;">Descripción</label>
                        <textarea name="descripcion" rows="3" style="width:100%;padding:10px;border-radius:8px;border:0.5px solid #D0CCC6;"><?= h((string)($edit['descripcion'] ?? '')) ?></textarea></div>
                <?php endif; ?>
                <div style="grid-column:1/-1;display:flex;gap:8px;justify-content:flex-end;">
                    <a class="ordenes-ver-btn" href="?page=diagnosticos">Cancelar</a>
                    <button type="submit" class="ordenes-ver-btn" style="background:#1F5C8B;color:#fff;border-color:#1F5C8B;">Guardar</button>
                </div>
            </form>
        <?php endif; ?>

        <div style="margin-top:14px;border-top:0.5px solid #EDECEA;padding-top:12px;">
            <div class="table-head" style="grid-template-columns:52px 80px 1fr 100px 120px;">
                <div>ID</div><div>Orden</div><div>Tipo / Descripción</div><div>Fecha</div><div style="text-align:right;">Acciones</div>
            </div>
            <?php foreach ($rows as $r): ?>
                <div class="table-row" style="grid-template-columns:52px 80px 1fr 100px 120px;">
                    <div class="order-id"><?= (int)($r[$idField] ?? 0) ?></div>
                    <div><?= (int)($r['id_orden'] ?? 0) ?></div>
                    <div class="order-tecnico" style="font-size:11px;"><?= h((string)($r['tipo'] ?? '')) ?> — <?= h(mb_substr((string)($r['descripcion'] ?? ''), 0, 80)) ?></div>
                    <div style="font-size:11px;"><?= h(substr((string)($r['fecha'] ?? ''), 0, 10)) ?></div>
                    <div style="display:flex;gap:6px;justify-content:flex-end;">
                        <a class="ordenes-ver-btn" href="?page=diagnosticos&action=edit&id=<?= (int)($r[$idField] ?? 0) ?>">Editar</a>
                        <form method="post" style="display:inline;" onsubmit="return confirm('¿Eliminar?');">
                            <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>">
                            <input type="hidden" name="diag_action" value="delete">
                            <input type="hidden" name="id_diagnostico" value="<?= (int)($r[$idField] ?? 0) ?>">
                            <button type="submit" class="ordenes-ver-btn" style="background:#FDF0F0;color:#B83232;">Eliminar</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php if (($action === 'new' || $action === 'edit') && isset($diag_cols['id_orden'])): ?>
<script>
(function () {
    'use strict';
    var sel = document.getElementById('diag-id-orden');
    if (!sel) return;
    var ti = document.querySelector('input[name="tipo"]');
    var pr = document.querySelector('input[name="costo_estimado"]');
    var fe = document.querySelector('input[name="fecha"]');
    var de = document.querySelector('textarea[name="descripcion"]');
    function applyFromOrden() {
        var opt = sel.options[sel.selectedIndex];
        if (!opt) return;
        var t = opt.getAttribute('data-orden-tipo') || '';
        var p = opt.getAttribute('data-orden-precio') || '';
        var f = opt.getAttribute('data-orden-fecha') || '';
        var d = opt.getAttribute('data-orden-desc') || '';
        if (ti) ti.value = t;
        if (pr) pr.value = p;
        if (fe) fe.value = f.length > 10 ? f.slice(0, 10) : f;
        if (de) de.value = d;
    }
    sel.addEventListener('change', applyFromOrden);
    <?php if ($action === 'new'): ?>
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', applyFromOrden);
    } else {
        applyFromOrden();
    }
    <?php endif; ?>
})();
</script>
<?php endif; ?>
