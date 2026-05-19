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

/** Nombre real de columna en la tabla (MySQL puede variar mayúsculas). */
function usuario_real_column(array $cols, string $needle): ?string
{
    foreach (array_keys($cols) as $k) {
        if (strcasecmp((string)$k, $needle) === 0) {
            return (string)$k;
        }
    }
    return null;
}

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
}

function require_csrf_login(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }
    $token = $_POST['csrf'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['csrf'] ?? '', $token)) {
        http_response_code(400);
        exit('Solicitud inválida (CSRF).');
    }
}

$usuario_table = repairly_usuario_table($conn);
$error = '';
$info = isset($_GET['msg']) && is_string($_GET['msg']) ? $_GET['msg'] : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_login();
    $action = $_POST['auth_action'] ?? '';
    if ($action === 'login') {
        $user = trim((string)($_POST['username'] ?? ''));
        $pass = (string)($_POST['password'] ?? '');
        if ($user === '' || $pass === '') {
            $error = 'Completa usuario y contraseña.';
        } elseif ($usuario_table === '') {
            $error = 'No existe la tabla de usuarios en la base de datos.';
        } else {
            $cols = table_columns($conn, $usuario_table);
            $idField = repairly_usuario_id_field($cols);
            $userCol = isset($cols['username']) ? 'username' : (isset($cols['USERNAME']) ? 'USERNAME' : 'username');
            $sql = "SELECT * FROM `{$usuario_table}` WHERE `{$userCol}` = ? LIMIT 1";
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param('s', $user);
                $stmt->execute();
                $res = $stmt->get_result();
                $row = $res ? $res->fetch_assoc() : null;
                $stmt->close();
                $hash = '';
                if ($row) {
                    foreach (['password_hash', 'PASSWORD_HASH', 'password', 'clave'] as $hk) {
                        if (isset($row[$hk]) && (string)$row[$hk] !== '') {
                            $hash = (string)$row[$hk];
                            break;
                        }
                    }
                }
                $estadoRaw = 'activo';
                if ($row) {
                    foreach (['estado', 'ESTADO'] as $ek) {
                        if (isset($row[$ek]) && $row[$ek] !== '') {
                            $estadoRaw = $row[$ek];
                            break;
                        }
                    }
                }
                if (!$row) {
                    $error = 'Usuario o contraseña incorrectos.';
                } elseif (!repairly_usuario_esta_activo($estadoRaw)) {
                    $error = 'Cuenta inactiva. Contacta al administrador.';
                } elseif ($hash === '' || !password_verify($pass, $hash)) {
                    $error = 'Usuario o contraseña incorrectos.';
                } else {
                    $uidLogin = isset($row[$idField]) ? (int)$row[$idField] : 0;
                    if ($uidLogin <= 0) {
                        $error = 'Error de esquema: no se encontró la columna de ID del usuario (id_usuario o id).';
                    } else {
                        $_SESSION['repairly_uid'] = $uidLogin;
                        session_regenerate_id(true);
                        $next = isset($_POST['next']) && is_string($_POST['next']) ? $_POST['next'] : '';
                        $target = 'index.php';
                        if ($next !== '' && str_starts_with(ltrim($next, '/'), 'index.php')) {
                            $target = ltrim($next, '/');
                        }
                        $rowReload = repairly_load_usuario($conn, (int)$_SESSION['repairly_uid']);
                        if ($rowReload && repairly_role_can_panel($rowReload['rol'])) {
                            repairly_redirect($target);
                        } else {
                            repairly_redirect('index.php');
                        }
                    }
                }
            } else {
                $error = 'No se pudo consultar el usuario.';
            }
        }
    } elseif ($action === 'register') {
        $user = trim((string)($_POST['reg_username'] ?? ''));
        $pass = (string)($_POST['reg_password'] ?? '');
        $pass2 = (string)($_POST['reg_password2'] ?? '');
        if ($usuario_table === '') {
            $error = 'No existe la tabla Usuario. Crea la tabla en MySQL según el esquema del proyecto.';
        } elseif (strlen($user) < 3) {
            $error = 'El usuario debe tener al menos 3 caracteres.';
        } elseif (strlen($pass) < 6) {
            $error = 'La contraseña debe tener al menos 6 caracteres.';
        } elseif ($pass !== $pass2) {
            $error = 'Las contraseñas no coinciden.';
        } else {
            $cols = table_columns($conn, $usuario_table);
            $userCol = isset($cols['username']) ? 'username' : 'USERNAME';
            $chk = $conn->prepare("SELECT COUNT(*) FROM `{$usuario_table}` WHERE `{$userCol}` = ?");
            $exists = 0;
            if ($chk) {
                $chk->bind_param('s', $user);
                $chk->execute();
                $chk->bind_result($exists);
                $chk->fetch();
                $chk->close();
            }
            if ((int)$exists > 0) {
                $error = 'Ese nombre de usuario ya está registrado.';
            }
            if ($error === '') {
                $hash = password_hash($pass, PASSWORD_DEFAULT);
                $count = (int)db_scalar($conn, "SELECT COUNT(*) FROM `{$usuario_table}`");
                $rolLogical = $count === 0 ? 'administrador' : 'tecnico';

                $cUser = usuario_real_column($cols, 'username');
                $cPass = usuario_real_column($cols, 'password_hash');
                $cRol  = usuario_real_column($cols, 'rol');
                $cEst  = usuario_real_column($cols, 'estado');
                $cTec  = usuario_real_column($cols, 'id_tecnico');

                if ($cUser === null || $cPass === null || $cRol === null) {
                    $error = 'La tabla Usuario debe tener las columnas username, password_hash y rol.';
                } else {
                    $rol = repairly_resolve_rol_for_insert($conn, $usuario_table, $cRol, $rolLogical);
                    /** @var string|int $estadoVal */
                    $estadoVal  = 'activo';
                    $estadoBind = 's';
                    if ($cEst !== null) {
                        [$estadoVal, $estadoBind] = repairly_resolve_estado_for_insert($conn, $usuario_table, $cEst, 'activo');
                    }
                    if ($cEst !== null && $cTec !== null) {
                        $sql = "INSERT INTO `{$usuario_table}` (`{$cUser}`,`{$cPass}`,`{$cRol}`,`{$cEst}`,`{$cTec}`) VALUES (?,?,?,?,NULL)";
                    } elseif ($cEst !== null) {
                        $sql = "INSERT INTO `{$usuario_table}` (`{$cUser}`,`{$cPass}`,`{$cRol}`,`{$cEst}`) VALUES (?,?,?,?)";
                    } elseif ($cTec !== null) {
                        $sql = "INSERT INTO `{$usuario_table}` (`{$cUser}`,`{$cPass}`,`{$cRol}`,`{$cTec}`) VALUES (?,?,?,NULL)";
                    } else {
                        $sql = "INSERT INTO `{$usuario_table}` (`{$cUser}`,`{$cPass}`,`{$cRol}`) VALUES (?,?,?)";
                    }

                    $stmt = $conn->prepare($sql);
                    $ok   = false;
                    $newId = 0;
                    if ($stmt) {
                        if ($cEst !== null && $cTec !== null) {
                            $stmt->bind_param('sss' . $estadoBind, $user, $hash, $rol, $estadoVal);
                        } elseif ($cEst !== null) {
                            $stmt->bind_param('sss' . $estadoBind, $user, $hash, $rol, $estadoVal);
                        } elseif ($cTec !== null) {
                            $stmt->bind_param('sss', $user, $hash, $rol);
                        } else {
                            $stmt->bind_param('sss', $user, $hash, $rol);
                        }
                        $ok    = $stmt->execute();
                        $newId = (int)$conn->insert_id;
                        if (!$ok) {
                            $error = 'No se pudo registrar: ' . ($stmt->error ?: $conn->error);
                        }
                        $stmt->close();
                    } else {
                        $error = 'Error al preparar registro: ' . $conn->error;
                    }

                    if ($error === '' && $ok && $newId > 0) {
                        $_SESSION['repairly_uid'] = $newId;
                        session_regenerate_id(true);
                        repairly_redirect('index.php');
                    }
                    if ($error === '' && (!$ok || $newId <= 0)) {
                        $error = 'No se pudo registrar (sin ID). Revisa AUTO_INCREMENT en id_usuario.';
                    }
                }
            }
        }
    }
}

$logoPath = 'assets/logo_sidebar.png';
$logoFs   = __DIR__ . '/' . $logoPath;
$hasLogo  = is_file($logoFs);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>RepairlyRD — Acceso</title>
<link rel="shortcut icon" href="logo.ico" type="image/x-icon">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&family=Rubik:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.19.0/dist/tabler-icons.min.css">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

:root {
  --navy:       #163f6e;
  --navy-dk:    #0f3057;
  --navy-md:    #1e5a96;
  --accent:     #2b7abc;
  --accent-lt:  #3489d4;
  --white:      #ffffff;
  --off-white:  #f5f7fb;
  --border:     #d4dff0;
  --text-dark:  #163f6e;
  --text-mid:   #3d4f72;
  --text-light: #7e8fb5;
  --input-bg:   #ffffff;
  --danger:     #c0392b;
  --success:    #1a6b3c;
  --font-head:  'Nunito', sans-serif;
  --font-body:  'Rubik', sans-serif;
}

html, body {
  min-height: 100vh;
  font-family: var(--font-body);
  background: var(--off-white);
  overflow-x: hidden;
}

/* ─── Page layout: left brand | right form ───────────────── */
.page {
  min-height: 100vh;
  display: grid;
  grid-template-columns: 400px 1fr;
}

/* ─── LEFT: Navy brand panel ──────────────────────────────── */
.panel-brand {
  background: #163f6e;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  padding: 48px 40px;
  position: relative;
  overflow: hidden;
}

/* Subtle dot-grid texture */
.panel-brand::before {
  content: '';
  position: absolute;
  inset: 0;
  background-image: radial-gradient(rgba(255,255,255,0.07) 1px, transparent 1px);
  background-size: 22px 22px;
  pointer-events: none;
}

/* Red top accent bar */
.panel-brand::after {
  content: '';
  position: absolute;
  top: 0; left: 0; right: 0;
  height: 5px;
  background: var(--accent);
}

.brand-top {
  position: relative;
  z-index: 1;
}

/* Logo */
.logo-wrap {
  width: 100px; height: 100px;
  border-radius: 50%;
  background: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 24px;
  box-shadow: 0 4px 20px rgba(0,0,0,0.22);
  overflow: hidden;
  flex-shrink: 0;
}
.login-logo {
  width: 100px; height: 100px;
  object-fit: cover;
  display: block;
}
.logo-fallback {
  font-size: 40px;
  color: var(--navy);
}

.brand-name {
  font-family: var(--font-head);
  font-size: 28px;
  font-weight: 800;
  color: #ffffff;
  line-height: 1.1;
  margin-bottom: 6px;
  letter-spacing: -0.3px;
}
.brand-tagline {
  font-size: 12px;
  color: rgba(255,255,255,0.45);
  letter-spacing: 0.1em;
  text-transform: uppercase;
  font-weight: 400;
  margin-bottom: 40px;
}

/* Divider */
.brand-divider {
  width: 36px;
  height: 3px;
  background: var(--accent);
  border-radius: 2px;
  margin-bottom: 36px;
}

/* Feature list */
.brand-features {
  list-style: none;
  display: flex;
  flex-direction: column;
  gap: 18px;
}
.brand-features li {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  font-size: 13px;
  color: rgba(255,255,255,0.62);
  line-height: 1.45;
}
.feat-dot {
  width: 7px; height: 7px;
  border-radius: 50%;
  background: var(--accent);
  flex-shrink: 0;
  margin-top: 5px;
}

/* Brand bottom */
.brand-bottom {
  position: relative;
  z-index: 1;
  font-size: 11px;
  color: rgba(255,255,255,0.2);
}

/* ─── RIGHT: Form panel ───────────────────────────────────── */
.panel-form {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 48px 32px;
  background: var(--off-white);
}

.form-card {
  width: 100%;
  max-width: 390px;
  animation: fade-up 0.5s cubic-bezier(0.22,1,0.36,1) both;
}
@keyframes fade-up {
  from { opacity: 0; transform: translateY(18px); }
  to   { opacity: 1; transform: translateY(0); }
}

.form-heading {
  font-family: var(--font-head);
  font-size: 24px;
  font-weight: 800;
  color: var(--text-dark);
  margin-bottom: 4px;
  letter-spacing: -0.2px;
}
.form-subhead {
  font-size: 13px;
  color: var(--text-light);
  margin-bottom: 26px;
  font-weight: 400;
}

/* ─── Tabs ────────────────────────────────────────────────── */
.tab-row {
  display: flex;
  border-bottom: 2px solid var(--border);
  margin-bottom: 24px;
}
.tab-btn {
  flex: 1;
  padding: 10px 0 11px;
  border: none;
  background: none;
  font-family: var(--font-body);
  font-size: 13px;
  font-weight: 500;
  color: var(--text-light);
  cursor: pointer;
  position: relative;
  transition: color 0.2s;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
}
.tab-btn i { font-size: 15px; }
.tab-btn::after {
  content: '';
  position: absolute;
  bottom: -2px; left: 0; right: 0;
  height: 2px;
  background: #163f6e;
  transform: scaleX(0);
  transition: transform 0.25s ease;
  border-radius: 2px;
}
.tab-btn.active { color: var(--navy); font-weight: 600; }
.tab-btn.active::after { transform: scaleX(1); }
.tab-btn:not(.active):hover { color: var(--text-mid); }

/* ─── Panels ──────────────────────────────────────────────── */
.panel { display: none; }
.panel.active {
  display: block;
  animation: panel-in 0.28s ease both;
}
@keyframes panel-in {
  from { opacity: 0; transform: translateY(6px); }
  to   { opacity: 1; transform: translateY(0); }
}

/* ─── Fields ──────────────────────────────────────────────── */
.field {
  position: relative;
  margin-bottom: 15px;
}
.field-label {
  display: block;
  font-size: 11.5px;
  font-weight: 600;
  color: var(--text-mid);
  margin-bottom: 6px;
  letter-spacing: 0.01em;
}
.field input {
  width: 100%;
  padding: 12px 14px 12px 42px;
  background: var(--input-bg);
  border: 1.5px solid var(--border);
  border-radius: 9px;
  color: #111111;
  font-family: var(--font-body);
  font-size: 14px;
  font-weight: 400;
  outline: none;
  transition: border-color 0.2s, box-shadow 0.2s;
  -webkit-text-fill-color: #111111;
  box-shadow: 0 1px 3px rgba(26,42,94,0.05);
}
.field input::placeholder { color: #c2c8dc; }
.field input:focus {
  border-color: var(--navy);
  box-shadow: 0 0 0 3px rgba(26,42,94,0.1);
}
.field-icon {
  position: absolute;
  left: 13px;
  bottom: 13px;
  color: #c2c8dc;
  font-size: 16px;
  pointer-events: none;
  transition: color 0.2s;
}
.field:focus-within .field-icon { color: var(--navy); }

.eye-btn {
  position: absolute;
  right: 12px;
  bottom: 13px;
  background: none;
  border: none;
  cursor: pointer;
  color: #c2c8dc;
  font-size: 16px;
  line-height: 1;
  padding: 0;
  transition: color 0.2s;
}
.eye-btn:hover { color: var(--navy); }
.field input.has-eye { padding-right: 40px; }

/* ─── Submit button ───────────────────────────────────────── */
.btn-submit {
  width: 100%;
  padding: 13px;
  margin-top: 6px;
  border: none;
  border-radius: 9px;
  background: #163f6e;
  color: #fff;
  font-family: var(--font-head);
  font-size: 14px;
  font-weight: 700;
  letter-spacing: 0.02em;
  cursor: pointer;
  transition: background 0.2s, transform 0.15s, box-shadow 0.2s;
  box-shadow: 0 4px 14px rgba(26,42,94,0.25);
  position: relative;
  overflow: hidden;
}
/* Red left accent on button */
.btn-submit::before {
  content: '';
  position: absolute;
  left: 0; top: 0; bottom: 0;
  width: 4px;
  background: var(--accent);
}
.btn-submit:hover {
  background: var(--navy-md);
  transform: translateY(-1px);
  box-shadow: 0 7px 20px rgba(26,42,94,0.32);
}
.btn-submit:active { transform: translateY(0); }

/* ─── Messages ────────────────────────────────────────────── */
.msg {
  display: flex;
  align-items: flex-start;
  gap: 9px;
  font-size: 13px;
  margin-bottom: 18px;
  padding: 11px 13px;
  border-radius: 8px;
  line-height: 1.45;
  animation: msg-in 0.28s ease both;
  font-family: var(--font-body);
}
@keyframes msg-in {
  from { opacity: 0; transform: translateY(-5px); }
  to   { opacity: 1; transform: translateY(0); }
}
.msg i { font-size: 16px; flex-shrink: 0; margin-top: 1px; }
.msg-err {
  background: #fef2f2;
  border: 1px solid #fecaca;
  color: #7f1d1d;
}
.msg-err i { color: var(--accent); }
.msg-ok {
  background: #f0fdf4;
  border: 1px solid #bbf7d0;
  color: var(--success);
}
.msg-ok i { color: var(--success); }

/* ─── Footer note ─────────────────────────────────────────── */
.footer-note {
  margin-top: 20px;
  font-size: 11.5px;
  color: var(--text-light);
  line-height: 1.55;
  padding-top: 16px;
  border-top: 1px solid var(--border);
  display: flex;
  align-items: flex-start;
  gap: 7px;
  font-family: var(--font-body);
}
.footer-note i { font-size: 14px; flex-shrink: 0; margin-top: 1px; color: var(--border); }

/* ─── Responsive ──────────────────────────────────────────── */
@media (max-width: 740px) {
  .page { grid-template-columns: 1fr; }
  .panel-brand { display: none; }
  .panel-form { padding: 36px 20px; }
}

/* ─── Loading screen ──────────────────────────────────────── */
#rl-loader {
  position: fixed;
  inset: 0;
  z-index: 9999;
  background: #163f6e;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 28px;
  opacity: 0;
  pointer-events: none;
  transition: opacity 0.25s ease;
}
#rl-loader.visible {
  opacity: 1;
  pointer-events: all;
}
.loader-logo-wrap {
  width: 90px; height: 90px;
  border-radius: 50%;
  background: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 8px 32px rgba(0,0,0,0.28);
  overflow: hidden;
  animation: loader-pulse 1.8s ease-in-out infinite;
}
@keyframes loader-pulse {
  0%, 100% { transform: scale(1);   box-shadow: 0 8px 32px rgba(0,0,0,0.28); }
  50%       { transform: scale(1.05); box-shadow: 0 14px 40px rgba(0,0,0,0.38); }
}
.loader-logo-wrap img { width: 90px; height: 90px; object-fit: cover; }
.loader-logo-fallback { font-size: 38px; color: var(--navy); }
.loader-brand {
  font-family: var(--font-head);
  font-size: 22px;
  font-weight: 800;
  color: #fff;
  letter-spacing: -0.2px;
}
.loader-msg {
  font-family: var(--font-body);
  font-size: 13px;
  color: rgba(255,255,255,0.5);
  font-weight: 400;
  letter-spacing: 0.02em;
}
/* Progress bar */
.loader-bar-track {
  width: 180px;
  height: 3px;
  background: rgba(255,255,255,0.12);
  border-radius: 2px;
  overflow: hidden;
  margin-top: -10px;
}
.loader-bar-fill {
  height: 100%;
  background: linear-gradient(90deg, rgba(43,122,188,0.5), #fff);
  border-radius: 2px;
  width: 0%;
  animation: bar-grow 1.4s cubic-bezier(0.4,0,0.2,1) forwards;
}
@keyframes bar-grow {
  0%   { width: 0%; }
  60%  { width: 70%; }
  85%  { width: 88%; }
  100% { width: 100%; }
}

</style>
</head>
<body>

<div class="page">

  <!-- LEFT: Brand -->
  <aside class="panel-brand">
    <div class="brand-top">
      <div class="logo-wrap">
        <?php if ($hasLogo): ?>
          <img src="<?= h($logoPath) ?>" alt="RepairlyRD" class="login-logo" width="100" height="100">
        <?php else: ?>
          <span class="logo-fallback"><i class="ti ti-cpu"></i></span>
        <?php endif; ?>
      </div>

      <div class="brand-name">RepairlyRD</div>
      <div class="brand-tagline">Sistema de gestión · Taller</div>
      <div class="brand-divider"></div>

      <ul class="brand-features">
        <li><div class="feat-dot"></div><span>Control de órdenes y reparaciones electrónicas</span></li>
        <li><div class="feat-dot"></div><span>Gestión de clientes, técnicos y roles</span></li>
        <li><div class="feat-dot"></div><span>Reportes y seguimiento de órdenes</span></li>
        <li><div class="feat-dot"></div><span>Acceso seguro con control de permisos</span></li>
      </ul>
    </div>

    <div class="brand-bottom">© RepairlyRD &mdash; Sistema empresarial</div>
  </aside>

  <!-- RIGHT: Form -->
  <main class="panel-form">
    <div class="form-card">

      <div class="form-heading">Bienvenido de vuelta</div>
      <div class="form-subhead">Ingresa tus credenciales para acceder al sistema</div>

      <?php if ($error !== ''): ?>
        <div class="msg msg-err">
          <i class="ti ti-alert-circle"></i>
          <span><?= h($error) ?></span>
        </div>
      <?php endif; ?>
      <?php if ($info !== ''): ?>
        <div class="msg msg-ok">
          <i class="ti ti-circle-check"></i>
          <span><?= h($info) ?></span>
        </div>
      <?php endif; ?>

      <!-- Tabs -->
      <div class="tab-row">
        <button type="button" class="tab-btn active" data-tab="login">
          <i class="ti ti-login"></i> Iniciar sesión
        </button>
        <button type="button" class="tab-btn" data-tab="reg">
          <i class="ti ti-user-plus"></i> Registro
        </button>
      </div>

      <!-- Login -->
      <div id="panel-login" class="panel active">
        <form method="post" autocomplete="off">
          <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>">
          <input type="hidden" name="auth_action" value="login">
          <input type="hidden" name="next" value="<?= h($_GET['next'] ?? 'index.php') ?>">

          <div class="field">
            <label class="field-label" for="username">Usuario</label>
            <input type="text" id="username" name="username" placeholder="Tu nombre de usuario" required value="">
            <i class="ti ti-user field-icon"></i>
          </div>

          <div class="field">
            <label class="field-label" for="password">Contraseña</label>
            <input type="password" id="password" name="password" placeholder="••••••••" class="has-eye" required>
            <i class="ti ti-lock field-icon"></i>
            <button type="button" class="eye-btn" data-target="password" aria-label="Mostrar contraseña">
              <i class="ti ti-eye"></i>
            </button>
          </div>

          <button type="submit" class="btn-submit">Entrar al sistema</button>
        </form>
      </div>

      <!-- Register -->
      <div id="panel-reg" class="panel">
        <form method="post" autocomplete="off">
          <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>">
          <input type="hidden" name="auth_action" value="register">

          <div class="field">
            <label class="field-label" for="reg_username">Usuario</label>
            <input type="text" id="reg_username" name="reg_username" placeholder="Mínimo 3 caracteres" required minlength="3">
            <i class="ti ti-user field-icon"></i>
          </div>

          <div class="field">
            <label class="field-label" for="reg_password">Contraseña</label>
            <input type="password" id="reg_password" name="reg_password" placeholder="Mínimo 6 caracteres" class="has-eye" required minlength="6">
            <i class="ti ti-lock field-icon"></i>
            <button type="button" class="eye-btn" data-target="reg_password" aria-label="Mostrar contraseña">
              <i class="ti ti-eye"></i>
            </button>
          </div>

          <div class="field">
            <label class="field-label" for="reg_password2">Confirmar contraseña</label>
            <input type="password" id="reg_password2" name="reg_password2" placeholder="Repite la contraseña" class="has-eye" required minlength="6">
            <i class="ti ti-lock-check field-icon"></i>
            <button type="button" class="eye-btn" data-target="reg_password2" aria-label="Mostrar contraseña">
              <i class="ti ti-eye"></i>
            </button>
          </div>

          <button type="submit" class="btn-submit">Crear cuenta</button>
        </form>
      </div>

      <p class="footer-note">
        <i class="ti ti-info-circle"></i>
        La solución a tu problema, está aqui.
      </p>

    </div>
  </main>

</div>


<!-- Loading screen -->
<div id="rl-loader" role="status" aria-live="polite" aria-label="Cargando sistema...">
  <div class="loader-logo-wrap">
    <?php if ($hasLogo): ?>
      <img src="<?= h($logoPath) ?>" alt="RepairlyRD">
    <?php else: ?>
      <span class="loader-logo-fallback"><i class="ti ti-cpu"></i></span>
    <?php endif; ?>
  </div>
  <div class="loader-brand">RepairlyRD</div>
  <div class="loader-bar-track"><div class="loader-bar-fill" id="rl-bar"></div></div>
  <div class="loader-msg" id="rl-loader-msg">Verificando credenciales…</div>
</div>

<script>
(function () {
  /* Show loading screen on login submit */
  var loginForm = document.querySelector('#panel-login form');
  if (loginForm) {
    loginForm.addEventListener('submit', function () {
      var loader = document.getElementById('rl-loader');
      var msg    = document.getElementById('rl-loader-msg');
      var bar    = document.getElementById('rl-bar');
      if (!loader) return;
      loader.classList.add('visible');
      // Cycle messages
      var msgs = ['Verificando credenciales…', 'Iniciando sesión…', 'Cargando tu espacio de trabajo…'];
      var i = 0;
      var interval = setInterval(function () {
        i++;
        if (i < msgs.length) { msg.textContent = msgs[i]; }
        else { clearInterval(interval); }
      }, 600);
    });
  }

  /* Tabs */
  document.querySelectorAll('.tab-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var tab = btn.getAttribute('data-tab');
      document.querySelectorAll('.tab-btn').forEach(function (b) { b.classList.remove('active'); });
      document.querySelectorAll('.panel').forEach(function (p) { p.classList.remove('active'); });
      btn.classList.add('active');
      document.getElementById(tab === 'reg' ? 'panel-reg' : 'panel-login').classList.add('active');
    });
  });
  /* Eye toggle */
  document.querySelectorAll('.eye-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var input = document.getElementById(btn.getAttribute('data-target'));
      var icon  = btn.querySelector('i');
      if (!input) return;
      if (input.type === 'password') { input.type = 'text';     icon.className = 'ti ti-eye-off'; }
      else                           { input.type = 'password'; icon.className = 'ti ti-eye'; }
    });
  });
})();
</script>
</body>
</html>
