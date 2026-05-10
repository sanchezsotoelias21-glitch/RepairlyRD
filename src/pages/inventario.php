<?php

declare(strict_types=1);

/** @var mysqli $conn */

$t = $_GET['t'] ?? '';
$m = $_GET['m'] ?? '';
$action = $_GET['action'] ?? '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

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

$rows = db_rows($conn, "SELECT * FROM `{$pieza_table}` ORDER BY `{$idField}` DESC LIMIT 300");
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
        <?php if ($t === 'ok' && is_string($m) && $m !== ''): ?>
            <div style="margin-top:8px;font-size:12px;color:#1B5E20;"><?= h($m) ?></div>
        <?php endif; ?>

        <?php if ($action === 'new' || $action === 'edit'): ?>
            <form method="post" style="margin-top:14px;display:grid;grid-template-columns:1fr 1fr;gap:10px;max-width:640px;">
                <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>">
                <input type="hidden" name="piezas_action" value="<?= $action === 'edit' ? 'update' : 'create' ?>">
                <?php if ($action === 'edit'): ?>
                    <input type="hidden" name="id_pieza" value="<?= (int)($edit[$idField] ?? 0) ?>">
                <?php endif; ?>
                <?php if (isset($pieza_cols['nombre'])): ?>
                    <div style="grid-column:1/-1;"><label style="font-size:10px;color:#6B6560;">Nombre *</label>
                        <input name="nombre" required style="width:100%;padding:10px;border-radius:8px;border:0.5px solid #D0CCC6;" value="<?= h((string)($edit['nombre'] ?? '')) ?>"></div>
                <?php endif; ?>
                <?php if (isset($pieza_cols['referencia'])): ?>
                    <div><label style="font-size:10px;color:#6B6560;">Referencia</label>
                        <input name="referencia" style="width:100%;padding:10px;border-radius:8px;border:0.5px solid #D0CCC6;" value="<?= h((string)($edit['referencia'] ?? '')) ?>"></div>
                <?php endif; ?>
                <?php if (isset($pieza_cols['precio_compra'])): ?>
                    <div><label style="font-size:10px;color:#6B6560;">Precio compra</label>
                        <input name="precio_compra" type="number" step="0.01" style="width:100%;padding:10px;border-radius:8px;border:0.5px solid #D0CCC6;" value="<?= h((string)($edit['precio_compra'] ?? '0')) ?>"></div>
                <?php endif; ?>
                <?php if (isset($pieza_cols['precio_venta'])): ?>
                    <div><label style="font-size:10px;color:#6B6560;">Precio venta</label>
                        <input name="precio_venta" type="number" step="0.01" style="width:100%;padding:10px;border-radius:8px;border:0.5px solid #D0CCC6;" value="<?= h((string)($edit['precio_venta'] ?? '0')) ?>"></div>
                <?php endif; ?>
                <?php if (isset($pieza_cols['stock'])): ?>
                    <div><label style="font-size:10px;color:#6B6560;">Stock</label>
                        <input name="stock" type="number" style="width:100%;padding:10px;border-radius:8px;border:0.5px solid #D0CCC6;" value="<?= h((string)($edit['stock'] ?? '0')) ?>"></div>
                <?php endif; ?>
                <div style="grid-column:1/-1;display:flex;gap:8px;justify-content:flex-end;">
                    <a class="ordenes-ver-btn" href="?page=inventario">Cancelar</a>
                    <button type="submit" class="ordenes-ver-btn" style="background:#1F5C8B;color:#fff;border-color:#1F5C8B;">Guardar</button>
                </div>
            </form>
        <?php endif; ?>

        <div style="margin-top:14px;border-top:0.5px solid #EDECEA;padding-top:12px;">
            <div class="table-head" style="grid-template-columns:48px 1fr 100px 80px 80px 120px;">
                <div>ID</div><div>Nombre</div><div>Ref.</div><div>Stock</div><div>P. venta</div><div style="text-align:right;">Acciones</div>
            </div>
            <?php foreach ($rows as $r): ?>
                <div class="table-row" style="grid-template-columns:48px 1fr 100px 80px 80px 120px;">
                    <div class="order-id"><?= (int)($r[$idField] ?? 0) ?></div>
                    <div class="order-cliente"><?= h((string)($r['nombre'] ?? '')) ?></div>
                    <div class="order-tecnico"><?= h((string)($r['referencia'] ?? '')) ?></div>
                    <div><?= (int)($r['stock'] ?? 0) ?></div>
                    <div>$<?= number_format((float)($r['precio_venta'] ?? 0), 2) ?></div>
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
