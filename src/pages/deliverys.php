<?php

declare(strict_types=1);

/** @var mysqli $conn */
require_once __DIR__ . '/../../includes/ui_helper.php';

$deliveries = [];
$query = "SELECT * FROM Deliveries ORDER BY FechaCreacion DESC";
$result = $conn->query($query);
if ($result) {
    while($row = $result->fetch_assoc()) {
        $deliveries[] = $row;
    }
}

// Contar estados
$total_deliveries = count($deliveries);
$en_transito = 0;
$completados = 0;

foreach ($deliveries as $d) {
    $estado = strtolower($d['Estado'] ?? '');
    if ($estado === 'en tránsito' || $estado === 'en transito') {
        $en_transito++;
    } elseif ($estado === 'completado' || $estado === 'entregado') {
        $completados++;
    }
}

?>

<!-- Tarjetas de estadísticas - Estilo Dashboard -->
<div class="kpi-grid">
    <div class="kpi-card" style="background:#E3F2FD;--kpi-color:#2b7abc;">
        <div class="kpi-label" style="color:#2b7abc;">
            <i class="ti ti-truck" aria-hidden="true"></i>
            Total deliveries
        </div>
        <div class="kpi-valor" style="color:#2b7abc;">
            <?= $total_deliveries ?>
        </div>
        <div class="kpi-sub">Entregas registradas</div>
    </div>
    <div class="kpi-card" style="background:#FFF3E0;--kpi-color:#FF9500;">
        <div class="kpi-label" style="color:#FF9500;">
            <i class="ti ti-activity-heartbeat" aria-hidden="true"></i>
            En tránsito
        </div>
        <div class="kpi-valor" style="color:#FF9500;">
            <?= $en_transito ?>
        </div>
        <div class="kpi-sub">Entregas activas</div>
    </div>
    <div class="kpi-card" style="background:#E8F5E9;--kpi-color:#00AA44;">
        <div class="kpi-label" style="color:#00AA44;">
            <i class="ti ti-check-circle" aria-hidden="true"></i>
            Completadas
        </div>
        <div class="kpi-valor" style="color:#00AA44;">
            <?= $completados ?>
        </div>
        <div class="kpi-sub">Entregas exitosas</div>
    </div>
</div>

<!-- Tabla de deliveries -->
<div class="card" style="margin-top:18px;">
    <div class="card-header">
        <div>
            <div class="card-title">🚚 Gestión de Deliveries</div>
            <div class="card-sub">Tracking y estado de entregas</div>
        </div>
    </div>

    <div class="table-head" style="margin-top:14px;grid-template-columns:70px 1.2fr 120px 100px;gap:12px;">
        <div>ID</div>
        <div>Código Tracking</div>
        <div>Estado</div>
        <div>Fecha Salida</div>
    </div>

    <?php foreach ($deliveries as $delivery): ?>
    <div class="table-row" style="grid-template-columns:70px 1.2fr 120px 100px;gap:12px;">
        <div style="font-size:11px;color:#8C8479;font-family:'Courier New';"><?= htmlspecialchars($delivery['IdDelivery']) ?></div>
        <div style="font-size:12px;color:#1C1A17;font-weight:500;"><?= htmlspecialchars($delivery['CodigoTracking']) ?></div>
        <div style="font-size:11px;">
            <?php 
                $estado = htmlspecialchars($delivery['Estado']);
                $estado_lower = strtolower($estado);
                $bg_color = '#E3F2FD';
                $text_color = '#2b7abc';
                
                if ($estado_lower === 'en tránsito' || $estado_lower === 'en transito') {
                    $bg_color = '#FFF3E0';
                    $text_color = '#FF9500';
                } elseif ($estado_lower === 'completado' || $estado_lower === 'entregado') {
                    $bg_color = '#E8F5E9';
                    $text_color = '#00AA44';
                }
            ?>
            <span style="background:<?= $bg_color ?>;color:<?= $text_color ?>;padding:4px 8px;border-radius:4px;display:inline-block;">
                <?= $estado ?>
            </span>
        </div>
        <div style="font-size:12px;color:#4D4841;"><?= htmlspecialchars($delivery['FechaSalida']) ?></div>
    </div>
    <?php endforeach; ?>

    <?php if (empty($deliveries)): ?>
    <div style="padding:20px;text-align:center;color:#6B6560;font-size:12px;">
        No hay deliveries registrados
    </div>
    <?php endif; ?>
</div>