<?php

declare(strict_types=1);

/** @var mysqli $conn */

$action = $_GET['action'] ?? '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$search_q = isset($_GET['q']) && is_string($_GET['q']) ? trim($_GET['q']) : '';

if (empty($tecnico_table)) {
    echo '<div class="charts-card">Tabla Técnico no encontrada.</div>';
    return;
}

$idField = 'id_tecnico';
foreach (array_keys($tecnico_cols) as $k) {
    if (strcasecmp((string)$k, 'id_tecnico') === 0) {
        $idField = $k;
        break;
    }
}

$rows = [];
$sql = "SELECT * FROM `{$tecnico_table}`";
$types = '';
$params = [];
if ($search_q !== '') {
    $like = '%' . $search_q . '%';
    $ors = [];
    $ors[] = "CAST(`{$idField}` AS CHAR) = ?";
    $types .= 's';
    $params[] = $search_q;
    foreach (['nombre', 'especialidad', 'email', 'telefono'] as $c) {
        if (isset($tecnico_cols[$c])) {
            $ors[] = "`{$c}` LIKE ?";
            $types .= 's';
            $params[] = $like;
        }
    }
    if ($ors !== []) {
        $sql .= ' WHERE ' . implode(' OR ', $ors);
    }
}
$sql .= " ORDER BY `{$idField}` DESC LIMIT 200";
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
    $st = $conn->prepare("SELECT * FROM `{$tecnico_table}` WHERE `{$idField}`=? LIMIT 1");
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
                <div class="card-title">Técnicos</div>
                <div class="card-sub">Equipo técnico del taller</div>
            </div>
            <a class="ordenes-ver-btn" href="?page=tecnicos&action=new">Nuevo técnico</a>
        </div>
        <div style="display:flex;gap:8px;align-items:center;margin-top:10px;flex-wrap:wrap;">
            <form method="get" style="display:flex;gap:8px;align-items:center;flex:1;min-width:240px;">
                <input type="hidden" name="page" value="tecnicos">
                <div class="topbar-search" style="flex:1;min-width:220px;">
                    <i class="ti ti-search" aria-hidden="true"></i>
                    <input name="q" value="<?= h($search_q) ?>" placeholder="Buscar por nombre, especialidad o ID" style="border:0;background:transparent;outline:none;font:inherit;color:#4D4841;width:100%;">
                </div>
                <button class="ordenes-ver-btn" type="submit">Buscar</button>
                <?php if ($search_q !== ''): ?>
                    <a class="ordenes-ver-btn" href="?page=tecnicos">Limpiar</a>
                <?php endif; ?>
            </form>
        </div>
        <?php if ($action === 'new' || $action === 'edit'): ?>
            <form method="post" style="margin-top:14px;display:grid;grid-template-columns:1fr 1fr;gap:10px;max-width:640px;">
                <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>">
                <input type="hidden" name="tecnicos_action" value="<?= $action === 'edit' ? 'update' : 'create' ?>">
                <?php if ($action === 'edit'): ?>
                    <input type="hidden" name="id_tecnico" value="<?= (int)($edit[$idField] ?? 0) ?>">
                <?php endif; ?>
                <?php if (isset($tecnico_cols['nombre'])): ?>
                    <div style="grid-column:1/-1;"><label style="font-size:10px;color:#6B6560;">Nombre *</label>
                        <input name="nombre" required style="width:100%;padding:10px;border-radius:8px;border:0.5px solid #D0CCC6;" value="<?= h((string)($edit['nombre'] ?? '')) ?>"></div>
                <?php endif; ?>
                <?php if (isset($tecnico_cols['especialidad'])): ?>
                    <div><label style="font-size:10px;color:#6B6560;">Especialidad</label>
                        <input name="especialidad" style="width:100%;padding:10px;border-radius:8px;border:0.5px solid #D0CCC6;" value="<?= h((string)($edit['especialidad'] ?? '')) ?>"></div>
                <?php endif; ?>
                <?php if (isset($tecnico_cols['email'])): ?>
                    <div><label style="font-size:10px;color:#6B6560;">Email</label>
                        <input name="email" type="email" style="width:100%;padding:10px;border-radius:8px;border:0.5px solid #D0CCC6;" value="<?= h((string)($edit['email'] ?? '')) ?>"></div>
                <?php endif; ?>
                <?php if (isset($tecnico_cols['telefono'])): ?>
                    <div><label style="font-size:10px;color:#6B6560;">Teléfono</label>
                        <input name="telefono" style="width:100%;padding:10px;border-radius:8px;border:0.5px solid #D0CCC6;" value="<?= h((string)($edit['telefono'] ?? '')) ?>"></div>
                <?php endif; ?>
                <?php if (isset($tecnico_cols['fecha_contrato'])): ?>
                    <div><label style="font-size:10px;color:#6B6560;">Fecha contrato</label>
                        <input name="fecha_contrato" type="date" style="width:100%;padding:10px;border-radius:8px;border:0.5px solid #D0CCC6;" value="<?= h(substr((string)($edit['fecha_contrato'] ?? ''), 0, 10)) ?>"></div>
                <?php endif; ?>
                <?php if (isset($tecnico_cols['estado'])): ?>
                    <div><label style="font-size:10px;color:#6B6560;">Estado</label>
                        <input name="estado" style="width:100%;padding:10px;border-radius:8px;border:0.5px solid #D0CCC6;" value="<?= h((string)($edit['estado'] ?? 'activo')) ?>"></div>
                <?php endif; ?>
                <div style="grid-column:1/-1;display:flex;gap:8px;justify-content:flex-end;">
                    <a class="ordenes-ver-btn" href="?page=tecnicos">Cancelar</a>
                    <button type="submit" class="ordenes-ver-btn" style="background:#1F5C8B;color:#fff;border-color:#1F5C8B;">Guardar</button>
                </div>
            </form>
        <?php endif; ?>

        <div style="margin-top:14px;border-top:0.5px solid #EDECEA;padding-top:12px;">
            <div class="table-head" style="grid-template-columns:48px 1fr 1fr 120px;">
                <div>ID</div><div>Nombre</div><div>Especialidad</div><div style="text-align:right;">Acciones</div>
            </div>
            <?php foreach ($rows as $r): ?>
                <div class="table-row" style="grid-template-columns:48px 1fr 1fr 120px;">
                    <div class="order-id"><?= (int)($r[$idField] ?? 0) ?></div>
                    <div class="order-cliente"><?= h((string)($r['nombre'] ?? '')) ?></div>
                    <div class="order-tecnico"><?= h((string)($r['especialidad'] ?? '')) ?></div>
                    <div style="display:flex;gap:6px;justify-content:flex-end;">
                        <a class="ordenes-ver-btn" href="?page=tecnicos&action=edit&id=<?= (int)($r[$idField] ?? 0) ?>">Editar</a>
                        <form method="post" style="display:inline;" onsubmit="return confirm('¿Eliminar?');">
                            <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>">
                            <input type="hidden" name="tecnicos_action" value="delete">
                            <input type="hidden" name="id_tecnico" value="<?= (int)($r[$idField] ?? 0) ?>">
                            <button type="submit" class="ordenes-ver-btn" style="background:#FDF0F0;color:#B83232;">Eliminar</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
