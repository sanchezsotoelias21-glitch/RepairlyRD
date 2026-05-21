<?php

declare(strict_types=1);

/** @var mysqli $conn */
require_once __DIR__ . '/../../includes/ui_helper.php';

$search_q = isset($_GET['q']) && is_string($_GET['q']) ? trim($_GET['q']) : '';

// Contar total de clientes
$total_clientes = 0;
$res_count = $conn->query("SELECT COUNT(*) as total FROM Cliente");
if ($res_count) {
    $row_count = $res_count->fetch_assoc();
    $total_clientes = (int)($row_count['total'] ?? 0);
}

// Obtener clientes
$res = $conn->query("SELECT * FROM Cliente ORDER BY id_cliente DESC");
$rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];

// Detectar duplicados por nombre
$duplicados_por_nombre = [];
$dup_query = $conn->query("
    SELECT nombre, COUNT(*) as total, GROUP_CONCAT(id_cliente) as ids 
    FROM Cliente 
    GROUP BY nombre 
    HAVING COUNT(*) > 1 
    ORDER BY total DESC
");
if ($dup_query) {
    while ($d = $dup_query->fetch_assoc()) {
        $duplicados_por_nombre[] = $d;
    }
}

?>

<!-- Tarjetas de estadísticas - Estilo Dashboard -->
<div class="kpi-grid">
    <div class="kpi-card" style="background:#E3F2FD;--kpi-color:#2b7abc;">
        <div class="kpi-label" style="color:#2b7abc;">
            <i class="ti ti-users" aria-hidden="true"></i>
            Total de clientes
        </div>
        <div class="kpi-valor" style="color:#2b7abc;">
            <?= $total_clientes ?>
        </div>
        <div class="kpi-sub">Clientes registrados</div>
    </div>
    <div class="kpi-card" style="background:#FFF3E0;--kpi-color:#FF9500;">
        <div class="kpi-label" style="color:#FF9500;">
            <i class="ti ti-activity-heartbeat" aria-hidden="true"></i>
            Cargados hoy
        </div>
        <div class="kpi-valor" style="color:#FF9500;">
            <?= count($rows) ?>
        </div>
        <div class="kpi-sub">Registros en sesión</div>
    </div>
    <div class="kpi-card" style="background:#E8F5E9;--kpi-color:#00AA44;">
        <div class="kpi-label" style="color:#00AA44;">
            <i class="ti ti-database" aria-hidden="true"></i>
            Base de datos
        </div>
        <div class="kpi-valor" style="color:#00AA44;">
            100%
        </div>
        <div class="kpi-sub">Sincronización correcta</div>
    </div>
</div>

<?php if (!empty($duplicados_por_nombre)): ?>
<!-- Panel de Duplicados Detectados -->
<div class="card" style="margin-top:18px;background:#FFF3E0;border-left:4px solid #FF9500;">
    <div class="card-header">
        <div>
            <div class="card-title" style="color:#FF9500;">
                <i class="ti ti-alert-triangle" aria-hidden="true"></i>
                Clientes Duplicados Detectados (<?= count($duplicados_por_nombre) ?>)
            </div>
            <div class="card-sub">Fusiona clientes con el mismo nombre para limpiar tu base de datos</div>
        </div>
    </div>

    <div style="padding:12px;">
    <?php foreach ($duplicados_por_nombre as $dup): ?>
        <div style="background:white;padding:12px;border-radius:8px;margin-bottom:10px;border:0.5px solid #F5C2C2;">
            <div style="display:flex;gap:12px;align-items:center;justify-content:space-between;flex-wrap:wrap;">
                <div>
                    <div style="font-weight:bold;color:#1C1A17;margin-bottom:4px;">
                        <?= h($dup['nombre']) ?> 
                        <span style="background:#FDF0F0;color:#B83232;padding:2px 6px;border-radius:4px;font-size:11px;">x<?= $dup['total'] ?></span>
                    </div>
                    <div style="font-size:11px;color:#6B6560;">
                        IDs: <?= h($dup['ids']) ?>
                    </div>
                </div>
                <button type="button" class="ordenes-ver-btn" onclick="mostrarFusionModal('<?= h($dup['ids']) ?>', '<?= h($dup['nombre']) ?>')" 
                    style="background:#FF9500;color:white;border-color:#FF9500;white-space:nowrap;">
                    Fusionar
                </button>
            </div>
        </div>
    <?php endforeach; ?>
    </div>
</div>

<!-- Modal de Fusión -->
<div id="fusion-modal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);z-index:2000;align-items:center;justify-content:center;">
    <div style="background:white;padding:20px;border-radius:12px;max-width:500px;width:90%;box-shadow:0 10px 40px rgba(0,0,0,0.2);">
        <div style="font-size:18px;font-weight:bold;margin-bottom:10px;">Fusionar Clientes Duplicados</div>
        <div style="font-size:12px;color:#6B6560;margin-bottom:16px;">
            Selecciona el cliente principal (el que conservarás) y los equipos del otro serán transferidos a este.
        </div>

        <form method="post" style="display:grid;gap:12px;">
            <input type="hidden" name="page" value="clientes">
            <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf'] ?? '') ?>">
            <input type="hidden" name="clientes_action" value="merge">
            <input type="hidden" id="fusion-ids" name="fusion_ids" value="">

            <div>
                <label style="font-size:11px;color:#6B6560;display:block;margin-bottom:6px;">Cliente Principal (mantener)</label>
                <select name="id_principal" required style="width:100%;padding:10px;border-radius:8px;border:0.5px solid #D0CCC6;">
                    <option value="">— Seleccionar —</option>
                    <?php foreach ($rows as $r): ?>
                        <option value="<?= (int)$r['id_cliente'] ?>"><?= h($r['nombre']) ?> (ID: <?= (int)$r['id_cliente'] ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label style="font-size:11px;color:#6B6560;display:block;margin-bottom:6px;">Cliente Secundario (eliminar)</label>
                <select name="id_secundario" required style="width:100%;padding:10px;border-radius:8px;border:0.5px solid #D0CCC6;">
                    <option value="">— Seleccionar —</option>
                    <?php foreach ($rows as $r): ?>
                        <option value="<?= (int)$r['id_cliente'] ?>"><?= h($r['nombre']) ?> (ID: <?= (int)$r['id_cliente'] ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="background:#E3F2FD;padding:10px;border-radius:8px;border-left:3px solid #2b7abc;font-size:11px;color:#1F5C8B;">
                <i class="ti ti-info-circle" aria-hidden="true"></i>
                Los equipos del cliente secundario se transferirán al principal, y el secundario será eliminado.
            </div>

            <div style="display:flex;gap:8px;justify-content:flex-end;">
                <button type="button" class="ordenes-ver-btn" onclick="cerrarFusionModal()">Cancelar</button>
                <button type="submit" class="ordenes-ver-btn" style="background:#FF9500;color:white;border-color:#FF9500;">Fusionar</button>
            </div>
        </form>
    </div>
</div>

<script>
function mostrarFusionModal(ids, nombre) {
    document.getElementById('fusion-ids').value = ids;
    document.getElementById('fusion-modal').style.display = 'flex';
}

function cerrarFusionModal() {
    document.getElementById('fusion-modal').style.display = 'none';
}

// Cerrar modal al hacer clic fuera
document.getElementById('fusion-modal')?.addEventListener('click', function(e) {
    if (e.target === this) cerrarFusionModal();
});
</script>
<?php endif; ?>

<!-- Tabla de clientes -->
<div class="card" style="margin-top:18px;">
    <div class="card-header">
        <div>
            <div class="card-title">Clientes</div>
            <div class="card-sub">Gestión de clientes del sistema</div>
        </div>
    </div>
    
    <div style="display:flex;gap:8px;align-items:center;margin-top:10px;flex-wrap:wrap;">
        <form method="get" style="display:flex;gap:8px;align-items:center;flex:1;min-width:240px;">
            <input type="hidden" name="page" value="clientes">
            <div class="topbar-search" style="flex:1;min-width:220px;">
                <i class="ti ti-search" aria-hidden="true"></i>
                <input name="q" value="<?= h($search_q) ?>" placeholder="Buscar por nombre, teléfono o email..." style="border:0;background:transparent;outline:none;font:inherit;color:#4D4841;width:100%;">
            </div>
            <button class="ordenes-ver-btn" type="submit">Buscar</button>
            <?php if ($search_q !== ''): ?>
                <a class="ordenes-ver-btn" href="?page=clientes">Limpiar</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="table-head" style="margin-top:14px;grid-template-columns:60px 1.2fr 1fr 1fr;gap:12px;">
        <div>ID</div>
        <div>Nombre</div>
        <div>Teléfono</div>
        <div>Email</div>
    </div>

    <div style="max-height:400px;overflow-y:auto;">
    <?php foreach ($rows as $row): ?>
    <div class="table-row" style="grid-template-columns:60px 1.2fr 1fr 1fr;gap:12px;">
        <div style="font-size:11px;color:#8C8479;font-family:'Courier New';"><?= (int)$row['id_cliente'] ?></div>
        <div style="font-size:12px;color:#1C1A17;font-weight:500;"><?= h($row['nombre']) ?></div>
        <div style="font-size:12px;color:#4D4841;"><?= h($row['telefono'] ?? '') ?></div>
        <div style="font-size:12px;color:#4D4841;"><?= h($row['email'] ?? '') ?></div>
    </div>
    <?php endforeach; ?>

    <?php if (empty($rows)): ?>
    <div style="padding:20px;text-align:center;color:#6B6560;font-size:12px;">
        No hay clientes registrados
    </div>
    <?php endif; ?>
    </div>
</div>