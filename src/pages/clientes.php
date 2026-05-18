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

?>

<!-- Tarjetas de estadísticas -->
<?php render_stats_cards([
    [
        'label' => 'Total de clientes',
        'value' => $total_clientes,
        'sub' => 'Clientes registrados',
        'icon' => 'ti-users',
    ],
    [
        'label' => 'Activos hoy',
        'value' => count($rows),
        'sub' => 'Clientes cargados',
        'icon' => 'ti-activity-heartbeat',
    ],
    [
        'label' => 'Estado',
        'value' => 'Sincronizado',
        'sub' => 'Base de datos actualizada',
        'icon' => 'ti-database',
    ]
]); ?>

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