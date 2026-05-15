<?php

declare(strict_types=1);

/** @var mysqli $conn */
require_once __DIR__ . '/../../includes/ui_helper.php';
/** @var bool $auth_is_admin */

$ut = repairly_usuario_table($conn);
$search_q = isset($_GET['q']) && is_string($_GET['q']) ? trim($_GET['q']) : '';
$rows = [];
$idU = 'id_usuario';
if ($ut !== '') {
    $uc = table_columns($conn, $ut);
    $idU = repairly_usuario_id_field($uc);

    $sql = "SELECT * FROM `{$ut}`";
    $types = '';
    $params = [];
    if ($search_q !== '') {
        $like = '%' . $search_q . '%';
        $ors = [];
        $ors[] = "CAST(`{$idU}` AS CHAR) = ?";
        $types .= 's';
        $params[] = $search_q;

        $userCol = repairly_pick_column($uc, ['username', 'user', 'usuario', 'login', 'email']);
        if ($userCol !== null) {
            $ors[] = "`{$userCol}` LIKE ?";
            $types .= 's';
            $params[] = $like;
        }
        $rolCol = repairly_pick_column($uc, ['rol', 'role']);
        if ($rolCol !== null) {
            $ors[] = "`{$rolCol}` LIKE ?";
            $types .= 's';
            $params[] = $like;
        }
        $estCol = repairly_pick_column($uc, ['estado']);
        if ($estCol !== null) {
            $ors[] = "`{$estCol}` LIKE ?";
            $types .= 's';
            $params[] = $like;
        }
        if ($ors !== []) {
            $sql .= ' WHERE ' . implode(' OR ', $ors);
        }
    }
    $sql .= " ORDER BY `{$idU}` ASC";
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
}
?>
<div class="charts-row">
<?php render_stats_cards([[
'label'=>'Panel activo','value'=>count($rows ?? []),'sub'=>'Registros cargados','icon'=>'ti-chart-bar','bg'=>'linear-gradient(135deg,#111827,#1f2937)','color'=>'#93C5FD'],
['label'=>'Estado','value'=>'Online','sub'=>'Sistema sincronizado','icon'=>'ti-activity-heartbeat','bg'=>'linear-gradient(135deg,#14532d,#166534)','color'=>'#86EFAC'],
['label'=>'RepairlyRD','value'=>'IA','sub'=>'Automatización habilitada','icon'=>'ti-cpu','bg'=>'linear-gradient(135deg,#4c1d95,#5b21b6)','color'=>'#C4B5FD'],
]); ?>

    <div class="charts-card">
        <div class="card-header">
            <div>
                <div class="card-title">Usuarios</div>
                <div class="card-sub">Cuentas del sistema y roles</div>
            </div>
            <?php if (!empty($auth_is_admin)): ?>
                <a class="ordenes-ver-btn" href="?page=configuracion">Configuración</a>
            <?php endif; ?>
        </div>
        <div style="display:flex;gap:8px;align-items:center;margin-top:10px;flex-wrap:wrap;">
            <form method="get" style="display:flex;gap:8px;align-items:center;flex:1;min-width:240px;">
                <input type="hidden" name="page" value="usuarios">
                <div class="topbar-search" style="flex:1;min-width:220px;">
                    <i class="ti ti-search" aria-hidden="true"></i>
                    <input name="q" value="<?= h($search_q) ?>" placeholder="Buscar por usuario, rol, estado o ID" style="border:0;background:transparent;outline:none;font:inherit;color:#4D4841;width:100%;">
                </div>
                <button class="ordenes-ver-btn" type="submit">Buscar</button>
                <?php if ($search_q !== ''): ?>
                    <a class="ordenes-ver-btn" href="?page=usuarios">Limpiar</a>
                <?php endif; ?>
            </form>
        </div>
        <?php if ($ut === ''): ?>
            <p style="padding:12px;">Tabla Usuario no encontrada.</p>
        <?php elseif (empty($auth_is_admin)): ?>
            <p style="padding:12px;font-size:12px;color:#6B6560;">Solo un administrador puede modificar roles.</p>
            <div style="margin-top:12px;">
                <div class="table-head" style="grid-template-columns:56px 1fr 120px 100px;">
                    <div>ID</div><div>Usuario</div><div>Rol</div><div>Estado</div>
                </div>
                <?php foreach ($rows as $r): ?>
                    <?php
                    $rrol = '';
                    foreach (['rol', 'ROL', 'role'] as $rk) {
                        if (isset($r[$rk])) {
                            $rrol = (string)$r[$rk];
                            break;
                        }
                    }
                    $rest = '';
                    foreach (['estado', 'ESTADO'] as $ek) {
                        if (isset($r[$ek]) && (string)$r[$ek] !== '') {
                            $rest = (string)$r[$ek];
                            break;
                        }
                    }
                    ?>
                    <div class="table-row" style="grid-template-columns:56px 1fr 120px 100px;">
                        <div class="order-id"><?= (int)($r[$idU] ?? 0) ?></div>
                        <div class="order-cliente"><?= h((string)($r['username'] ?? $r['USERNAME'] ?? $r['user'] ?? '')) ?></div>
                        <div><?= h($rrol) ?></div>
                        <div><?= h($rest) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <?php
            $roles_opts = ['administrador', 'tecnico', 'supervisor', 'operador', 'cliente', 'pendiente'];
            ?>
            <p style="font-size:11px;color:#6B6560;margin-top:8px;">Cambia el rol y pulsa <strong>Guardar</strong> en la fila del usuario.</p>
            <div style="margin-top:12px;">
                <div class="table-head" style="grid-template-columns:56px 1fr 160px 100px 100px;margin-bottom:0;">
                    <div>ID</div><div>Usuario</div><div>Rol</div><div>Estado</div><div></div>
                </div>
                <?php foreach ($rows as $r): ?>
                    <?php
                    $rrol = '';
                    foreach (['rol', 'ROL', 'role'] as $rk) {
                        if (isset($r[$rk])) {
                            $rrol = (string)$r[$rk];
                            break;
                        }
                    }
                    $rest = '';
                    foreach (['estado', 'ESTADO'] as $ek) {
                        if (isset($r[$ek]) && (string)$r[$ek] !== '') {
                            $rest = (string)$r[$ek];
                            break;
                        }
                    }
                    ?>
                    <form method="post" class="table-row" style="grid-template-columns:56px 1fr 160px 100px 100px;align-items:center;margin:0;border-bottom:0.5px solid #EDECEA;">
                        <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>">
                        <input type="hidden" name="usuarios_action" value="update_user_role">
                        <input type="hidden" name="id_usuario" value="<?= (int)($r[$idU] ?? 0) ?>">
                        <div class="order-id"><?= (int)($r[$idU] ?? 0) ?></div>
                        <div class="order-cliente"><?= h((string)($r['username'] ?? $r['USERNAME'] ?? $r['user'] ?? '')) ?></div>
                        <div>
                            <select name="rol" style="width:100%;padding:8px;border-radius:8px;border:0.5px solid #D0CCC6;font-size:11px;">
                                <?php foreach ($roles_opts as $ro): ?>
                                    <option value="<?= h($ro) ?>" <?= repairly_normalize_role($rrol) === repairly_normalize_role($ro) ? 'selected' : '' ?>><?= h($ro) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div style="font-size:11px;"><?= h($rest) ?></div>
                        <div style="text-align:right;">
                            <button type="submit" class="ordenes-ver-btn" style="background:#1F5C8B;color:#fff;border-color:#1F5C8B;">Guardar</button>
                        </div>
                    </form>
                <?php endforeach; ?>
                <?php if (empty($rows)): ?>
                    <p style="font-size:12px;color:#6B6560;padding:10px;">No hay usuarios en la tabla.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
