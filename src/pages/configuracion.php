<?php



/** @var mysqli $conn */
/** @var bool $auth_is_admin */

$search_q = isset($_GET['q']) && is_string($_GET['q']) ? trim($_GET['q']) : '';
?>
<div class="charts-row">
    <div class="charts-card">
        <div class="card-header">
            <div>
                <div class="card-title">Configuración</div>
                <div class="card-sub">Apariencia y permisos</div>
            </div>
        </div>

        <div style="margin-top:16px;padding:14px;border:0.5px solid #EDECEA;border-radius:10px;max-width:520px;">
            <div style="font-size:13px;font-weight:600;margin-bottom:10px;">Apariencia</div>
            <label style="display:flex;align-items:center;gap:10px;font-size:12px;margin-bottom:10px;cursor:pointer;">
                <input type="checkbox" id="toggle-dark"> Modo oscuro (se guarda en este navegador)
            </label>
            <label style="display:flex;align-items:center;gap:10px;font-size:12px;cursor:pointer;">
                <input type="checkbox" id="toggle-night"> Luz nocturna (filtro cálido sobre la pantalla)
            </label>
            <p style="font-size:11px;color:#6B6560;margin-top:10px;">Los ajustes se aplican al instante y persisten en <code>localStorage</code>.</p>
        </div>

        <?php if (!empty($auth_is_admin)):
            $ut = repairly_usuario_table($conn);
            $all_users = [];
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
                        $all_users = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
                        $st->close();
                    }
                } else {
                    $all_users = db_rows($conn, $sql);
                }
            }
            $roles_opts = ['administrador', 'tecnico', 'supervisor', 'operador', 'cliente', 'pendiente'];
            ?>
            <div style="margin-top:22px;padding:14px;border:0.5px solid #EDECEA;border-radius:10px;">
                <div style="font-size:13px;font-weight:600;margin-bottom:10px;">Usuarios y roles</div>
                <p style="font-size:11px;color:#6B6560;margin-bottom:12px;">Solo administradores pueden cambiar el rol. Los usuarios nuevos quedan como <strong>cliente</strong> hasta que se les asigne acceso al panel.</p>
                <div style="display:flex;gap:8px;align-items:center;margin:10px 0 12px;flex-wrap:wrap;">
                    <form method="get" style="display:flex;gap:8px;align-items:center;flex:1;min-width:240px;">
                        <input type="hidden" name="page" value="configuracion">
                        <div class="topbar-search" style="flex:1;min-width:220px;">
                            <i class="ti ti-search" aria-hidden="true"></i>
                            <input name="q" value="<?= h($search_q) ?>" placeholder="Buscar usuarios por nombre, rol o ID" style="border:0;background:transparent;outline:none;font:inherit;color:#4D4841;width:100%;">
                        </div>
                        <button class="ordenes-ver-btn" type="submit">Buscar</button>
                        <?php if ($search_q !== ''): ?>
                            <a class="ordenes-ver-btn" href="?page=configuracion">Limpiar</a>
                        <?php endif; ?>
                    </form>
                </div>
                <div class="table-head" style="grid-template-columns:56px 1fr 160px 100px;margin-bottom:0;">
                    <div>ID</div><div>Usuario</div><div>Rol</div><div></div>
                </div>
                <div style="max-height:400px;overflow-y:auto;">
                <?php foreach ($all_users as $urow): ?>
                    <form method="post" class="table-row" style="grid-template-columns:56px 1fr 160px 100px;align-items:center;margin:0;border-bottom:0.5px solid #EDECEA;">
                        <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>">
                        <input type="hidden" name="config_action" value="update_user_role">
                        <input type="hidden" name="id_usuario" value="<?= (int)($urow[$idU] ?? 0) ?>">
                        <div class="order-id"><?= (int)($urow[$idU] ?? 0) ?></div>
                        <div class="order-cliente"><?= h((string)($urow['username'] ?? '')) ?></div>
                        <div>
                            <select name="rol" style="width:100%;padding:8px;border-radius:8px;border:0.5px solid #D0CCC6;font-size:11px;">
                                <?php
                                $urol = '';
                                foreach (['rol', 'ROL', 'role'] as $rk) {
                                    if (isset($urow[$rk])) {
                                        $urol = (string)$urow[$rk];
                                        break;
                                    }
                                }
                                ?>
                                <?php foreach ($roles_opts as $ro): ?>
                                    <option value="<?= h($ro) ?>" <?= repairly_normalize_role($urol) === repairly_normalize_role($ro) ? 'selected' : '' ?>><?= h($ro) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div style="text-align:right;">
                            <button type="submit" class="ordenes-ver-btn" style="background:#1F5C8B;color:#fff;border-color:#1F5C8B;">Guardar</button>
                        </div>
                    </form>
                <?php endforeach; ?>
                <?php if (empty($all_users)): ?>
                    <p style="font-size:12px;color:#6B6560;padding:10px;">No hay usuarios en la tabla.</p>
                <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <p style="margin-top:16px;font-size:12px;color:#6B6560;">Solo un administrador puede modificar roles desde esta pantalla.</p>
        <?php endif; ?>
    </div>
</div>
<script>
(function(){
    var root = document.documentElement;
    var darkKey = 'repairly_dark';
    var nightKey = 'repairly_night';
    function applyDark(on){
        root.classList.toggle('theme-dark', on);
        localStorage.setItem(darkKey, on ? '1' : '0');
    }
    function applyNight(on){
        var el = document.getElementById('night-overlay');
        if (el) { el.classList.toggle('on', on); }
        localStorage.setItem(nightKey, on ? '1' : '0');
    }
    var td = document.getElementById('toggle-dark');
    var tn = document.getElementById('toggle-night');
    if (td) {
        td.checked = localStorage.getItem(darkKey) === '1';
        applyDark(td.checked);
        td.addEventListener('change', function(){ applyDark(td.checked); });
    }
    if (tn) {
        tn.checked = localStorage.getItem(nightKey) === '1';
        applyNight(tn.checked);
        tn.addEventListener('change', function(){ applyNight(tn.checked); });
    }
})();
</script>
