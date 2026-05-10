<?php

declare(strict_types=1);

/** @var mysqli $conn */

$action = $_GET['action'] ?? '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$search_q = isset($_GET['q']) && is_string($_GET['q']) ? trim($_GET['q']) : '';

if (empty($pieza_table)) {
    echo '<div class="charts-card">Tabla Pieza no encontrada.</div>';
    return;
}

$idField = 'id_pieza';
foreach (array_keys($pieza_cols) as $k) {
    if (strcasecmp((string)$k, 'id_pieza') === 0) {
        $idField = $k;
        break;
    }
}

$rows = [];
$sql = "SELECT * FROM `{$pieza_table}`";
$types = '';
$params = [];
if ($search_q !== '') {
    $like = '%' . $search_q . '%';
    $ors = [];
    $ors[] = "CAST(`{$idField}` AS CHAR) = ?";
    $types .= 's';
    $params[] = $search_q;
    if ($pz_col_nombre !== null) {
        $ors[] = "`{$pz_col_nombre}` LIKE ?";
        $types .= 's';
        $params[] = $like;
    }
    if ($pz_col_ref !== null) {
        $ors[] = "`{$pz_col_ref}` LIKE ?";
        $types .= 's';
        $params[] = $like;
    }
    if ($ors !== []) {
        $sql .= ' WHERE ' . implode(' OR ', $ors);
    }
}
$sql .= " ORDER BY `{$idField}` DESC LIMIT 300";
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
$edit = null;
if ($action === 'edit' && $id > 0) {
    $st = $conn->prepare("SELECT * FROM `{$pieza_table}` WHERE `{$idField}`=? LIMIT 1");
    if ($st) {
        $st->bind_param('i', $id);
        $st->execute();
        $rs = $st->get_result();
        $edit = $rs ? $rs->fetch_assoc() : null;
        $st->close();
    }
}

$en = $pz_col_nombre !== null && $edit ? (string)($edit[$pz_col_nombre] ?? '') : '';
$er = $pz_col_ref !== null && $edit ? (string)($edit[$pz_col_ref] ?? '') : '';
$epc = $pz_col_pc !== null && $edit ? (string)($edit[$pz_col_pc] ?? '0') : '0';
$epv = $pz_col_pv !== null && $edit ? (string)($edit[$pz_col_pv] ?? '0') : '0';
$es = $pz_col_stock !== null && $edit ? (string)($edit[$pz_col_stock] ?? '0') : '0';
?>
<div class="charts-row">
    <div class="charts-card">
        <div class="card-header">
            <div>
                <div class="card-title">Inventario / Piezas</div>
                <div class="card-sub">Stock y precios</div>
            </div>
            <a class="ordenes-ver-btn" href="?page=inventario&action=new">Nueva pieza</a>
        </div>
        <div style="display:flex;gap:8px;align-items:center;margin-top:10px;flex-wrap:wrap;">
            <form method="get" style="display:flex;gap:8px;align-items:center;flex:1;min-width:240px;">
                <input type="hidden" name="page" value="inventario">
                <div class="topbar-search" style="flex:1;min-width:220px;">
                    <i class="ti ti-search" aria-hidden="true"></i>
                    <input name="q" value="<?= h($search_q) ?>" placeholder="Buscar por nombre, referencia o ID" style="border:0;background:transparent;outline:none;font:inherit;color:#4D4841;width:100%;">
                </div>
                <button class="ordenes-ver-btn" type="submit">Buscar</button>
                <?php if ($search_q !== ''): ?>
                    <a class="ordenes-ver-btn" href="?page=inventario">Limpiar</a>
                <?php endif; ?>
            </form>
        </div>

        <?php if ($action === 'new' || $action === 'edit'): ?>
            <?php if ($pz_col_nombre === null): ?>
                <p style="padding:12px;color:#B83232;font-size:12px;">
                    No se encontró una columna de nombre reconocida en la tabla de inventario.
                    <?php if (!empty($pieza_cols)): ?>
                        <br><span style="color:#4D4841;font-size:11px;">Columnas en <code><?= h((string)$pieza_table) ?></code>:
                        <?= h(implode(', ', array_keys($pieza_cols))) ?></span>
                    <?php endif; ?>
                    <br><span style="font-size:11px;color:#6B6560;">Si el nombre de la pieza está en otra columna, indícanos el nombre exacto para añadirlo al sistema.</span>
                </p>
            <?php else: ?>
            <form method="post" style="margin-top:14px;display:grid;grid-template-columns:1fr 1fr;gap:10px;max-width:640px;">
                <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>">
                <input type="hidden" name="piezas_action" value="<?= $action === 'edit' ? 'update' : 'create' ?>">
                <?php if ($action === 'edit'): ?>
                    <input type="hidden" name="id_pieza" value="<?= (int)($edit[$idField] ?? 0) ?>">
                <?php endif; ?>
                <div style="grid-column:1/-1;"><label style="font-size:10px;color:#6B6560;">Nombre *</label>
                    <input name="nombre" required style="width:100%;padding:10px;border-radius:8px;border:0.5px solid #D0CCC6;" value="<?= h($action === 'edit' ? $en : '') ?>"></div>
                <?php if ($pz_col_ref !== null): ?>
                    <div><label style="font-size:10px;color:#6B6560;">Referencia</label>
                        <input name="referencia" style="width:100%;padding:10px;border-radius:8px;border:0.5px solid #D0CCC6;" value="<?= h($action === 'edit' ? $er : '') ?>"></div>
                <?php endif; ?>
                <?php if ($pz_col_pc !== null): ?>
                    <div><label style="font-size:10px;color:#6B6560;">Precio compra</label>
                        <input name="precio_compra" type="number" step="0.01" style="width:100%;padding:10px;border-radius:8px;border:0.5px solid #D0CCC6;" value="<?= h($action === 'edit' ? $epc : '0') ?>"></div>
                <?php endif; ?>
                <?php if ($pz_col_pv !== null): ?>
                    <div><label style="font-size:10px;color:#6B6560;">Precio venta</label>
                        <input name="precio_venta" type="number" step="0.01" style="width:100%;padding:10px;border-radius:8px;border:0.5px solid #D0CCC6;" value="<?= h($action === 'edit' ? $epv : '0') ?>"></div>
                <?php endif; ?>
                <?php if ($pz_col_stock !== null): ?>
                    <div><label style="font-size:10px;color:#6B6560;">Stock</label>
                        <input name="stock" type="number" style="width:100%;padding:10px;border-radius:8px;border:0.5px solid #D0CCC6;" value="<?= h($action === 'edit' ? $es : '0') ?>"></div>
                <?php endif; ?>
                <div style="grid-column:1/-1;display:flex;gap:8px;justify-content:flex-end;">
                    <a class="ordenes-ver-btn" href="?page=inventario">Cancelar</a>
                    <button type="submit" class="ordenes-ver-btn" style="background:#1F5C8B;color:#fff;border-color:#1F5C8B;">Guardar</button>
                </div>
            </form>
            <?php endif; ?>
        <?php endif; ?>

        <div style="margin-top:14px;border-top:0.5px solid #EDECEA;padding-top:12px;">
            <div class="table-head" style="grid-template-columns:48px 1fr 100px 80px 80px 120px;">
                <div>ID</div><div>Nombre</div><div>Ref.</div><div>Stock</div><div>P. venta</div><div style="text-align:right;">Acciones</div>
            </div>
            <?php foreach ($rows as $r): ?>
                <?php
                $rn = $pz_col_nombre !== null ? (string)($r[$pz_col_nombre] ?? '') : '';
                $rr = $pz_col_ref !== null ? (string)($r[$pz_col_ref] ?? '') : '';
                $rsv = $pz_col_stock !== null ? (int)($r[$pz_col_stock] ?? 0) : 0;
                $rpv = $pz_col_pv !== null ? (float)($r[$pz_col_pv] ?? 0) : 0.0;
                ?>
                <div class="table-row" style="grid-template-columns:48px 1fr 100px 80px 80px 120px;">
                    <div class="order-id"><?= (int)($r[$idField] ?? 0) ?></div>
                    <div class="order-cliente"><?= h($rn) ?></div>
                    <div class="order-tecnico"><?= h($rr) ?></div>
                    <div><?= $rsv ?></div>
                    <div>$<?= number_format($rpv, 2) ?></div>
                    <div style="display:flex;gap:6px;justify-content:flex-end;">
                        <a class="ordenes-ver-btn" href="?page=inventario&action=edit&id=<?= (int)($r[$idField] ?? 0) ?>">Editar</a>
                        <form method="post" style="display:inline;" onsubmit="return confirm('¿Eliminar pieza?');">
                            <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>">
                            <input type="hidden" name="piezas_action" value="delete">
                            <input type="hidden" name="id_pieza" value="<?= (int)($r[$idField] ?? 0) ?>">
                            <button type="submit" class="ordenes-ver-btn" style="background:#FDF0F0;color:#B83232;">Eliminar</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
