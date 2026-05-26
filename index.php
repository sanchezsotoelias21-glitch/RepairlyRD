<?php
ob_start();

// En Railway/producción: no imprimir errores en la respuesta (evita "headers already sent").
// Para depurar: variable PHP_DISPLAY_ERRORS=1 en el servicio.
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

function h(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
}

function require_csrf(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }

    if (session_status() !== PHP_SESSION_ACTIVE) {
        repairly_session_start();
    }

    $sessionToken = $_SESSION['csrf'] ?? '';
    $formToken = $_POST['csrf'] ?? '';

    if (
        !is_string($sessionToken) ||
        !is_string($formToken) ||
        $sessionToken === '' ||
        $formToken === '' ||
        !hash_equals($sessionToken, $formToken)
    ) {
        http_response_code(400);
        error_log('CSRF FAIL | SESSION=' . session_id());
        die('Solicitud inválida (CSRF).');
    }
}

require_once __DIR__ . '/includes/sql_helpers.php';
require_once __DIR__ . '/includes/auth.php';

if (isset($_GET['logout'])) {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
    repairly_redirect('login.php');
}

$repairly_uid = isset($_SESSION['repairly_uid']) ? (int)$_SESSION['repairly_uid'] : 0;
if ($repairly_uid <= 0) {
    $qs = isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] !== '' ? '?' . $_SERVER['QUERY_STRING'] : '';
    repairly_redirect('login.php?next=' . urlencode('index.php' . $qs));
}

$usuario_row = repairly_load_usuario($conn, $repairly_uid);
if (!$usuario_row || !repairly_usuario_esta_activo($usuario_row['estado'])) {
    $_SESSION = [];
    session_destroy();
    repairly_redirect('login.php?msg=' . urlencode('Sesión inválida o cuenta inactiva.'));
}

if (!repairly_role_can_panel($usuario_row['rol'])) {
    repairly_redirect('portal.php');
}

$usuario = [
    'nombre'    => $usuario_row['username'],
    'iniciales' => repairly_iniciales($usuario_row['username']),
    'email'     => '',
    'rol'       => ucfirst(repairly_normalize_role($usuario_row['rol']) ?: 'usuario'),
];
$auth_is_admin = repairly_is_admin($usuario_row['rol']);

// ============================================================
//  RepairlyRD — Dashboard Principal
//  Paleta oficial según guía de identidad visual v1.0
// ============================================================

$current_page = $_GET['page'] ?? 'dashboard';
$allowed_pages = [
    'dashboard',
    'clientes',
    'equipos',
    'ordenes',
    'diagnosticos',
    'inventario',
    'tecnicos',
    'garantias',
    'notificaciones',
    'whatsapp',
    'reportes',
    'usuarios',
    'configuracion',
    'deliverys',
];
if (!in_array($current_page, $allowed_pages, true)) {
    $current_page = 'dashboard';
}

$page_meta = [
    'dashboard' => ['label' => 'Dashboard', 'desc' => 'Mostrar resumen general del sistema', 'icono' => 'ti-layout-dashboard'],
    'clientes' => ['label' => 'Clientes', 'desc' => 'Gestión de clientes registrados', 'icono' => 'ti-users'],
    'equipos' => ['label' => 'Equipos', 'desc' => 'Registro y administración de equipos', 'icono' => 'ti-device-laptop'],
    'ordenes' => ['label' => 'Órdenes de reparación', 'desc' => 'Crear y gestionar órdenes', 'icono' => 'ti-clipboard-list'],
    'diagnosticos' => ['label' => 'Diagnósticos', 'desc' => 'Registrar diagnósticos técnicos', 'icono' => 'ti-stethoscope'],
    'inventario' => ['label' => 'Inventario / Piezas', 'desc' => 'Gestión de piezas y stock', 'icono' => 'ti-package'],
    'tecnicos' => ['label' => 'Técnicos', 'desc' => 'Administración de técnicos', 'icono' => 'ti-user-check'],
    'garantias' => ['label' => 'Garantías', 'desc' => 'Ver y controlar garantías activas', 'icono' => 'ti-shield-check'],
    'notificaciones' => ['label' => 'Notificaciones', 'desc' => 'Historial de WhatsApp y correos enviados', 'icono' => 'ti-bell'],
    'whatsapp' => ['label' => 'WhatsApp', 'desc' => 'Historial de WhatsApp y correos enviados', 'icono' => 'ti-brand-whatsapp'],
    'reportes' => ['label' => 'Reportes', 'desc' => 'Ingresos, productividad y estadísticas', 'icono' => 'ti-chart-bar'],
    'usuarios' => ['label' => 'Usuarios', 'desc' => 'Gestión de accesos y roles', 'icono' => 'ti-user-shield'],
    'configuracion' => ['label' => 'Configuración', 'desc' => 'Ajustes generales del sistema', 'icono' => 'ti-settings'],
    'deliverys' => ['label' => 'Deliveries', 'desc' => 'Gestión de deliveries y tracking', 'icono' => 'ti-truck-delivery'],
];
$page = $page_meta[$current_page] ?? $page_meta['dashboard'];

// Carga solo el CRUD necesario para el apartado actual (reduce tiempo de carga).
$crud_by_page = [
    'clientes' => 'crud_clientes.php',
    'equipos' => 'crud_equipos.php',
    'ordenes' => 'crud_ordenes.php',
    'tecnicos' => 'crud_tecnicos.php',
    'configuracion' => 'crud_configuracion.php',
    'usuarios' => 'crud_usuarios.php',
    'inventario' => 'crud_piezas.php',
    'piezas' => 'crud_piezas.php',
    'diagnosticos' => 'crud_diagnosticos.php',
    'garantias' => 'crud_garantias.php',
    'notificaciones' => 'crud_notificaciones.php',
];
if (isset($crud_by_page[$current_page])) {
    require_once __DIR__ . '/includes/' . $crud_by_page[$current_page];
}

function status_palette(string $status): array {
    $name = strtolower($status);
    if (strpos($name, 'falla') !== false || strpos($name, 'error') !== false || strpos($name, 'problema') !== false) {
        return ['bg' => '#FFEBEE', 'color' => '#FF4444', 'border' => '#FF4444', 'dot' => '#FF4444'];
    }
    if (strpos($name, 'garant') !== false) {
        return ['bg' => '#F3E5F5', 'color' => '#7B4EC4', 'border' => '#7B4EC4', 'dot' => '#7B4EC4'];
    }
    if (strpos($name, 'listo') !== false || strpos($name, 'entreg') !== false || strpos($name, 'complet') !== false || strpos($name, 'finaliz') !== false) {
        return ['bg' => '#E8F5E9', 'color' => '#00AA44', 'border' => '#00AA44', 'dot' => '#00AA44'];
    }
    if (strpos($name, 'pend') !== false || strpos($name, 'recib') !== false || strpos($name, 'esper') !== false) {
        return ['bg' => '#FFF3E0', 'color' => '#FF9500', 'border' => '#FF9500', 'dot' => '#FF9500'];
    }
    if (strpos($name, 'diagn') !== false || strpos($name, 'evalu') !== false) {
        return ['bg' => '#F5F5F5', 'color' => '#424242', 'border' => '#424242', 'dot' => '#424242'];
    }
    if (strpos($name, 'proceso') !== false || strpos($name, 'repar') !== false || strpos($name, 'trabaj') !== false) {
        return ['bg' => '#E3F2FD', 'color' => '#2b7abc', 'border' => '#2b7abc', 'dot' => '#2b7abc'];
    }
    if (strpos($name, 'cancel') !== false) {
        return ['bg' => '#FFEBEE', 'color' => '#DC2626', 'border' => '#DC2626', 'dot' => '#DC2626'];
    }
    return ['bg' => '#E3F2FD', 'color' => '#2b7abc', 'border' => '#2b7abc', 'dot' => '#2b7abc'];
}

function device_icon(string $type): string {
    $name = strtolower($type);
    if (strpos($name, 'phone') || strpos($name, 'tel')) {
        return 'ti-device-mobile';
    }
    if (strpos($name, 'tablet') || strpos($name, 'ipad')) {
        return 'ti-device-tablet';
    }
    if (strpos($name, 'pc') || strpos($name, 'torre') || strpos($name, 'desktop')) {
        return 'ti-device-desktop';
    }
    return 'ti-device-laptop';
}

$flash = [
    'type' => $_GET['t'] ?? null,
    'msg' => $_GET['m'] ?? null,
];
if (!is_string($flash['type'])) { $flash['type'] = null; }
if (!is_string($flash['msg'])) { $flash['msg'] = null; }

// ---------------------------
// Clientes (CRUD)
// ---------------------------
$cliente_table = 'Cliente';
$cliente_cols = $cliente_table ? table_columns($conn, $cliente_table) : [];
$clientes_action = $_GET['action'] ?? '';
$clientes_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$clientes_search = isset($_GET['q']) && is_string($_GET['q']) ? trim($_GET['q']) : '';

if ($current_page === 'clientes' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $post_action = $_POST['clientes_action'] ?? '';
    if (!is_string($post_action)) {
        $post_action = '';
    }

    if (!$cliente_table) {
        header('Location: ?page=clientes&t=err&m=No+se+encontr%C3%B3+la+tabla+de+clientes');
        exit;
    }

    $id_field = isset($cliente_cols['id_cliente']) ? 'id_cliente' : (isset($cliente_cols['ID_CLIENTE']) ? 'ID_CLIENTE' : 'id_cliente');
    $nombre = isset($_POST['nombre']) && is_string($_POST['nombre']) ? trim($_POST['nombre']) : '';
    $telefono = isset($_POST['telefono']) && is_string($_POST['telefono']) ? trim($_POST['telefono']) : '';
    $email = isset($_POST['email']) && is_string($_POST['email']) ? trim($_POST['email']) : '';
    $direccion = isset($_POST['direccion']) && is_string($_POST['direccion']) ? trim($_POST['direccion']) : '';

    if ($post_action === 'create') {
        if ($nombre === '') {
            header('Location: ?page=clientes&action=new&t=err&m=El+nombre+es+obligatorio');
            exit;
        }

        $fields = [];
        $placeholders = [];
        $types = '';
        $values = [];

        if (isset($cliente_cols['nombre'])) { $fields[] = 'nombre'; $placeholders[] = '?'; $types .= 's'; $values[] = $nombre; }
        if (isset($cliente_cols['telefono'])) { $fields[] = 'telefono'; $placeholders[] = '?'; $types .= 's'; $values[] = $telefono; }
        if (isset($cliente_cols['email'])) { $fields[] = 'email'; $placeholders[] = '?'; $types .= 's'; $values[] = $email; }
        if (isset($cliente_cols['direccion'])) { $fields[] = 'direccion'; $placeholders[] = '?'; $types .= 's'; $values[] = $direccion; }
        if (isset($cliente_cols['fecha_registro'])) { $fields[] = 'fecha_registro'; $placeholders[] = 'CURDATE()'; }

        $sql = "INSERT INTO `{$cliente_table}` (" . implode(',', array_map(fn($f) => "`{$f}`", $fields)) . ") VALUES (" . implode(',', $placeholders) . ")";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            header('Location: ?page=clientes&t=err&m=No+se+pudo+crear+el+cliente');
            exit;
        }
        if ($types !== '') {
            $stmt->bind_param($types, ...$values);
        }
        $ok = $stmt->execute();
        $stmt->close();
        header('Location: ?page=clientes&t=' . ($ok ? 'ok' : 'err') . '&m=' . ($ok ? 'Cliente+creado' : 'Error+al+crear'));
        exit;
    }

    if ($post_action === 'update') {
        $id = isset($_POST['id_cliente']) ? (int)$_POST['id_cliente'] : 0;
        if ($id <= 0) {
            header('Location: ?page=clientes&t=err&m=ID+inv%C3%A1lido');
            exit;
        }
        if ($nombre === '') {
            header('Location: ?page=clientes&action=edit&id=' . $id . '&t=err&m=El+nombre+es+obligatorio');
            exit;
        }

        $sets = [];
        $types = '';
        $values = [];

        if (isset($cliente_cols['nombre'])) { $sets[] = "`nombre`=?"; $types .= 's'; $values[] = $nombre; }
        if (isset($cliente_cols['telefono'])) { $sets[] = "`telefono`=?"; $types .= 's'; $values[] = $telefono; }
        if (isset($cliente_cols['email'])) { $sets[] = "`email`=?"; $types .= 's'; $values[] = $email; }
        if (isset($cliente_cols['direccion'])) { $sets[] = "`direccion`=?"; $types .= 's'; $values[] = $direccion; }

        $sql = "UPDATE `{$cliente_table}` SET " . implode(',', $sets) . " WHERE `{$id_field}`=? LIMIT 1";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            header('Location: ?page=clientes&t=err&m=No+se+pudo+actualizar');
            exit;
        }
        $types2 = $types . 'i';
        $values[] = $id;
        $stmt->bind_param($types2, ...$values);
        $ok = $stmt->execute();
        $stmt->close();
        header('Location: ?page=clientes&t=' . ($ok ? 'ok' : 'err') . '&m=' . ($ok ? 'Cliente+actualizado' : 'Error+al+actualizar'));
        exit;
    }

    if ($post_action === 'delete') {
        $id = isset($_POST['id_cliente']) ? (int)$_POST['id_cliente'] : 0;
        if ($id <= 0) {
            header('Location: ?page=clientes&t=err&m=ID+inv%C3%A1lido');
            exit;
        }
        $sql = "DELETE FROM `{$cliente_table}` WHERE `{$id_field}`=? LIMIT 1";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            header('Location: ?page=clientes&t=err&m=No+se+pudo+eliminar');
            exit;
        }
        $stmt->bind_param('i', $id);
        $ok = $stmt->execute();
        $stmt->close();
        header('Location: ?page=clientes&t=' . ($ok ? 'ok' : 'err') . '&m=' . ($ok ? 'Cliente+eliminado' : 'Error+al+eliminar'));
        exit;
    }

    if ($post_action === 'merge') {
        $id_principal = isset($_POST['id_principal']) ? (int)$_POST['id_principal'] : 0;
        $id_secundario = isset($_POST['id_secundario']) ? (int)$_POST['id_secundario'] : 0;
        
        if ($id_principal <= 0 || $id_secundario <= 0 || $id_principal === $id_secundario) {
            header('Location: ?page=clientes&t=err&m=IDs+inv%C3%A1lidos');
            exit;
        }

        // Iniciar transacción
        $conn->begin_transaction();

        try {
            // 1. Reasignar todos los equipos del cliente secundario al principal
            $sql_equipos = "UPDATE Equipo SET id_cliente = ? WHERE id_cliente = ?";
            $stmt_eq = $conn->prepare($sql_equipos);
            if (!$stmt_eq) throw new Exception('Error al preparar SQL de equipos');
            $stmt_eq->bind_param('ii', $id_principal, $id_secundario);
            $stmt_eq->execute();
            $stmt_eq->close();

            // 2. Eliminar el cliente secundario
            $sql_del = "DELETE FROM `{$cliente_table}` WHERE `{$id_field}` = ?";
            $stmt_del = $conn->prepare($sql_del);
            if (!$stmt_del) throw new Exception('Error al preparar SQL de eliminación');
            $stmt_del->bind_param('i', $id_secundario);
            $stmt_del->execute();
            $stmt_del->close();

            // Confirmar transacción
            $conn->commit();
            header('Location: ?page=clientes&t=ok&m=Clientes+fusionados+correctamente');
            exit;
        } catch (Exception $e) {
            // Revertir en caso de error
            $conn->rollback();
            header('Location: ?page=clientes&t=err&m=Error+al+fusionar:+' . urlencode($e->getMessage()));
            exit;
        }
    }
}

$nav_items = [
    ['seccion' => true, 'label' => 'Principal'],
    ['key' => 'dashboard', 'label' => $page_meta['dashboard']['label'], 'desc' => $page_meta['dashboard']['desc'], 'href' => '?page=dashboard', 'icono' => $page_meta['dashboard']['icono'], 'activo' => $current_page === 'dashboard', 'badge' => null],

    ['seccion' => true, 'label' => 'Gestión'],
    ['key' => 'clientes', 'label' => $page_meta['clientes']['label'], 'desc' => $page_meta['clientes']['desc'], 'href' => '?page=clientes', 'icono' => $page_meta['clientes']['icono'], 'activo' => $current_page === 'clientes', 'badge' => null],
    ['key' => 'equipos', 'label' => $page_meta['equipos']['label'], 'desc' => $page_meta['equipos']['desc'], 'href' => '?page=equipos', 'icono' => $page_meta['equipos']['icono'], 'activo' => $current_page === 'equipos', 'badge' => null],
    ['key' => 'tecnicos', 'label' => $page_meta['tecnicos']['label'], 'desc' => $page_meta['tecnicos']['desc'], 'href' => '?page=tecnicos', 'icono' => $page_meta['tecnicos']['icono'], 'activo' => $current_page === 'tecnicos', 'badge' => null],
    ['key' => 'usuarios', 'label' => $page_meta['usuarios']['label'], 'desc' => $page_meta['usuarios']['desc'], 'href' => '?page=usuarios', 'icono' => $page_meta['usuarios']['icono'], 'activo' => $current_page === 'usuarios', 'badge' => null],

    ['seccion' => true, 'label' => 'Operaciones'],
    ['key' => 'ordenes', 'label' => $page_meta['ordenes']['label'], 'desc' => $page_meta['ordenes']['desc'], 'href' => '?page=ordenes', 'icono' => $page_meta['ordenes']['icono'], 'activo' => $current_page === 'ordenes', 'badge' => null],
    ['key' => 'deliverys', 'label' => $page_meta['deliverys']['label'], 'desc' => $page_meta['deliverys']['desc'], 'href' => '?page=deliverys', 'icono' => $page_meta['deliverys']['icono'], 'activo' => $current_page === 'deliverys', 'badge' => null],
    ['key' => 'diagnosticos', 'label' => $page_meta['diagnosticos']['label'], 'desc' => $page_meta['diagnosticos']['desc'], 'href' => '?page=diagnosticos', 'icono' => $page_meta['diagnosticos']['icono'], 'activo' => $current_page === 'diagnosticos', 'badge' => null],
    ['key' => 'inventario', 'label' => $page_meta['inventario']['label'], 'desc' => $page_meta['inventario']['desc'], 'href' => '?page=inventario', 'icono' => $page_meta['inventario']['icono'], 'activo' => $current_page === 'inventario', 'badge' => null],
    ['key' => 'garantias', 'label' => $page_meta['garantias']['label'], 'desc' => $page_meta['garantias']['desc'], 'href' => '?page=garantias', 'icono' => $page_meta['garantias']['icono'], 'activo' => $current_page === 'garantias', 'badge' => ['valor'=>0, 'bg'=>'#7B4EC4','color'=>'#fff']],

    ['seccion' => true, 'label' => 'Comunicación'],
    ['key' => 'notificaciones', 'label' => $page_meta['notificaciones']['label'], 'desc' => $page_meta['notificaciones']['desc'], 'href' => '?page=notificaciones', 'icono' => $page_meta['notificaciones']['icono'], 'activo' => $current_page === 'notificaciones', 'badge' => null],
    ['key' => 'whatsapp', 'label' => $page_meta['whatsapp']['label'], 'desc' => $page_meta['whatsapp']['desc'], 'href' => '?page=whatsapp', 'icono' => $page_meta['whatsapp']['icono'], 'activo' => $current_page === 'whatsapp', 'badge' => ['dot' => true, 'bg'=>'#25D366']],

    ['seccion' => true, 'label' => 'Analítica'],
    ['key' => 'reportes', 'label' => $page_meta['reportes']['label'], 'desc' => $page_meta['reportes']['desc'], 'href' => '?page=reportes', 'icono' => $page_meta['reportes']['icono'], 'activo' => $current_page === 'reportes', 'badge' => null],

    ['seccion' => true, 'label' => 'Sistema'],
    ['key' => 'configuracion', 'label' => $page_meta['configuracion']['label'], 'desc' => $page_meta['configuracion']['desc'], 'href' => '?page=configuracion', 'icono' => $page_meta['configuracion']['icono'], 'activo' => $current_page === 'configuracion', 'badge' => null],
];

$ordenes_recientes = [
    [
        'id'       => '1042',
        'cliente'  => 'Ramón Díaz',
        'equipo'   => 'iPhone 14 Pro — pantalla',
        'icono_eq' => 'ti-device-mobile',
        'tecnico'  => 'Juan',
        'estado'   => 'En proceso',
        'est_bg'   => '#E3F2FD',
        'est_color'=> '#2b7abc',
        'est_borde'=> '#2b7abc',
        'est_dot'  => '#2b7abc',
        'valor'    => '$850',
    ],
    [
        'id'       => '1041',
        'cliente'  => 'Laura Méndez',
        'equipo'   => 'MacBook Air — teclado',
        'icono_eq' => 'ti-device-laptop',
        'tecnico'  => 'María',
        'estado'   => 'Listo',
        'est_bg'   => '#E8F5E9',
        'est_color'=> '#00AA44',
        'est_borde'=> '#00AA44',
        'est_dot'  => '#00AA44',
        'valor'    => '$1,200',
    ],
    [
        'id'       => '1040',
        'cliente'  => 'Pedro Santos',
        'equipo'   => 'PC Torre — fuente de poder',
        'icono_eq' => 'ti-device-desktop',
        'tecnico'  => 'Carlos',
        'estado'   => 'Con falla',
        'est_bg'   => '#FFEBEE',
        'est_color'=> '#FF4444',
        'est_borde'=> '#FF4444',
        'est_dot'  => '#FF4444',
        'valor'    => '$450',
    ],
    [
        'id'       => '1039',
        'cliente'  => 'Ana Castillo',
        'equipo'   => 'iPad Air — batería',
        'icono_eq' => 'ti-device-tablet',
        'tecnico'  => 'Ana',
        'estado'   => 'Pendiente',
        'est_bg'   => '#FFF3E0',
        'est_color'=> '#FF9500',
        'est_borde'=> '#FF9500',
        'est_dot'  => '#FF9500',
        'valor'    => '$380',
    ],
    [
        'id'       => '1038',
        'cliente'  => 'Mario Reyes',
        'equipo'   => 'Samsung S23 — garantía',
        'icono_eq' => 'ti-device-mobile',
        'tecnico'  => 'Pedro',
        'estado'   => 'Garantía',
        'est_bg'   => '#F3E5F5',
        'est_color'=> '#7B4EC4',
        'est_borde'=> '#7B4EC4',
        'est_dot'  => '#7B4EC4',
        'valor'    => '$0',
    ],
    [
        'id'       => '1037',
        'cliente'  => 'Sofía Herrera',
        'equipo'   => 'Dell XPS 15 — diagnóstico',
        'icono_eq' => 'ti-device-laptop',
        'tecnico'  => 'María',
        'estado'   => 'Diagnóstico',
        'est_bg'   => '#F5F5F5',
        'est_color'=> '#424242',
        'est_borde'=> '#424242',
        'est_dot'  => '#424242',
        'valor'    => '$200',
    ],
    
];
$dispositivos = [
    ['tipo'=>'Teléfonos',  'icono'=>'ti-device-mobile',  'pct'=>45, 'color'=>'#2b7abc', 'bg'=>'#E3F2FD',  'tc'=>'#2b7abc'],
    ['tipo'=>'Laptops',    'icono'=>'ti-device-laptop',  'pct'=>28, 'color'=>'#00AA44', 'bg'=>'#E8F5E9',  'tc'=>'#00AA44'],
    ['tipo'=>'Tablets',    'icono'=>'ti-device-tablet',  'pct'=>15, 'color'=>'#FF9500', 'bg'=>'#FFF3E0',  'tc'=>'#FF9500'],
    ['tipo'=>'PC Torre',   'icono'=>'ti-device-desktop', 'pct'=>12, 'color'=>'#7B4EC4', 'bg'=>'#F3E5F5',  'tc'=>'#7B4EC4'],
];

$ordenes_recientes = [];
$dispositivos = [];

$dashboard_tables = [
    'cliente' => pick_table($conn, [
        'cliente'
    ]),

    'equipo' => pick_table($conn, [
        'equipo'
    ]),

    'tecnico' => pick_table($conn, [
        'tecnico'
    ]),

    'orden' => pick_table($conn, [
        'orden_reparacion',
        'Orden_Reparacion',
        'reparacion',
        'Reparacion',
        'orden'
    ]),

    'estado' => pick_table($conn, [
        'estado_servicio',
        'estado',
        'Estado_Servicio'
    ]),

    'garantia' => pick_table($conn, [
        'garantia'
    ]),
];

$has_dashboard_core = table_exists($conn, $dashboard_tables['orden'])
    && table_exists($conn, $dashboard_tables['estado'])
    && table_exists($conn, $dashboard_tables['equipo']);

// Variables para debug (comentar o eliminar después de verificar)
$_dashboard_debug = [
    'orden_encontrada' => $dashboard_tables['orden'] !== '' ? $dashboard_tables['orden'] : 'NO ENCONTRADA',
    'estado_encontrado' => $dashboard_tables['estado'] !== '' ? $dashboard_tables['estado'] : 'NO ENCONTRADO',
    'equipo_encontrado' => $dashboard_tables['equipo'] !== '' ? $dashboard_tables['equipo'] : 'NO ENCONTRADO',
];

$chart_tecnico_labels = [];
$chart_tecnico_reparaciones = [];
$chart_tecnico_ingresos = [];
$chart_estado_labels = [];
$chart_estado_values = [];
$chart_estado_colors = [];

if ($has_dashboard_core) {
    $d_ord = $dashboard_tables['orden'];
    $d_est = $dashboard_tables['estado'];
    $d_eq = $dashboard_tables['equipo'];
    $cliente_table_dashboard = $dashboard_tables['cliente'];
    $d_tec = $dashboard_tables['tecnico'];
    $d_gar = $dashboard_tables['garantia'];

    $estado_join = "FROM `{$d_ord}` o LEFT JOIN `{$d_est}` s ON s.id_estado = o.id_estado_actual";
    $estado_expr = "LOWER(COALESCE(s.nombre_estado, ''))";

    $en_proceso = (int)db_scalar($conn, "SELECT COUNT(*) {$estado_join} WHERE {$estado_expr} LIKE '%proceso%' OR {$estado_expr} LIKE '%repar%'");
    $pendientes = (int)db_scalar($conn, "SELECT COUNT(*) {$estado_join} WHERE {$estado_expr} LIKE '%pend%' OR {$estado_expr} LIKE '%recib%'");
    $con_falla = (int)db_scalar($conn, "SELECT COUNT(*) {$estado_join} WHERE {$estado_expr} LIKE '%falla%'");
    $completadas = (int)db_scalar($conn, "SELECT COUNT(*) {$estado_join} WHERE ({$estado_expr} LIKE '%entreg%' OR {$estado_expr} LIKE '%complet%' OR {$estado_expr} LIKE '%listo%') AND MONTH(COALESCE(o.fecha_entrega_real, o.fecha_actualizacion, o.fecha_ingreso)) = MONTH(CURDATE()) AND YEAR(COALESCE(o.fecha_entrega_real, o.fecha_actualizacion, o.fecha_ingreso)) = YEAR(CURDATE())");
    $ingresos_hoy = (float)db_scalar($conn, "SELECT COALESCE(SUM(o.costo_total), 0) FROM `{$d_ord}` o WHERE DATE(COALESCE(o.fecha_entrega_real, o.fecha_actualizacion, o.fecha_ingreso)) = CURDATE()", 0);
    $ingresos_ayer = (float)db_scalar($conn, "SELECT COALESCE(SUM(o.costo_total), 0) FROM `{$d_ord}` o WHERE DATE(COALESCE(o.fecha_entrega_real, o.fecha_actualizacion, o.fecha_ingreso)) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)", 0);
    $garantias_activas = table_exists($conn, $d_gar)
        ? (int)db_scalar($conn, "SELECT COUNT(*) FROM `{$d_gar}` WHERE (LOWER(COALESCE(estado, '')) LIKE '%activ%' OR (CURDATE() BETWEEN fecha_inicio AND fecha_fin))")
        : 0;

    $trend = $ingresos_ayer > 0 ? (($ingresos_hoy - $ingresos_ayer) / $ingresos_ayer) * 100 : ($ingresos_hoy > 0 ? 100 : 0);
    $trend_color = $trend >= 0 ? '#00AA44' : '#2b7abc';
    $trend_icon = $trend >= 0 ? 'ti-trending-up' : 'ti-trending-down';

    $kpis = [
        ['clave' => 'en_proceso', 'label' => 'En proceso', 'valor' => $en_proceso, 'sub' => 'órdenes activas', 'icono' => 'ti-loader', 'color' => '#2b7abc', 'bg' => '#E3F2FD', 'texto' => '#2b7abc'],
        ['clave' => 'pendientes', 'label' => 'Pendientes', 'valor' => $pendientes, 'sub' => 'sin completar', 'icono' => 'ti-clock', 'color' => '#FF9500', 'bg' => '#FFF3E0', 'texto' => '#FF9500'],
        ['clave' => 'ingresos', 'label' => 'Ingresos hoy', 'valor' => '$' . number_format($ingresos_hoy, 2), 'sub' => '<span style="color:' . $trend_color . ';display:flex;align-items:center;gap:3px;"><i class="ti ' . $trend_icon . '" style="font-size:11px;"></i>' . number_format(abs($trend), 0) . '% vs ayer</span>', 'icono' => 'ti-cash', 'color' => '#00AA44', 'bg' => '#E8F5E9', 'texto' => '#00AA44'],
        ['clave' => 'garantias', 'label' => 'Garantías', 'valor' => $garantias_activas, 'sub' => 'activas ahora', 'icono' => 'ti-shield', 'color' => '#7B4EC4', 'bg' => '#F3E5F5', 'texto' => '#7B4EC4'],
        ['clave' => 'completadas', 'label' => 'Completadas', 'valor' => $completadas, 'sub' => 'servicios este mes', 'icono' => 'ti-checks', 'color' => '#424242', 'bg' => '#F5F5F5', 'texto' => '#424242'],
        ['clave' => 'con_falla', 'label' => 'Con falla', 'valor' => $con_falla, 'sub' => 'requieren atención', 'icono' => 'ti-alert-triangle', 'color' => '#FF4444', 'bg' => '#FFEBEE', 'texto' => '#FF4444'],
    ];

    $ordenes_recientes = [];
    $ordenes_sql = "SELECT o.id_orden, COALESCE(c.nombre, 'Cliente no asignado') AS cliente, COALESCE(e.tipo, '') AS tipo, COALESCE(e.marca, '') AS marca, COALESCE(e.modelo, '') AS modelo, COALESCE(t.nombre, 'Sin técnico') AS tecnico, COALESCE(s.nombre_estado, 'Sin estado') AS estado, COALESCE(o.costo_total, 0) AS costo_total FROM `{$d_ord}` o LEFT JOIN `{$d_eq}` e ON e.id_equipo = o.id_equipo LEFT JOIN `{$cliente_table_dashboard}` c ON c.id_cliente = e.id_cliente LEFT JOIN `{$d_tec}` t ON t.id_tecnico = o.id_tecnico LEFT JOIN `{$d_est}` s ON s.id_estado = o.id_estado_actual ORDER BY COALESCE(o.fecha_actualizacion, o.fecha_creacion, o.fecha_ingreso) DESC, o.id_orden DESC LIMIT 6";
    foreach (db_rows($conn, $ordenes_sql) as $row) {
        $status = (string)$row['estado'];
        $palette = status_palette($status);
        $tipo = trim((string)$row['tipo']);
        $equipo_label = trim($tipo . ' ' . (string)$row['marca'] . ' ' . (string)$row['modelo']);
        $ordenes_recientes[] = ['id' => (string)$row['id_orden'], 'cliente' => (string)$row['cliente'], 'equipo' => $equipo_label !== '' ? $equipo_label : 'Equipo sin detalle', 'icono_eq' => device_icon($tipo), 'tecnico' => (string)$row['tecnico'], 'estado' => $status, 'est_bg' => $palette['bg'], 'est_color'=> $palette['color'], 'est_borde'=> $palette['border'], 'est_dot' => $palette['dot'], 'valor' => '$' . number_format((float)$row['costo_total'], 2)];
    }

    $device_rows = db_rows($conn, "SELECT COALESCE(NULLIF(TRIM(tipo), ''), 'Sin tipo') AS tipo, COUNT(*) AS total FROM `{$d_eq}` GROUP BY COALESCE(NULLIF(TRIM(tipo), ''), 'Sin tipo') ORDER BY total DESC LIMIT 5");
    $device_total = array_sum(array_map(fn($row) => (int)$row['total'], $device_rows));
    $device_colors = ['#2b7abc', '#00AA44', '#FF9500', '#7B4EC4', '#424242'];
    $device_bgs = ['#E3F2FD', '#E8F5E9', '#FFF3E0', '#F3E5F5', '#F5F5F5'];
    $dispositivos = [];
    foreach ($device_rows as $idx => $row) {
        $color = $device_colors[$idx % count($device_colors)];
        $dispositivos[] = ['tipo' => (string)$row['tipo'], 'icono' => device_icon((string)$row['tipo']), 'pct' => $device_total > 0 ? (int)round(((int)$row['total'] / $device_total) * 100) : 0, 'color' => $color, 'bg' => $device_bgs[$idx % count($device_bgs)], 'tc' => $color];
    }

    foreach (db_rows($conn, "SELECT COALESCE(t.nombre, 'Sin técnico') AS tecnico, COUNT(o.id_orden) AS reparaciones, COALESCE(SUM(o.costo_total), 0) AS ingresos FROM `{$d_ord}` o LEFT JOIN `{$d_tec}` t ON t.id_tecnico = o.id_tecnico WHERE MONTH(COALESCE(o.fecha_entrega_real, o.fecha_actualizacion, o.fecha_ingreso)) = MONTH(CURDATE()) AND YEAR(COALESCE(o.fecha_entrega_real, o.fecha_actualizacion, o.fecha_ingreso)) = YEAR(CURDATE()) GROUP BY COALESCE(t.nombre, 'Sin técnico') ORDER BY reparaciones DESC LIMIT 6") as $row) {
        $chart_tecnico_labels[] = (string)$row['tecnico'];
        $chart_tecnico_reparaciones[] = (int)$row['reparaciones'];
        $chart_tecnico_ingresos[] = round(((float)$row['ingresos']) / 100, 2);
    }

    $estado_rows = db_rows($conn, "SELECT COALESCE(s.nombre_estado, 'Sin estado') AS estado, COUNT(o.id_orden) AS total {$estado_join} GROUP BY COALESCE(s.nombre_estado, 'Sin estado') ORDER BY total DESC");
    $estado_total = array_sum(array_map(fn($row) => (int)$row['total'], $estado_rows));
    foreach ($estado_rows as $row) {
        $palette = status_palette((string)$row['estado']);
        $chart_estado_labels[] = (string)$row['estado'];
        $chart_estado_values[] = $estado_total > 0 ? (int)round(((int)$row['total'] / $estado_total) * 100) : 0;
        $chart_estado_colors[] = $palette['color'];
    }
} else {
    $kpis = [
        ['clave' => 'en_proceso', 'label' => 'En proceso', 'valor' => 0, 'sub' => 'sin tablas base', 'icono' => 'ti-loader', 'color' => '#2b7abc', 'bg' => '#E3F2FD', 'texto' => '#2b7abc'],
        ['clave' => 'pendientes', 'label' => 'Pendientes', 'valor' => 0, 'sub' => 'sin datos', 'icono' => 'ti-clock', 'color' => '#FF9500', 'bg' => '#FFF3E0', 'texto' => '#FF9500'],
        ['clave' => 'ingresos', 'label' => 'Ingresos hoy', 'valor' => '$0.00', 'sub' => '—', 'icono' => 'ti-cash', 'color' => '#00AA44', 'bg' => '#E8F5E9', 'texto' => '#00AA44'],
        ['clave' => 'garantias', 'label' => 'Garantías', 'valor' => 0, 'sub' => 'activas', 'icono' => 'ti-shield', 'color' => '#7B4EC4', 'bg' => '#F3E5F5', 'texto' => '#7B4EC4'],
        ['clave' => 'completadas', 'label' => 'Completadas', 'valor' => 0, 'sub' => 'este mes', 'icono' => 'ti-checks', 'color' => '#424242', 'bg' => '#F5F5F5', 'texto' => '#424242'],
        ['clave' => 'con_falla', 'label' => 'Con falla', 'valor' => 0, 'sub' => '—', 'icono' => 'ti-alert-triangle', 'color' => '#FF4444', 'bg' => '#FFEBEE', 'texto' => '#FF4444'],
    ];
}

$fallas_urgentes = array_filter($ordenes_recientes, fn($o) => strtolower($o['estado']) === 'con falla' || strpos(strtolower($o['estado']), 'falla'));
$total_fallas    = count($fallas_urgentes);

foreach ($nav_items as &$nav_item) {
    if (($nav_item['key'] ?? '') === 'garantias' && !empty($nav_item['badge'])) {
        $nav_item['badge']['valor'] = (int)($kpis[3]['valor'] ?? 0);
    }
}
unset($nav_item);

$notif_table_ui = pick_table($conn, ['notificacion', 'Notificacion']);
$notificaciones_top = [];
$notificaciones_pendientes = 0;
if ($notif_table_ui !== '') {
    $ncol = table_columns($conn, $notif_table_ui);
    $orderNotif = isset($ncol['fecha_envio']) ? 'fecha_envio' : (isset($ncol['id_notificacion']) ? 'id_notificacion' : '');
    if ($orderNotif !== '') {
        $notificaciones_top = db_rows($conn, "SELECT * FROM `{$notif_table_ui}` ORDER BY `{$orderNotif}` DESC LIMIT 8");
        // Contar notificaciones pendientes
        foreach ($notificaciones_top as $n) {
            if (isset($n['estado']) && strtolower($n['estado']) === 'pendiente') {
                $notificaciones_pendientes++;
            }
        }
    }
}
$global_ord_search_q = isset($_GET['ord_q']) && is_string($_GET['ord_q']) ? trim($_GET['ord_q']) : '';

$fecha_es = null;
if (class_exists('IntlDateFormatter')) {
    try {
        $fecha_es = (new IntlDateFormatter(
            'es_DO',
            IntlDateFormatter::FULL,
            IntlDateFormatter::NONE
        ))->format(new DateTime());
    } catch (Throwable $e) {
        $fecha_es = null;
    }
}
// Fallback si no hay extensión intl
if (!$fecha_es) {
    $dias   = ['domingo','lunes','martes','miércoles','jueves','viernes','sábado'];
    $meses  = ['','ene.','feb.','mar.','abr.','may.','jun.','jul.','ago.','sep.','oct.','nov.','dic.'];
    $fecha_es = $dias[date('w')].' '.date('j').' '.$meses[(int)date('n')].' '.date('Y');
}
?>
<!DOCTYPE html>
<html lang="es" id="repairly-root">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ReparlyRD — Dashboard</title>
<link rel="shortcut icon" href="logo.ico" type="image/x-icon">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.19.0/dist/tabler-icons.min.css">
<style>
/* ── Reset ── */
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html{font-size:14px}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',system-ui,sans-serif;background:var(--rl-bg,#F8F7F5);color:var(--rl-text,#1C1A17);height:100vh;display:flex;overflow:hidden;transition:background .2s,color .2s}
.theme-dark{--rl-bg:#121417;--rl-text:#E8E6E3;--rl-card:#1a1f26;--rl-border:#333840;--rl-muted:#9a9590;--rl-sub:#b8b3ad}
.theme-dark .sidebar{background:#050810}
.theme-dark .topbar{background:var(--rl-card);border-color:var(--rl-border)}
.theme-dark .topbar-title,.theme-dark .card-title{color:var(--rl-text)}
.theme-dark .topbar-sub{color:var(--rl-muted)}
.theme-dark .topbar-search,.theme-dark .topbar-btn,.theme-dark .topbar-date{background:var(--rl-bg);border-color:var(--rl-border)}
.theme-dark .card,.theme-dark .charts-card,.theme-dark .ordenes-card,.theme-dark .dispositivos-card{background:var(--rl-card);border-color:var(--rl-border)}
.theme-dark .table-head{background:#252b34;color:#a8a39e}
.theme-dark .table-row{border-color:var(--rl-border)}
.night-overlay{pointer-events:none;position:fixed;inset:0;background:rgba(255,180,70,.14);z-index:99998;display:none;mix-blend-mode:multiply}
.night-overlay.on{display:block}
.notif-dd-wrap{position:relative}
.notif-dropdown{position:absolute;right:0;top:calc(100% + 8px);width:min(340px,calc(100vw - 40px));max-height:360px;overflow:auto;background:#fff;border:0.5px solid #D0CCC6;border-radius:10px;box-shadow:0 10px 28px rgba(0,0,0,.14);display:none;padding:8px 0;z-index:250}
.theme-dark .notif-dropdown{background:var(--rl-card);border-color:var(--rl-border)}
.notif-dropdown.open{display:block}
.notif-item{padding:10px 14px;border-bottom:0.5px solid #EDECEA;font-size:12px;color:#322F2A}
.theme-dark .notif-item{border-color:var(--rl-border);color:var(--rl-text)}
.notif-item:last-child{border-bottom:none}
.notif-item small{display:block;font-size:10px;color:#8C8479;margin-top:4px}
.topbar-search input{border:0;background:transparent;outline:none;font:inherit;color:inherit;width:160px;min-width:0}
.topbar-link{font-size:11.5px;padding:8px 12px;border-radius:7px;border:0.5px solid #D0CCC6;background:#F8F7F5;color:#2b7abc;text-decoration:none;font-weight:600;white-space:nowrap}
.topbar-link:hover{background:#EDECEA}
.theme-dark .topbar-link{background:var(--rl-bg);border-color:var(--rl-border);color:#3489d4}

/* ── Sidebar ── */
.sidebar{width:190px;min-height:100vh;background:#163f6e;display:flex;flex-direction:column;flex-shrink:0;position:sticky;top:0;height:100vh;overflow-y:auto}
.sidebar-logo{padding:18px 14px 14px;border-bottom:0.5px solid rgba(255,255,255,0.08);display:flex;flex-direction:column;align-items:center;gap:8px;text-align:center}
.sidebar-logo-img{width:56px;height:56px;border-radius:50%;object-fit:cover;background:#fff;box-shadow:0 2px 10px rgba(0,0,0,0.25);flex-shrink:0}
.sidebar-logo-fallback{width:56px;height:56px;border-radius:50%;background:#2b7abc;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.sidebar-logo-fallback i{color:#fff;font-size:22px}
.sidebar-logo-text{color:#fff;font-size:13px;font-weight:600;line-height:1.2;letter-spacing:0.01em}
.sidebar-logo-ver{color:rgba(255,255,255,0.35);font-size:9px;letter-spacing:0.05em}
.sidebar-nav{padding:10px 8px;flex:1}
.nav-section{font-size:8.5px;color:rgba(255,255,255,0.3);text-transform:uppercase;letter-spacing:0.1em;padding:10px 8px 6px}
.nav-section:first-child{padding-top:2px}
.nav-item{display:flex;align-items:center;gap:8px;padding:8px 10px;border-radius:7px;cursor:pointer;transition:background 0.15s;text-decoration:none;margin-bottom:1px}
.nav-item:hover{background:rgba(255,255,255,0.07)}
.nav-item.active{background:#2b7abc}
.nav-item i{color:rgba(255,255,255,0.55);font-size:16px;flex-shrink:0}
.nav-item.active i{color:#fff}
.nav-item span{color:rgba(255,255,255,0.65);font-size:12.5px}
.nav-item.active span{color:#fff}
.nav-badge{margin-left:auto;font-size:9px;font-weight:600;padding:2px 6px;border-radius:10px;line-height:1.4}
.nav-dot{margin-left:auto;width:7px;height:7px;border-radius:50%}
.sidebar-user{padding:12px 14px;border-top:0.5px solid rgba(255,255,255,0.08);display:flex;align-items:center;gap:8px}
.user-avatar{width:28px;height:28px;border-radius:50%;background:#2b7abc;display:flex;align-items:center;justify-content:center;font-size:10.5px;color:#fff;font-weight:600;flex-shrink:0}
.user-nombre{color:rgba(255,255,255,0.82);font-size:11.5px;font-weight:500;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.user-rol{color:rgba(255,255,255,0.38);font-size:9.5px}
.user-logout{margin-left:auto;color:rgba(255,255,255,0.3);font-size:15px;cursor:pointer;transition:color 0.15s;flex-shrink:0}
.user-logout:hover{color:rgba(255,255,255,0.7)}

/* ── Main ── */
.main{
    flex:1;
    display:flex;
    flex-direction:column;
    min-width:0;
    max-width:1400px;
    margin:auto;
    width:100%;
    overflow-y:auto;
    height:100vh;
}
.topbar{background:#fff;border-bottom:0.5px solid #D0CCC6;padding:11px 20px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:100;flex-shrink:0;}
.topbar-title{font-size:19px;font-weight:500;color:#1C1A17;line-height:1.2}
.topbar-sub{font-size:11.5px;color:#6B6560;margin-top:1px}
.topbar-actions{display:flex;align-items:center;gap:9px}
.topbar-search{background:#F8F7F5;border:0.5px solid #D0CCC6;border-radius:7px;padding:6px 11px;display:flex;align-items:center;gap:6px;font-size:11.5px;color:#8C8479;cursor:text}
.topbar-search i{font-size:13px}
.topbar-btn{background:#F8F7F5;border:0.5px solid #D0CCC6;border-radius:7px;width:32px;height:32px;display:flex;align-items:center;justify-content:center;cursor:pointer;position:relative;transition:background 0.15s}
.topbar-btn:hover{background:#EDECEA}
.topbar-btn i{font-size:16px;color:#4D4841}
.notif-dot{position:absolute;top:6px;right:6px;width:7px;height:7px;border-radius:50%;background:#2b7abc;border:1.5px solid #fff}
.topbar-date{font-size:11px;color:#6B6560;background:#F8F7F5;border:0.5px solid #D0CCC6;border-radius:7px;padding:6px 9px;white-space:nowrap;display:flex;align-items:center;gap:5px}
.topbar-date i{font-size:12px}

/* ── Contenido ── */
.content{padding:16px 20px;flex:1}

/* ── Alerta urgente ── */
.alert-falla{background:#e8f2fb;border:0.5px solid #2b7abc;border-radius:8px;padding:9px 14px;display:flex;align-items:center;gap:9px;margin-bottom:16px}
.alert-falla i{font-size:16px;color:#2b7abc;flex-shrink:0}
.alert-falla-txt{font-size:12px;color:#163f6e;font-weight:500}
.alert-falla-btn{margin-left:auto;font-size:11px;padding:4px 10px;border-radius:5px;background:#fff;border:0.5px solid #2b7abc;color:#2b7abc;cursor:pointer;transition:background 0.15s;text-decoration:none;white-space:nowrap}
.alert-falla-btn:hover{background:#e8f2fb}

/* ── KPI Grid ── */
.kpi-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:10px}
.kpi-grid:last-of-type{margin-bottom:18px}
.kpi-card{border-radius:10px;border:none;padding:16px;box-shadow:0 2px 8px rgba(0,0,0,0.08);transition:transform 0.2s,box-shadow 0.2s;position:relative;overflow:hidden}
.kpi-card::before{content:'';position:absolute;top:0;left:0;width:100%;height:3px;background:var(--kpi-color,#2b7abc);opacity:0.8}
.kpi-label{font-size:9px;font-weight:600;text-transform:uppercase;letter-spacing:0.1em;margin-bottom:8px;display:flex;align-items:center;gap:4px}
.kpi-label i{font-size:12px}
.kpi-valor{font-size:32px;font-weight:700;font-variant-numeric:tabular-nums;line-height:1;font-family:'Courier New',Courier,monospace;margin-bottom:3px}
.kpi-sub{font-size:11px;color:#6B6560;margin-top:4px}

/* ── Fila de charts ── */
.charts-row{display:grid;grid-template-columns:1.65fr 1fr;gap:12px;margin-bottom:14px}
.card{background:#fff;border-radius:9px;border:0.5px solid #D0CCC6;padding:15px}
.card-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:6px}
.card-title{font-size:13.5px;font-weight:500;color:#1C1A17}
.card-pill{font-size:10px;color:#8C8479;background:#F8F7F5;padding:3px 8px;border-radius:4px;border:0.5px solid #D0CCC6}
.chart-legend{display:flex;gap:14px;margin-bottom:9px}
.legend-item{display:flex;align-items:center;gap:5px;font-size:10px;color:#6B6560}
.legend-sq{width:9px;height:9px;border-radius:2px;display:inline-block;flex-shrink:0}
.chart-wrap{position:relative}
.donut-legend{display:grid;grid-template-columns:1fr 1fr;gap:3px;margin-top:9px}
.donut-legend-item{display:flex;align-items:center;gap:5px;font-size:9.5px;color:#6B6560}
.donut-dot{width:7px;height:7px;border-radius:50%;display:inline-block;flex-shrink:0}

/* ── Fila inferior ── */
.bottom-row{display:grid;grid-template-columns:1fr 2fr;gap:12px}

/* ── Dispositivos ── */
.dispositivos-card{background:#fff;border-radius:9px;border:0.5px solid #D0CCC6;padding:15px}
.dispositivo-item{margin-bottom:12px}
.dispositivo-item:last-child{margin-bottom:0}
.dispositivo-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:5px}
.dispositivo-info{display:flex;align-items:center;gap:8px}
.dispositivo-icon{width:28px;height:28px;border-radius:7px;display:flex;align-items:center;justify-content:center;font-size:14px;flex-shrink:0}
.dispositivo-nombre{font-size:12px;color:#322F2A}
.dispositivo-pct{font-size:12.5px;font-weight:500;color:#1C1A17;font-family:'Courier New',Courier,monospace}
.barra-track{background:#EDECEA;border-radius:3px;height:5px}
.barra-fill{height:100%;border-radius:3px;transition:width 0.6s ease}

/* ── Tabla de órdenes ── */
.ordenes-card{background:#fff;border-radius:9px;border:0.5px solid #D0CCC6;overflow:hidden}
.ordenes-header{padding:12px 15px;border-bottom:0.5px solid #EDECEA;display:flex;align-items:center;justify-content:space-between;background:#FAFAF9}
.ordenes-ver-btn{font-size:11px;padding:5px 10px;border-radius:5px;background:#fff;border:0.5px solid #D0CCC6;color:#4D4841;cursor:pointer;text-decoration:none;transition:background 0.15s}
.ordenes-ver-btn:hover{background:#F8F7F5}
.table-head{display:grid;grid-template-columns:42px 1fr 110px 120px 85px;gap:8px;padding:7px 15px;background:#EDECEA;font-size:9.5px;font-weight:500;color:#6B6560;text-transform:uppercase;letter-spacing:0.07em}
.table-row{display:grid;grid-template-columns:42px 1fr 110px 120px 85px;gap:8px;padding:9px 15px;border-bottom:0.5px solid #EDECEA;align-items:center;transition:background 0.1s}
.table-row:last-child{border-bottom:none}
.table-row:hover{background:#FAFAF9}
.order-id{font-size:10.5px;color:#8C8479;font-family:'Courier New',Courier,monospace}
.order-cliente{font-size:12px;color:#1C1A17;font-weight:500;margin-bottom:1px}
.order-equipo{font-size:10px;color:#6B6560;display:flex;align-items:center;gap:3px}
.order-equipo i{font-size:11px}
.order-tecnico{font-size:11.5px;color:#4D4841}
.status-badge{display:inline-flex;align-items:center;gap:4px;padding:3px 7px;border-radius:4px;font-size:10px;font-weight:500;border-width:0.5px;border-style:solid;white-space:nowrap}
.status-dot{width:5px;height:5px;border-radius:50%;display:inline-block;flex-shrink:0}
.order-valor{font-size:12px;font-weight:500;color:#1C1A17;text-align:right;font-family:'Courier New',Courier,monospace}

/* ── Responsive ── */
@media(max-width:960px){
    .sidebar{width:56px}
    .sidebar-logo-text,.sidebar-logo-ver,.nav-item span,.nav-section,.user-nombre,.user-rol,.nav-badge{display:none}
    .sidebar-logo{padding:12px 6px;flex-direction:column;justify-content:center}
    .sidebar-logo-img,.sidebar-logo-fallback{width:38px;height:38px}
    .sidebar-logo-fallback i{font-size:16px}
    .nav-item{justify-content:center;padding:10px}
    .sidebar-user{justify-content:center;padding:10px}
    .user-logout{display:none}
    .kpi-grid{grid-template-columns:repeat(2,1fr)}
    .charts-row{grid-template-columns:1fr}
    .bottom-row{grid-template-columns:1fr}
}
@media(max-width:640px){
    .kpi-grid{grid-template-columns:1fr}
    .table-head,.table-row{grid-template-columns:36px 1fr 65px 55px}
    .col-tecnico{display:none}
}

/* ── Contenedor de registros con scroll ── */
.records-scroll-container{
    max-height:400px;
    overflow-y:auto;
    border:0.5px solid #EDECEA;
    border-radius:8px;
    background:#fff;
}
.records-scroll-container .table-head{
    position:sticky;
    top:0;
    z-index:10;
}

/* ── Filtro de búsqueda ── */
.search-input-filtro{
    width:100%;
    padding:10px 12px;
    border:0.5px solid #D0CCC6;
    border-radius:8px;
    font-size:13px;
    transition:border-color 0.15s;
}
.search-input-filtro:focus{
    outline:none;
    border-color:#2b7abc;
    box-shadow:0 0 0 3px rgba(43,122,188,0.08);
}

/* ===== Clientes Moderno ===== */

.clientes-form-card{
    background:#fff;
    border-radius:18px;
    padding:22px;
    margin-top:18px;
    box-shadow:0 4px 18px rgba(0,0,0,.05);
}

.clientes-form-grid{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:18px;
    margin-top:20px;
}

.form-group{
    display:flex;
    flex-direction:column;
    gap:6px;
}

.form-group.full{
    grid-column:1/-1;
}

.form-group input{
    height:46px;
    border:1px solid #D8D5D0;
    border-radius:12px;
    padding:0 14px;
    font-size:14px;
}

.clientes-actions{
    display:flex;
    gap:10px;
    justify-content:flex-end;
    grid-column:1/-1;
}

.btn-save{
    background:#2b7abc;
    color:#fff;
    border:none;
    border-radius:10px;
    padding:12px 18px;
    cursor:pointer;
}

.btn-cancel{
    border:1px solid #D8D5D0;
    border-radius:10px;
    padding:12px 18px;
    text-decoration:none;
    color:#444;
}

.dgv-clientes{
    margin-top:24px;
    background:#fff;
    border-radius:18px;
    overflow:hidden;
    box-shadow:0 4px 18px rgba(0,0,0,.05);
}

.dgv-header,
.dgv-row{
    display:grid;
    grid-template-columns:80px 1.4fr 1fr 1.2fr 180px;
    gap:14px;
    padding:16px 18px;
    align-items:center;
}

.dgv-header{
    background:#F6F8FB;
    font-size:12px;
    font-weight:700;
    text-transform:uppercase;
}

.dgv-row{
    border-top:1px solid #eee;
}

.dgv-actions{
    display:flex;
    gap:8px;
}

.btn-edit{
    background:#e8f2fb;
    color:#2b7abc;
    padding:8px 12px;
    border-radius:9px;
    text-decoration:none;
}

.btn-delete{
    background:#FCEBEC;
    color:#C0392B;
    border:none;
    border-radius:9px;
    padding:8px 12px;
    cursor:pointer;
}



/* ===== CRUD MODERNO ===== */

.form-card,
.crud-card,
.module-card{
    background:#fff;
    border-radius:18px;
    padding:22px;
    margin-top:18px;
    box-shadow:0 4px 18px rgba(0,0,0,.05);
}

.crud-grid{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:18px;
}

.crud-grid .full{
    grid-column:1/-1;
}

.crud-grid input,
.crud-grid select,
.crud-grid textarea{
    height:46px;
    border:1px solid #D8D5D0;
    border-radius:12px;
    padding:0 14px;
    font-size:14px;
    width:100%;
}

.crud-grid textarea{
    min-height:120px;
    padding-top:12px;
}

.btn-primary-modern{
    background:#2b7abc;
    color:#fff;
    border:none;
    border-radius:10px;
    padding:12px 18px;
    cursor:pointer;
}

.table-modern{
    width:100%;
    border-collapse:collapse;
    margin-top:20px;
    overflow:hidden;
    border-radius:16px;
    background:#fff;
}

.table-modern th{
    background:#F6F8FB;
    text-align:left;
    padding:14px;
    font-size:12px;
    text-transform:uppercase;
}

.table-modern td{
    padding:14px;
    border-top:1px solid #eee;
}

.btn-edit{
    background:#e8f2fb;
    color:#2b7abc;
    padding:8px 12px;
    border-radius:8px;
    text-decoration:none;
}

.btn-delete{
    background:#FCEBEC;
    color:#C0392B;
    border:none;
    border-radius:8px;
    padding:8px 12px;
    cursor:pointer;
}

</style>
</head>
<body>

<div id="night-overlay" class="night-overlay" aria-hidden="true"></div>

<!-- ════════════════════════════════════════════════
     SIDEBAR
═════════════════════════════════════════════════ -->
<aside class="sidebar" role="navigation" aria-label="Navegación principal">

    <div class="sidebar-logo">
        <img src="assets/logo_sidebar.png" alt="RepairlyRD" class="sidebar-logo-img"
             onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
        <div class="sidebar-logo-fallback" style="display:none;" aria-hidden="true">
            <i class="ti ti-tool"></i>
        </div>
        <div>
            <div class="sidebar-logo-text">RepairlyRD</div>
            <div class="sidebar-logo-ver">ERP · Sistema de Gestión</div>
        </div>
    </div>

    <nav class="sidebar-nav">
        <?php foreach ($nav_items as $item): ?>
            <?php if (!empty($item['seccion'])): ?>
                <div class="nav-section"><?= htmlspecialchars($item['label']) ?></div>
            <?php else: ?>
                <a href="<?= htmlspecialchars($item['href'] ?? '#') ?>"
                   class="nav-item<?= !empty($item['activo']) ? ' active' : '' ?>"
                   <?= !empty($item['activo']) ? 'aria-current="page"' : '' ?>
                   title="<?= htmlspecialchars($item['label'] . (!empty($item['desc']) ? ' — ' . $item['desc'] : '')) ?>">
                    <i class="ti <?= htmlspecialchars($item['icono']) ?>" aria-hidden="true"></i>
                    <span><?= htmlspecialchars($item['label']) ?></span>
                    <?php if (!empty($item['badge'])): ?>
                        <?php $b = $item['badge']; ?>
                        <?php if (!empty($b['dot'])): ?>
                            <span class="nav-dot" style="background:<?= $b['bg'] ?>;"></span>
                        <?php else: ?>
                            <span class="nav-badge"
                                  style="background:<?= $b['bg'] ?>;color:<?= $b['color'] ?>;">
                                <?= (int)$b['valor'] ?>
                            </span>
                        <?php endif; ?>
                    <?php endif; ?>
                </a>
            <?php endif; ?>
        <?php endforeach; ?>
    </nav>

    <div class="sidebar-user">
        <div class="user-avatar" aria-hidden="true"><?= htmlspecialchars($usuario['iniciales']) ?></div>
        <div style="min-width:0;flex:1">
            <div class="user-nombre"><?= htmlspecialchars($usuario['nombre']) ?></div>
            <div class="user-rol"><?= htmlspecialchars($usuario['rol']) ?></div>
        </div>
        <span style="margin-left:auto;display:flex;gap:8px;align-items:center;">
            <a href="portal.php" class="ti ti-user" style="color:rgba(255,255,255,0.38);font-size:15px;text-decoration:none;" title="Mi cuenta"></a>
            <a href="?logout=1" class="ti ti-logout" style="color:rgba(255,255,255,0.3);font-size:15px;text-decoration:none;" title="Cerrar sesión"></a>
        </span>
    </div>
</aside>


<!-- ════════════════════════════════════════════════
     MAIN
═════════════════════════════════════════════════ -->
<main class="main">

    <!-- ── Top bar ── -->
    <header class="topbar">
        <div>
            <div class="topbar-title"><?= htmlspecialchars($page['label']) ?></div>
            <div class="topbar-sub"><?= h($page['desc']) ?></div>
        </div>
        <div class="topbar-actions">
            <a class="topbar-link" href="portal.php" title="Área de cuenta">Mi cuenta</a>
            <form class="topbar-search" method="get" action="index.php" role="search" style="cursor:default;">
                <input type="hidden" name="page" value="ordenes">
                <i class="ti ti-search" aria-hidden="true"></i>
                <input type="search" name="ord_q" value="<?= h($global_ord_search_q) ?>" placeholder="Buscar por código o ID…" aria-label="Buscar orden">
                <button type="submit" class="ordenes-ver-btn" style="padding:4px 10px;">Ir</button>
            </form>
            <div class="notif-dd-wrap">
                <button type="button" class="topbar-btn" id="notif-toggle" title="Notificaciones" aria-expanded="false" aria-controls="notif-menu">
                    <i class="ti ti-bell" aria-hidden="true"></i>
                    <?php if ($notificaciones_pendientes > 0): ?>
                        <span class="notif-dot" aria-hidden="true"></span>
                    <?php elseif ($total_fallas > 0): ?>
                        <span class="notif-dot" aria-hidden="true"></span>
                    <?php endif; ?>
                </button>
                <div class="notif-dropdown" id="notif-menu" role="menu">
                    <?php if (empty($notificaciones_top)): ?>
                        <div class="notif-item">No hay notificaciones recientes.</div>
                    <?php else: ?>
                        <?php foreach ($notificaciones_top as $n): ?>
                            <div class="notif-item">
                                <?= h((string)($n['mensaje'] ?? $n['MENSAJE'] ?? $n['tipo'] ?? 'Notificación')) ?>
                                <small><?= h((string)($n['fecha_envio'] ?? $n['fecha'] ?? '')) ?> · <?= h((string)($n['tipo'] ?? '')) ?></small>
                            </div>
                        <?php endforeach; ?>
                        <div style="padding:8px 14px;">
                            <a class="ordenes-ver-btn" href="?page=notificaciones" style="display:inline-block;width:100%;text-align:center;">Ver todas</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="topbar-date">
                <i class="ti ti-calendar" aria-hidden="true"></i>
                <?= htmlspecialchars($fecha_es) ?>
            </div>
        </div>
    </header>


    <!-- ── Contenido ── -->
    <div class="content">
        <?php if ($flash['type'] === 'ok' && $flash['msg']): ?>
            <div class="alert-falla" style="background:#E8F5E9;border-color:#00AA44;margin-bottom:12px;">
                <i class="ti ti-check" style="color:#00AA44;"></i>
                <span class="alert-falla-txt" style="color:#1B5E20;"><?= h((string)$flash['msg']) ?></span>
            </div>
        <?php elseif ($flash['type'] === 'err' && $flash['msg']): ?>
            <div class="alert-falla" style="margin-bottom:12px;">
                <i class="ti ti-alert-circle"></i>
                <span class="alert-falla-txt"><?= h((string)$flash['msg']) ?></span>
            </div>
        <?php endif; ?>

        <?php if ($current_page === 'clientes'): ?>

            <?php
            $clientes = [];
            $clientes_error = null;
            $cliente_id_field = isset($cliente_cols['id_cliente']) ? 'id_cliente' : (isset($cliente_cols['ID_CLIENTE']) ? 'ID_CLIENTE' : 'id_cliente');
            $can_fecha_registro = isset($cliente_cols['fecha_registro']);

            if (!$cliente_table) {
                $clientes_error = 'No se encontró la tabla de clientes en la base de datos.';
            } else {
                $sql = "SELECT `{$cliente_id_field}` AS id, `nombre`, `telefono`, `email`, `direccion`" . ($can_fecha_registro ? ", `fecha_registro`" : "") . " FROM `{$cliente_table}`";
                $types = '';
                $params = [];
                if ($clientes_search !== '') {
                    $sql .= " WHERE `nombre` LIKE ? OR `telefono` LIKE ? OR `email` LIKE ?";
                    $q = '%' . $clientes_search . '%';
                    $types = 'sss';
                    $params = [$q, $q, $q];
                }
                $sql .= " ORDER BY `{$cliente_id_field}` DESC LIMIT 200";
                $stmt = $conn->prepare($sql);
                if ($stmt) {
                    if ($types !== '') {
                        $stmt->bind_param($types, ...$params);
                    }
                    $stmt->execute();
                    $res = $stmt->get_result();
                    if ($res) {
                        $clientes = $res->fetch_all(MYSQLI_ASSOC);
                        $res->free();
                    }
                    $stmt->close();
                } else {
                    $clientes_error = 'No se pudo leer la lista de clientes.';
                }
            }

            $cliente_edit = null;
            if ($clientes_action === 'edit' && $clientes_id > 0 && $cliente_table) {
                $stmt = $conn->prepare("SELECT `{$cliente_id_field}` AS id, `nombre`, `telefono`, `email`, `direccion`" . ($can_fecha_registro ? ", `fecha_registro`" : "") . " FROM `{$cliente_table}` WHERE `{$cliente_id_field}`=? LIMIT 1");
                if ($stmt) {
                    $stmt->bind_param('i', $clientes_id);
                    $stmt->execute();
                    $res = $stmt->get_result();
                    if ($res) {
                        $cliente_edit = $res->fetch_assoc() ?: null;
                        $res->free();
                    }
                    $stmt->close();
                }
            }
            ?>

            <div class="charts-row">
                <div class="charts-card">
                    <div class="card-header">
                        <div style="display:flex;align-items:center;gap:8px;min-width:0;">
                            <div class="kpi-ico" style="width:28px;height:28px;border-radius:8px;background:#F0F6FC;color:#2b7abc;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <i class="ti ti-users" aria-hidden="true"></i>
                            </div>
                            <div style="min-width:0;">
                                <div class="card-title">Clientes</div>
                                <div class="card-sub">Gestión de clientes registrados</div>
                            </div>
                        </div>
                        <div style="display:flex;gap:8px;align-items:center;">
                            <a class="ordenes-ver-btn" href="?page=clientes&action=new">Nuevo cliente</a>
                        </div>
                    </div>

                    <div style="display:flex;gap:8px;align-items:center;margin-top:10px;flex-wrap:wrap;">
                        <form method="get" style="display:flex;gap:8px;align-items:center;flex:1;min-width:240px;">
                            <input type="hidden" name="page" value="clientes">
                            <div class="topbar-search" style="flex:1;min-width:220px;">
                                <i class="ti ti-search" aria-hidden="true"></i>
                                <input name="q" value="<?= h($clientes_search) ?>" placeholder="Buscar por nombre, teléfono o email" style="border:0;background:transparent;outline:none;font:inherit;color:#4D4841;width:100%;">
                            </div>
                            <button class="ordenes-ver-btn" type="submit">Buscar</button>
                            <?php if ($clientes_search !== ''): ?>
                                <a class="ordenes-ver-btn" href="?page=clientes">Limpiar</a>
                            <?php endif; ?>
                        </form>
                    </div>

                    <?php if ($clientes_error): ?>
                        <div style="margin-top:12px;color:#2b7abc;font-size:12px;"><?= h($clientes_error) ?></div>
                    <?php endif; ?>

                    <?php if ($clientes_action === 'new' || $clientes_action === 'edit'): ?>
                        <div style="margin-top:14px;border-top:0.5px solid #EDECEA;padding-top:14px;display:grid;grid-template-columns:1fr;gap:10px;">
                            <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;">
                                <div style="font-size:12.5px;font-weight:600;color:#1C1A17;">
                                    <?= $clientes_action === 'edit' ? 'Editar cliente' : 'Nuevo cliente' ?>
                                </div>
                                <a class="ordenes-ver-btn" href="?page=clientes">Cerrar</a>
                            </div>

                            <form method="post" style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                                <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>">
                                <input type="hidden" name="clientes_action" value="<?= $clientes_action === 'edit' ? 'update' : 'create' ?>">
                                <?php if ($clientes_action === 'edit'): ?>
                                    <input type="hidden" name="id_cliente" value="<?= (int)($cliente_edit['id'] ?? 0) ?>">
                                <?php endif; ?>

                                <div style="grid-column:span 2;">
                                    <label style="display:block;font-size:10px;color:#6B6560;margin-bottom:4px;">Nombre *</label>
                                    <input name="nombre" required value="<?= h((string)($cliente_edit['nombre'] ?? '')) ?>" style="width:100%;padding:10px 11px;border-radius:8px;border:0.5px solid #D0CCC6;background:#fff;outline:none;">
                                </div>

                                <div>
                                    <label style="display:block;font-size:10px;color:#6B6560;margin-bottom:4px;">Teléfono</label>
                                    <input name="telefono" value="<?= h((string)($cliente_edit['telefono'] ?? '')) ?>" style="width:100%;padding:10px 11px;border-radius:8px;border:0.5px solid #D0CCC6;background:#fff;outline:none;">
                                </div>
                                <div>
                                    <label style="display:block;font-size:10px;color:#6B6560;margin-bottom:4px;">Email</label>
                                    <input name="email" type="email" value="<?= h((string)($cliente_edit['email'] ?? '')) ?>" style="width:100%;padding:10px 11px;border-radius:8px;border:0.5px solid #D0CCC6;background:#fff;outline:none;">
                                </div>

                                <div style="grid-column:span 2;">
                                    <label style="display:block;font-size:10px;color:#6B6560;margin-bottom:4px;">Dirección</label>
                                    <input name="direccion" value="<?= h((string)($cliente_edit['direccion'] ?? '')) ?>" style="width:100%;padding:10px 11px;border-radius:8px;border:0.5px solid #D0CCC6;background:#fff;outline:none;">
                                </div>

                                <div style="grid-column:span 2;display:flex;gap:8px;justify-content:flex-end;">
                                    <button class="ordenes-ver-btn" type="submit" style="background:#2b7abc;border-color:#2b7abc;color:#fff;">Guardar</button>
                                </div>
                            </form>
                        </div>
                    <?php endif; ?>

                    <div style="margin-top:14px;border-top:0.5px solid #EDECEA;padding-top:12px;">
                        <div class="table-head" style="grid-template-columns:50px 1.2fr 0.9fr 1.2fr 1.3fr 120px;">
                            <div>ID</div>
                            <div>Nombre</div>
                            <div>Teléfono</div>
                            <div>Email</div>
                            <div>Dirección</div>
                            <div style="text-align:right;">Acciones</div>
                        </div>
                        <?php if (empty($clientes)): ?>
                            <div style="padding:14px 10px;color:#6B6560;font-size:12px;">No hay clientes registrados.</div>
                        <?php else: ?>
                            <?php foreach ($clientes as $c): ?>
                            <div class="table-row" style="grid-template-columns:50px 1.2fr 0.9fr 1.2fr 1.3fr 120px;">
                                <div class="order-id"><?= (int)$c['id'] ?></div>
                                <div style="min-width:0;">
                                    <div class="order-cliente" style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= h((string)($c['nombre'] ?? '')) ?></div>
                                    <?php if ($can_fecha_registro && !empty($c['fecha_registro'])): ?>
                                        <div class="order-equipo" style="gap:6px;">
                                            <i class="ti ti-calendar" aria-hidden="true"></i>
                                            Registrado: <?= h((string)$c['fecha_registro']) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="order-tecnico"><?= h((string)($c['telefono'] ?? '')) ?></div>
                                <div class="order-tecnico" style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= h((string)($c['email'] ?? '')) ?></div>
                                <div class="order-tecnico" style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= h((string)($c['direccion'] ?? '')) ?></div>
                                <div style="display:flex;gap:6px;justify-content:flex-end;align-items:center;">
                                    <a class="ordenes-ver-btn" href="<?= h('?page=clientes&action=edit&id=' . (int)$c['id']) ?>">Editar</a>
                                    <form method="post" onsubmit="return confirm('¿Eliminar este cliente?');" style="display:inline;">
                                        <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>">
                                        <input type="hidden" name="clientes_action" value="delete">
                                        <input type="hidden" name="id_cliente" value="<?= (int)$c['id'] ?>">
                                        <button class="ordenes-ver-btn" type="submit" style="background:#e8f2fb;border-color:#a8cef0;color:#2b7abc;">Eliminar</button>
                                    </form>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                </div>
            </div>

        <?php else: ?>
            <?php
            $page_file = __DIR__ . '/src/pages/' . $current_page . '.php';
            if (is_file($page_file)) {
                include $page_file;
            } else {
                echo '<div class="charts-card">Módulo en construcción</div>';
            }
            ?>
        <?php endif; ?>
    </div><!-- /content -->
</main><!-- /main -->


<!-- ════════════════════════════════════════════════
     Chart.js
═════════════════════════════════════════════════ -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.js"></script>
<script>
(function () {
    'use strict';
    const tecnicoLabels = <?= json_encode($chart_tecnico_labels ?: ['Sin datos'], JSON_UNESCAPED_UNICODE) ?>;
    const tecnicoReparaciones = <?= json_encode($chart_tecnico_reparaciones ?: [0], JSON_UNESCAPED_UNICODE) ?>;
    const tecnicoIngresos = <?= json_encode($chart_tecnico_ingresos ?: [0], JSON_UNESCAPED_UNICODE) ?>;
    const estadoLabels = <?= json_encode($chart_estado_labels ?: ['Sin datos'], JSON_UNESCAPED_UNICODE) ?>;
    const estadoValues = <?= json_encode($chart_estado_values ?: [0], JSON_UNESCAPED_UNICODE) ?>;
    const estadoColors = <?= json_encode($chart_estado_colors ?: ['#D0CCC6'], JSON_UNESCAPED_UNICODE) ?>;
    const barChart = document.getElementById('barChart');
    const donutChart = document.getElementById('donutChart');

    // ── Bar chart ──────────────────────────────────────
    if (barChart) new Chart(barChart, {
        type: 'bar',
        data: {
            labels: tecnicoLabels,
            datasets: [
                {
                    label: 'Reparaciones',
                    data: tecnicoReparaciones,
                    backgroundColor: '#2b7abc',
                    borderRadius: 4,
                    barPercentage: 0.55,
                    categoryPercentage: 0.8,
                },
                {
                    label: 'Ingresos ($100s)',
                    data: tecnicoIngresos,
                    backgroundColor: '#5BA3FF',
                    borderRadius: 4,
                    barPercentage: 0.55,
                    categoryPercentage: 0.8,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    bodyFont: { size: 12 },
                    titleFont: { size: 12 },
                    padding: 10,
                },
            },
            scales: {
                x: {
                    ticks: { font: { size: 12 }, color: '#6B6560' },
                    grid:  { display: false },
                    border: { display: false },
                },
                y: {
                    ticks: { font: { size: 10 }, color: '#8C8479', stepSize: 5 },
                    grid:  { color: 'rgba(0,0,0,0.04)' },
                    border: { display: false },
                    max: 28, min: 0,
                },
            },
        },
    });

    // ── Donut chart ────────────────────────────────────
    if (donutChart) new Chart(donutChart, {
        type: 'doughnut',
        data: {
            labels: estadoLabels,
            datasets: [{
                data: estadoValues,
                backgroundColor: estadoColors,
                borderWidth: 3,
                borderColor: '#FFFFFF',
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '70%',
            plugins: {
                legend: { display: false },
                tooltip: {
                    bodyFont: { size: 12 },
                    titleFont: { size: 12 },
                    padding: 10,
                    callbacks: {
                        label: function (ctx) {
                            return ' ' + ctx.label + ': ' + ctx.parsed + '%';
                        },
                    },
                },
            },
        },
    });
})();
</script>
<script>
(function () {
    'use strict';
    var root = document.documentElement;
    if (localStorage.getItem('repairly_dark') === '1') {
        root.classList.add('theme-dark');
    }
    var no = document.getElementById('night-overlay');
    if (no && localStorage.getItem('repairly_night') === '1') {
        no.classList.add('on');
    }
    var btn = document.getElementById('notif-toggle');
    var menu = document.getElementById('notif-menu');
    if (btn && menu) {
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            var open = menu.classList.toggle('open');
            btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
        document.addEventListener('click', function () {
            menu.classList.remove('open');
            btn.setAttribute('aria-expanded', 'false');
        });
        menu.addEventListener('click', function (e) {
            e.stopPropagation();
        });
    }
})();
</script>

<!-- Script de Select Searchable -->
<script src="public/searchable-select.js"></script>

<!-- Filtrado en vivo de búsqueda -->
<script>
(function(){
    // Esperar a que el DOM esté listo
    document.addEventListener('DOMContentLoaded', function(){
        // Encontrar todos los inputs de búsqueda que contengan "search" o "filtro" en su name
        var searchInputs = document.querySelectorAll('input[name*="q"], input[name*="search"], input[name*="filtro"], .topbar-search input');
        
        searchInputs.forEach(function(input){
            // Agregar evento de entrada para filtrar en tiempo real
            input.addEventListener('input', function(e){
                var query = e.target.value.toLowerCase();
                var form = e.target.closest('form');
                
                if(!form) return;
                
                // Buscar tabla de registros en el mismo contenedor
                var container = form.closest('.charts-card') || form.closest('div[class*="card"]') || document.body;
                var rows = container.querySelectorAll('.table-row');
                var noResultsMsg = container.querySelector('.no-results-msg');
                var visibleCount = 0;
                
                rows.forEach(function(row){
                    var text = row.textContent.toLowerCase();
                    if(text.includes(query)){
                        row.style.display = '';
                        visibleCount++;
                    } else {
                        row.style.display = 'none';
                    }
                });
                
                // Mostrar mensaje si no hay resultados
                if(visibleCount === 0 && rows.length > 0){
                    if(!noResultsMsg){
                        noResultsMsg = document.createElement('div');
                        noResultsMsg.className = 'no-results-msg';
                        noResultsMsg.style.cssText = 'padding:20px;text-align:center;color:#6B6560;font-size:12px;';
                        noResultsMsg.textContent = 'No se encontraron resultados';
                        rows[0].parentNode.appendChild(noResultsMsg);
                    }
                    noResultsMsg.style.display = 'block';
                } else if(noResultsMsg){
                    noResultsMsg.style.display = 'none';
                }
            });
        });
    });
})();
</script>
</body>
</html>
