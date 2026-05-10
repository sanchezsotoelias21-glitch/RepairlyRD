<?php

declare(strict_types=1);

/** @var mysqli $conn */

$nt = pick_table($conn, ['notificacion', 'Notificacion']);
$rows = [];
if ($nt !== '') {
    $nc = table_columns($conn, $nt);
    $ord = isset($nc['fecha_envio']) ? 'fecha_envio' : 'id_notificacion';
    $rows = db_rows($conn, "SELECT * FROM `{$nt}` ORDER BY `{$ord}` DESC LIMIT 200");
}
?>
<div class="charts-row">
    <div class="charts-card">
        <div class="card-header">
            <div>
                <div class="card-title">Notificaciones</div>
                <div class="card-sub">Historial reciente del sistema</div>
            </div>
        </div>
        <?php if ($nt === ''): ?>
            <p style="padding:12px;color:#B83232;">Tabla Notificacion no encontrada.</p>
        <?php else: ?>
            <div style="margin-top:12px;">
                <?php foreach ($rows as $r): ?>
                    <div style="padding:12px 14px;border-bottom:0.5px solid #EDECEA;font-size:12px;">
                        <div style="font-weight:600;color:#1C1A17;"><?= h((string)($r['tipo'] ?? 'aviso')) ?></div>
                        <div style="margin-top:4px;color:#4D4841;"><?= h((string)($r['mensaje'] ?? '')) ?></div>
                        <div style="font-size:10px;color:#8C8479;margin-top:6px;">
                            <?= h((string)($r['fecha_envio'] ?? '')) ?>
                            <?php if (isset($r['id_orden'])): ?> · Orden #<?= (int)$r['id_orden'] ?><?php endif; ?>
                            · <?= h((string)($r['estado'] ?? '')) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($rows)): ?>
                    <p style="padding:12px;color:#6B6560;">Sin notificaciones registradas.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
