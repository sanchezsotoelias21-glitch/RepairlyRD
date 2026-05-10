<?php

declare(strict_types=1);

/** @var mysqli $conn */

$ut = repairly_usuario_table($conn);
$rows = $ut !== '' ? db_rows($conn, "SELECT * FROM `{$ut}` ORDER BY id_usuario ASC") : [];
?>
<div class="charts-row">
    <div class="charts-card">
        <div class="card-header">
            <div>
                <div class="card-title">Usuarios</div>
                <div class="card-sub">Listado de cuentas</div>
            </div>
            <a class="ordenes-ver-btn" href="?page=configuracion">Editar roles</a>
        </div>
        <?php if ($ut === ''): ?>
            <p style="padding:12px;">Tabla Usuario no encontrada.</p>
        <?php else: ?>
            <div style="margin-top:12px;">
                <div class="table-head" style="grid-template-columns:56px 1fr 120px 100px;">
                    <div>ID</div><div>Usuario</div><div>Rol</div><div>Estado</div>
                </div>
                <?php foreach ($rows as $r): ?>
                    <div class="table-row" style="grid-template-columns:56px 1fr 120px 100px;">
                        <div class="order-id"><?= (int)($r['id_usuario'] ?? 0) ?></div>
                        <div class="order-cliente"><?= h((string)($r['username'] ?? '')) ?></div>
                        <div><?= h((string)($r['rol'] ?? '')) ?></div>
                        <div><?= h((string)($r['estado'] ?? '')) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
