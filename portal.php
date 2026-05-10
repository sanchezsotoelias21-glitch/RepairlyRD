<?php

declare(strict_types=1);

ob_start();

if (getenv('PHP_DISPLAY_ERRORS') === '1' || getenv('PHP_DISPLAY_ERRORS') === 'true') {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(E_ALL);
    ini_set('log_errors', '1');
}

require_once __DIR__ . '/src/config/database.php';
require_once __DIR__ . '/includes/session_bootstrap.php';
repairly_session_start();

require_once __DIR__ . '/includes/sql_helpers.php';
require_once __DIR__ . '/includes/auth.php';

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$uid = isset($_SESSION['repairly_uid']) ? (int)$_SESSION['repairly_uid'] : 0;
if ($uid <= 0) {
    repairly_redirect('login.php');
}

$me = repairly_load_usuario($conn, $uid);
if (!$me) {
    $_SESSION = [];
    session_destroy();
    repairly_redirect('login.php');
}

$canPanel = repairly_role_can_panel($me['rol']);

$nombre = $me['username'];
$welcome = isset($_GET['welcome']);

if (isset($_GET['logout'])) {
    $_SESSION = [];
    session_destroy();
    repairly_redirect('login.php');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>RepairlyRD — Mi cuenta</title>
<link rel="shortcut icon" href="logo.ico" type="image/x-icon">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.19.0/dist/tabler-icons.min.css">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',system-ui,sans-serif;background:#0A2540;min-height:100vh;color:#1C1A17}
.portal-top{background:#0A2540;padding:16px 22px;display:flex;align-items:center;justify-content:space-between;border-bottom:0.5px solid rgba(255,255,255,.1)}
.portal-brand{display:flex;align-items:center;gap:10px;color:#fff;font-weight:600;font-size:15px}
.portal-brand i{font-size:20px;color:#7EB8FF}
.portal-actions{display:flex;align-items:center;gap:10px}
.btn-panel{background:#1F5C8B;color:#fff;border:none;border-radius:10px;padding:10px 16px;font-size:13px;font-weight:600;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:6px}
.btn-panel:hover{background:#256fad}
.btn-ghost{color:rgba(255,255,255,.75);font-size:12px;text-decoration:none}
.btn-ghost:hover{color:#fff}
.wrap{max-width:720px;margin:0 auto;padding:28px 20px 40px}
.card{background:#fff;border-radius:14px;border:0.5px solid #D0CCC6;padding:22px 20px;box-shadow:0 8px 28px rgba(0,0,0,.08)}
.card h1{font-size:18px;color:#0A2540;margin-bottom:8px}
.card p{font-size:13px;color:#4D4841;line-height:1.55;margin-bottom:12px}
.badge{display:inline-block;font-size:11px;padding:4px 10px;border-radius:20px;background:#E8F1FB;color:#1F5C8B;font-weight:600;margin-top:6px}
.steps{font-size:12px;color:#6B6560;margin-top:14px;padding-left:18px}
.steps li{margin-bottom:6px}
</style>
</head>
<body>
<header class="portal-top">
    <div class="portal-brand"><i class="ti ti-tool"></i> RepairlyRD</div>
    <div class="portal-actions">
        <?php if ($canPanel): ?>
            <a class="btn-panel" href="index.php"><i class="ti ti-layout-dashboard"></i> Ir al panel</a>
        <?php endif; ?>
        <a class="btn-ghost" href="portal.php?logout=1">Cerrar sesión</a>
    </div>
</header>
<div class="wrap">
    <div class="card">
        <h1><?= $welcome ? '¡Cuenta creada!' : 'Bienvenido a RepairlyRD' ?></h1>
        <p>Hola, <strong><?= h($nombre) ?></strong>. Esta es tu página dentro del ecosistema del taller: seguimiento de equipos, comunicación y próximas mejoras.</p>
        <span class="badge">Rol actual: <?= h(ucfirst(repairly_normalize_role($me['rol']) ?: 'usuario')) ?></span>

        <?php if (!$canPanel): ?>
            <p style="margin-top:16px;">Tu cuenta está activa. El acceso al <strong>panel interno del taller</strong> lo concede un <strong>administrador</strong> cuando asigne un rol con permiso (por ejemplo técnico u operador).</p>
            <ol class="steps">
                <li>Espera la confirmación del equipo o contacta al taller.</li>
                <li>Cuando tu rol tenga permiso, verás el botón <strong>Ir al panel</strong> arriba a la derecha.</li>
            </ol>
        <?php else: ?>
            <p style="margin-top:16px;">Tienes acceso al panel de gestión. Pulsa <strong>Ir al panel</strong> para continuar.</p>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
