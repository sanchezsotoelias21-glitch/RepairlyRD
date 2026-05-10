<?php

declare(strict_types=1);

/** @var mysqli $conn */

$t = $_GET['t'] ?? '';
$m = $_GET['m'] ?? '';
$action = $_GET['action'] ?? '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (empty($equipo_table)) {
    echo '<div class="charts-card"><div class="card-title">Equipos</div><p style="padding:12px;color:#B83232;">No se encontró la tabla Equipo.</p></div>';
    return;
}

$idField = 'id_equipo';
foreach (array_keys($equipo_cols) as $k) {
    if (strcasecmp((string)$k, 'id_equipo') === 0) {
        $idField = $k;
        break;
    }
}

$cliente_pick = pick_table($conn, ['cliente', 'Cliente']);
$clientes_opts = [];
if ($cliente_pick !== '') {
    $cc = table_columns($conn, $cliente_pick);
    $cid = 'id_cliente';
    foreach (array_keys($cc) as $k) {
        if (strcasecmp((string)$k, 'id_cliente') === 0) {
            $cid = $k;
            break;
        }
    }
    $clientes_opts = db_rows($conn, "SELECT `{$cid}` AS id, `nombre` FROM `{$cliente_pick}` ORDER BY `nombre` ASC LIMIT 500");
}

$rows = db_rows($conn, "SELECT * FROM `{$equipo_table}` ORDER BY `{$idField}` DESC LIMIT 150");

$edit = null;
if ($action === 'edit' && $id > 0) {
    $st = $conn->prepare("SELECT * FROM `{$equipo_table}` WHERE `{$idField}`=? LIMIT 1");
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
                <div class="card-title">Equipos</div>
                <div class="card-sub">Registro de equipos y vínculo con clientes</div>
            </div>
            <a class="ordenes-ver-btn" href="?page=equipos&action=new">Nuevo equipo</a>
        </div>
        <?php if ($t === 'ok' && is_string($m) && $m !== ''): ?>
            <div style="margin-top:10px;font-size:12px;color:#1B5E20;"><?= h($m) ?></div>
        <?php elseif ($t === 'err' && is_string($m) && $m !== ''): ?>
            <div style="margin-top:10px;font-size:12px;color:#B83232;"><?= h($m) ?></div>
        <?php endif; ?>

        <?php if ($action === 'new' || $action === 'edit'): ?>
            <form method="post" style="margin-top:14px;display:grid;grid-template-columns:1fr 1fr;gap:10px;max-width:720px;">
                <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>">
                <input type="hidden" name="equipos_action" value="<?= $action === 'edit' ? 'update' : 'create' ?>">
                <?php if ($action === 'edit'): ?>
                    <input type="hidden" name="id_equipo" value="<?= (int)($edit[$idField] ?? 0) ?>">
                <?php endif; ?>
                <?php if (isset($equipo_cols['tipo'])): ?>
                    <div><label style="font-size:10px;color:#6B6560;">Tipo</label><input name="tipo" style="width:100%;padding:10px;border-radius:8px;border:0.5px solid #D0CCC6;background:#fff;" value="<?= h((string)($edit['tipo'] ?? '')) ?>"></div>
                <?php endif; ?>
                <?php if (isset($equipo_cols['marca'])): ?>
                    <div><label style="font-size:10px;color:#6B6560;">Marca</label><input name="marca" style="width:100%;padding:10px;border-radius:8px;border:0.5px solid #D0CCC6;" value="<?= h((string)($edit['marca'] ?? '')) ?>"></div>
                <?php endif; ?>
                <?php if (isset($equipo_cols['modelo'])): ?>
                    <div><label style="font-size:10px;color:#6B6560;">Modelo</label><input name="modelo" style="width:100%;padding:10px;border-radius:8px;border:0.5px solid #D0CCC6;" value="<?= h((string)($edit['modelo'] ?? '')) ?>"></div>
                <?php endif; ?>
                <?php if (isset($equipo_cols['numero_identificacion'])): ?>
                    <div><label style="font-size:10px;color:#6B6560;">Nº identificación</label><input name="numero_identificacion" style="width:100%;padding:10px;border-radius:8px;border:0.5px solid #D0CCC6;" value="<?= h((string)($edit['numero_identificacion'] ?? '')) ?>"></div>
                <?php endif; ?>
                <?php if (isset($equipo_cols['tipo_identificacion'])): ?>
                    <div><label style="font-size:10px;color:#6B6560;">Tipo ID</label><input name="tipo_identificacion" style="width:100%;padding:10px;border-radius:8px;border:0.5px solid #D0CCC6;" value="<?= h((string)($edit['tipo_identificacion'] ?? '')) ?>"></div>
                <?php endif; ?>
                <?php if (isset($equipo_cols['bloqueo_tipo'])): ?>
                    <div><label style="font-size:10px;color:#6B6560;">Bloqueo</label><input name="bloqueo_tipo" style="width:100%;padding:10px;border-radius:8px;border:0.5px solid #D0CCC6;" value="<?= h((string)($edit['bloqueo_tipo'] ?? '')) ?>"></div>
                <?php endif; ?>
                <?php if (isset($equipo_cols['requiere_desbloqueo'])): ?>
                    <div style="display:flex;align-items:center;gap:8px;margin-top:18px;">
                        <input type="checkbox" name="requiere_desbloqueo" value="1" <?= !empty($edit['requiere_desbloqueo']) ? 'checked' : '' ?>>
                        <span style="font-size:12px;">Requiere desbloqueo</span>
                    </div>
                <?php endif; ?>
                <?php if (isset($equipo_cols['observaciones_ingreso'])): ?>
                    <div style="grid-column:1/-1;"><label style="font-size:10px;color:#6B6560;">Observaciones ingreso</label><textarea name="observaciones_ingreso" rows="3" style="width:100%;padding:10px;border-radius:8px;border:0.5px solid #D0CCC6;"><?= h((string)($edit['observaciones_ingreso'] ?? '')) ?></textarea></div>
                <?php endif; ?>
                <?php if (isset($equipo_cols['id_cliente']) && $cliente_pick !== ''): ?>
                    <div style="grid-column:1/-1;">
                        <label style="font-size:10px;color:#6B6560;">Cliente</label>
                        <select name="id_cliente" style="width:100%;padding:10px;border-radius:8px;border:0.5px solid #D0CCC6;">
                            <option value="0">— Seleccionar —</option>
                            <?php foreach ($clientes_opts as $c): ?>
                                <option value="<?= (int)$c['id'] ?>" <?= (int)($edit['id_cliente'] ?? 0) === (int)$c['id'] ? 'selected' : '' ?>><?= h((string)$c['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>
                <div style="grid-column:1/-1;display:flex;gap:8px;justify-content:flex-end;">
                    <a class="ordenes-ver-btn" href="?page=equipos">Cancelar</a>
                    <button type="submit" class="ordenes-ver-btn" style="background:#1F5C8B;color:#fff;border-color:#1F5C8B;">Guardar</button>
                </div>
            </form>
        <?php endif; ?>

        <div style="margin-top:14px;border-top:0.5px solid #EDECEA;padding-top:12px;">
            <div class="table-head" style="grid-template-columns:56px 1fr 1fr 100px 140px;">
                <div>ID</div><div>Tipo / Marca</div><div>Modelo</div><div>Cliente</div><div style="text-align:right;">Acciones</div>
            </div>
            <?php foreach ($rows as $r): ?>
                <?php
                $idc = (int)($r['id_cliente'] ?? 0);
                $nomCli = '';
                foreach ($clientes_opts as $c) {
                    if ((int)$c['id'] === $idc) {
                        $nomCli = (string)$c['nombre'];
                        break;
                    }
                }
                ?>
                <div class="table-row" style="grid-template-columns:56px 1fr 1fr 100px 140px;">
                    <div class="order-id"><?= (int)($r[$idField] ?? 0) ?></div>
                    <div class="order-cliente"><?= h(trim(((string)($r['tipo'] ?? '')) . ' ' . ((string)($r['marca'] ?? '')))) ?></div>
                    <div class="order-tecnico"><?= h((string)($r['modelo'] ?? '')) ?></div>
                    <div class="order-tecnico" style="font-size:11px;"><?= $nomCli !== '' ? h($nomCli) : '—' ?></div>
                    <div style="display:flex;gap:6px;justify-content:flex-end;">
                        <a class="ordenes-ver-btn" href="?page=equipos&action=edit&id=<?= (int)($r[$idField] ?? 0) ?>">Editar</a>
                        <form method="post" style="display:inline;" onsubmit="return confirm('¿Eliminar equipo?');">
                            <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>">
                            <input type="hidden" name="equipos_action" value="delete">
                            <input type="hidden" name="id_equipo" value="<?= (int)($r[$idField] ?? 0) ?>">
                            <button type="submit" class="ordenes-ver-btn" style="background:#FDF0F0;color:#B83232;border-color:#F5C2C2;">Eliminar</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php if (empty($rows)): ?>
                <div style="padding:14px;color:#6B6560;font-size:12px;">Sin equipos registrados.</div>
            <?php endif; ?>
        </div>
    </div>
</div>
