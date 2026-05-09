<?php
ob_start();

ini_set('display_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

require_once __DIR__ . '/src/config/database.php';

function h(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
}

function require_csrf(): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }
    $token = $_POST['csrf'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['csrf'] ?? '', $token)) {
        http_response_code(400);
        die('Solicitud inválida (CSRF).');
    }
}

function table_exists(mysqli $conn, string $name): bool {

    $name = trim($name);

    $sql = "
        SELECT COUNT(*)
        FROM information_schema.tables
        WHERE table_schema = DATABASE()
        AND LOWER(table_name) = LOWER(?)
    ";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param('s', $name);
    $stmt->execute();

    $stmt->bind_result($count);
    $stmt->fetch();

    $stmt->close();

    return $count > 0;
}

function pick_table(mysqli $conn, array $candidates): string {

    $tables = [];

    $res = $conn->query("SHOW TABLES");

    if ($res) {
        while ($row = $res->fetch_array()) {
            $tables[] = strtolower($row[0]);
        }
    }

    foreach ($candidates as $candidate) {

        $candidate = strtolower(trim($candidate));

        foreach ($tables as $table) {

            if ($table === $candidate) {
                return $table;
            }

            // búsqueda flexible
            if (str_contains($table, $candidate)) {
                return $table;
            }
        }
    }

    return '';
}
// ============================================================
//  RepairlyRD — Dashboard Principal
//  Paleta oficial según guía de identidad visual v1.0
// ============================================================

// ── Datos del sistema (en producción vendrían de la BD) ─────
$usuario = [
    'nombre'   => 'Carlos Jerez',
    'iniciales'=> 'CJ',
    'email'    => 'carlos.jerez@fixmaster.com',
    'rol'      => 'Administrador',
];

$kpis = [
    [
        'clave'     => 'en_proceso',
        'label'     => 'En proceso',
        'valor'     => 0,
        'sub'       => 'órdenes activas',
        'icono'     => 'ti-loader',
        'color'     => '#0052CC',
        'bg'        => '#E3F2FD',
        'texto'     => '#0052CC',
    ],
    [
        'clave'     => 'pendientes',
        'label'     => 'Pendientes',
        'valor'     => 0,
        'sub'       => 'sin completar',
        'icono'     => 'ti-clock',
        'color'     => '#FF9500',
        'bg'        => '#FFF3E0',
        'texto'     => '#FF9500',
    ],
    [
        'clave'     => 'ingresos',
        'label'     => 'Ingresos hoy',
        'valor'     => '$0.00',
        'sub'       => '<span style="color:#00AA44;display:flex;align-items:center;gap:3px;"><i class="ti ti-trending-up" style="font-size:11px;"></i>0% vs ayer</span>',
        'icono'     => 'ti-cash',
        'color'     => '#00AA44',
        'bg'        => '#E8F5E9',
        'texto'     => '#00AA44',
    ],
    [
        'clave'     => 'garantias',
        'label'     => 'Garantías',
        'valor'     => 0,
        'sub'       => 'activas ahora',
        'icono'     => 'ti-shield',
        'color'     => '#7B4EC4',
        'bg'        => '#F3E5F5',
        'texto'     => '#7B4EC4',
    ],
    [
        'clave'     => 'completadas',
        'label'     => 'Completadas',
        'valor'     => 0,
        'sub'       => 'servicios este mes',
        'icono'     => 'ti-checks',
        'color'     => '#424242',
        'bg'        => '#F5F5F5',
        'texto'     => '#424242',
    ],
    [
        'clave'     => 'con_falla',
        'label'     => 'Con falla',
        'valor'     => 0,
        'sub'       => 'requieren atención',
        'icono'     => 'ti-alert-triangle',
        'color'     => '#FF4444',
        'bg'        => '#FFEBEE',
        'texto'     => '#FF4444',
    ],
];

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
];
$page = $page_meta[$current_page] ?? $page_meta['dashboard'];

require_once __DIR__ . '/includes/crud_clientes.php';
require_once __DIR__ . '/includes/crud_equipos.php';
require_once __DIR__ . '/includes/crud_ordenes.php';
require_once __DIR__ . '/includes/crud_tecnicos.php';


function table_columns(mysqli $conn, string $table): array {
    $cols = [];
    $res = $conn->query("SHOW COLUMNS FROM `{$table}`");
    if (!$res) {
        return $cols;
    }
    while ($row = $res->fetch_assoc()) {
        if (!empty($row['Field'])) {
            $cols[$row['Field']] = true;
        }
    }
    $res->free();
    return $cols;
}

function db_scalar(mysqli $conn, string $sql, int|float|string $default = 0): int|float|string {
    $res = $conn->query($sql);
    if (!$res) {
        return $default;
    }
    $row = $res->fetch_row();
    $res->free();
    return $row[0] ?? $default;
}

function db_rows(mysqli $conn, string $sql): array {
    $res = $conn->query($sql);
    if (!$res) {
        return [];
    }
    $rows = $res->fetch_all(MYSQLI_ASSOC);
    $res->free();
    return $rows;
}

function status_palette(string $status): array {
    $name = strtolower($status);
    if (str_contains($name, 'falla')) {
        return ['bg' => '#FFEBEE', 'color' => '#FF4444', 'border' => '#FF4444', 'dot' => '#FF4444'];
    }
    if (str_contains($name, 'garant')) {
        return ['bg' => '#F3E5F5', 'color' => '#7B4EC4', 'border' => '#7B4EC4', 'dot' => '#7B4EC4'];
    }
    if (str_contains($name, 'listo') || str_contains($name, 'entreg') || str_contains($name, 'complet')) {
        return ['bg' => '#E8F5E9', 'color' => '#00AA44', 'border' => '#00AA44', 'dot' => '#00AA44'];
    }
    if (str_contains($name, 'pend') || str_contains($name, 'recib')) {
        return ['bg' => '#FFF3E0', 'color' => '#FF9500', 'border' => '#FF9500', 'dot' => '#FF9500'];
    }
    if (str_contains($name, 'diagn')) {
        return ['bg' => '#F5F5F5', 'color' => '#424242', 'border' => '#424242', 'dot' => '#424242'];
    }
    return ['bg' => '#E3F2FD', 'color' => '#0052CC', 'border' => '#0052CC', 'dot' => '#0052CC'];
}

function device_icon(string $type): string {
    $name = strtolower($type);
    if (str_contains($name, 'phone') || str_contains($name, 'tel')) {
        return 'ti-device-mobile';
    }
    if (str_contains($name, 'tablet') || str_contains($name, 'ipad')) {
        return 'ti-device-tablet';
    }
    if (str_contains($name, 'pc') || str_contains($name, 'torre') || str_contains($name, 'desktop')) {
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
        'est_color'=> '#0052CC',
        'est_borde'=> '#0052CC',
        'est_dot'  => '#0052CC',
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
switch($page){

    case 'clientes':
        include 'pages/clientes.php';
        break;

    case 'equipos':
        include 'pages/equipos.php';
        break;

    case 'tecnicos':
        include 'pages/tecnicos.php';
        break;

    case 'ordenes':
        include 'pages/ordenes.php';
        break;

    default:
        include 'pages/dashboard.php';
}
$dispositivos = [
    ['tipo'=>'Teléfonos',  'icono'=>'ti-device-mobile',  'pct'=>45, 'color'=>'#0052CC', 'bg'=>'#E3F2FD',  'tc'=>'#0052CC'],
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
    'Orden_Reparacion'
  ]),

    'estado' => pick_table($conn, [
        'estado',
        'estado_servicio'
    ]),

    'garantia' => pick_table($conn, [
        'garantia'
    ]),
];

$has_dashboard_core = table_exists($conn, $dashboard_tables['orden'])
    && table_exists($conn, $dashboard_tables['estado'])
    && table_exists($conn, $dashboard_tables['equipo']);

$chart_tecnico_labels = [];
$chart_tecnico_reparaciones = [];
$chart_tecnico_ingresos = [];
$chart_estado_labels = [];
$chart_estado_values = [];
$chart_estado_colors = [];

if ($has_dashboard_core) {
    $orden_table = $dashboard_tables['orden'];
    $estado_table = $dashboard_tables['estado'];
    $equipo_table = $dashboard_tables['equipo'];
    $cliente_table_dashboard = $dashboard_tables['cliente'];
    $tecnico_table = $dashboard_tables['tecnico'];
    $garantia_table = $dashboard_tables['garantia'];

    $estado_join = "FROM `{$orden_table}` o LEFT JOIN `{$estado_table}` s ON s.id_estado = o.id_estado_actual";
    $estado_expr = "LOWER(COALESCE(s.nombre_estado, ''))";

    $en_proceso = (int)db_scalar($conn, "SELECT COUNT(*) {$estado_join} WHERE {$estado_expr} LIKE '%proceso%' OR {$estado_expr} LIKE '%repar%'");
    $pendientes = (int)db_scalar($conn, "SELECT COUNT(*) {$estado_join} WHERE {$estado_expr} LIKE '%pend%' OR {$estado_expr} LIKE '%recib%'");
    $con_falla = (int)db_scalar($conn, "SELECT COUNT(*) {$estado_join} WHERE {$estado_expr} LIKE '%falla%'");
    $completadas = (int)db_scalar($conn, "SELECT COUNT(*) {$estado_join} WHERE ({$estado_expr} LIKE '%entreg%' OR {$estado_expr} LIKE '%complet%' OR {$estado_expr} LIKE '%listo%') AND MONTH(COALESCE(o.fecha_entrega_real, o.fecha_actualizacion, o.fecha_ingreso)) = MONTH(CURDATE()) AND YEAR(COALESCE(o.fecha_entrega_real, o.fecha_actualizacion, o.fecha_ingreso)) = YEAR(CURDATE())");
    $ingresos_hoy = (float)db_scalar($conn, "SELECT COALESCE(SUM(o.costo_total), 0) FROM `{$orden_table}` o WHERE DATE(COALESCE(o.fecha_entrega_real, o.fecha_actualizacion, o.fecha_ingreso)) = CURDATE()", 0);
    $ingresos_ayer = (float)db_scalar($conn, "SELECT COALESCE(SUM(o.costo_total), 0) FROM `{$orden_table}` o WHERE DATE(COALESCE(o.fecha_entrega_real, o.fecha_actualizacion, o.fecha_ingreso)) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)", 0);
    $garantias_activas = table_exists($conn, $garantia_table)
        ? (int)db_scalar($conn, "SELECT COUNT(*) FROM `{$garantia_table}` WHERE (LOWER(COALESCE(estado, '')) LIKE '%activ%' OR (CURDATE() BETWEEN fecha_inicio AND fecha_fin))")
        : 0;

    $trend = $ingresos_ayer > 0 ? (($ingresos_hoy - $ingresos_ayer) / $ingresos_ayer) * 100 : ($ingresos_hoy > 0 ? 100 : 0);
    $trend_color = $trend >= 0 ? '#00AA44' : '#B83232';
    $trend_icon = $trend >= 0 ? 'ti-trending-up' : 'ti-trending-down';

    $kpis = [
        ['clave' => 'en_proceso', 'label' => 'En proceso', 'valor' => $en_proceso, 'sub' => 'órdenes activas', 'icono' => 'ti-loader', 'color' => '#0052CC', 'bg' => '#E3F2FD', 'texto' => '#0052CC'],
        ['clave' => 'pendientes', 'label' => 'Pendientes', 'valor' => $pendientes, 'sub' => 'sin completar', 'icono' => 'ti-clock', 'color' => '#FF9500', 'bg' => '#FFF3E0', 'texto' => '#FF9500'],
        ['clave' => 'ingresos', 'label' => 'Ingresos hoy', 'valor' => '$' . number_format($ingresos_hoy, 2), 'sub' => '<span style="color:' . $trend_color . ';display:flex;align-items:center;gap:3px;"><i class="ti ' . $trend_icon . '" style="font-size:11px;"></i>' . number_format(abs($trend), 0) . '% vs ayer</span>', 'icono' => 'ti-cash', 'color' => '#00AA44', 'bg' => '#E8F5E9', 'texto' => '#00AA44'],
        ['clave' => 'garantias', 'label' => 'Garantías', 'valor' => $garantias_activas, 'sub' => 'activas ahora', 'icono' => 'ti-shield', 'color' => '#7B4EC4', 'bg' => '#F3E5F5', 'texto' => '#7B4EC4'],
        ['clave' => 'completadas', 'label' => 'Completadas', 'valor' => $completadas, 'sub' => 'servicios este mes', 'icono' => 'ti-checks', 'color' => '#424242', 'bg' => '#F5F5F5', 'texto' => '#424242'],
        ['clave' => 'con_falla', 'label' => 'Con falla', 'valor' => $con_falla, 'sub' => 'requieren atención', 'icono' => 'ti-alert-triangle', 'color' => '#FF4444', 'bg' => '#FFEBEE', 'texto' => '#FF4444'],
    ];

    $ordenes_recientes = [];
    $ordenes_sql = "SELECT o.id_orden, COALESCE(c.nombre, 'Cliente no asignado') AS cliente, COALESCE(e.tipo, '') AS tipo, COALESCE(e.marca, '') AS marca, COALESCE(e.modelo, '') AS modelo, COALESCE(t.nombre, 'Sin técnico') AS tecnico, COALESCE(s.nombre_estado, 'Sin estado') AS estado, COALESCE(o.costo_total, 0) AS costo_total FROM `{$orden_table}` o LEFT JOIN `{$equipo_table}` e ON e.id_equipo = o.id_equipo LEFT JOIN `{$cliente_table_dashboard}` c ON c.id_cliente = e.id_cliente LEFT JOIN `{$tecnico_table}` t ON t.id_tecnico = o.id_tecnico LEFT JOIN `{$estado_table}` s ON s.id_estado = o.id_estado_actual ORDER BY COALESCE(o.fecha_actualizacion, o.fecha_creacion, o.fecha_ingreso) DESC, o.id_orden DESC LIMIT 6";
    foreach (db_rows($conn, $ordenes_sql) as $row) {
        $status = (string)$row['estado'];
        $palette = status_palette($status);
        $tipo = trim((string)$row['tipo']);
        $equipo_label = trim($tipo . ' ' . (string)$row['marca'] . ' ' . (string)$row['modelo']);
        $ordenes_recientes[] = ['id' => (string)$row['id_orden'], 'cliente' => (string)$row['cliente'], 'equipo' => $equipo_label !== '' ? $equipo_label : 'Equipo sin detalle', 'icono_eq' => device_icon($tipo), 'tecnico' => (string)$row['tecnico'], 'estado' => $status, 'est_bg' => $palette['bg'], 'est_color'=> $palette['color'], 'est_borde'=> $palette['border'], 'est_dot' => $palette['dot'], 'valor' => '$' . number_format((float)$row['costo_total'], 2)];
    }

    $device_rows = db_rows($conn, "SELECT COALESCE(NULLIF(TRIM(tipo), ''), 'Sin tipo') AS tipo, COUNT(*) AS total FROM `{$equipo_table}` GROUP BY COALESCE(NULLIF(TRIM(tipo), ''), 'Sin tipo') ORDER BY total DESC LIMIT 5");
    $device_total = array_sum(array_map(fn($row) => (int)$row['total'], $device_rows));
    $device_colors = ['#0052CC', '#00AA44', '#FF9500', '#7B4EC4', '#424242'];
    $device_bgs = ['#E3F2FD', '#E8F5E9', '#FFF3E0', '#F3E5F5', '#F5F5F5'];
    $dispositivos = [];
    foreach ($device_rows as $idx => $row) {
        $color = $device_colors[$idx % count($device_colors)];
        $dispositivos[] = ['tipo' => (string)$row['tipo'], 'icono' => device_icon((string)$row['tipo']), 'pct' => $device_total > 0 ? (int)round(((int)$row['total'] / $device_total) * 100) : 0, 'color' => $color, 'bg' => $device_bgs[$idx % count($device_bgs)], 'tc' => $color];
    }

    foreach (db_rows($conn, "SELECT COALESCE(t.nombre, 'Sin técnico') AS tecnico, COUNT(o.id_orden) AS reparaciones, COALESCE(SUM(o.costo_total), 0) AS ingresos FROM `{$orden_table}` o LEFT JOIN `{$tecnico_table}` t ON t.id_tecnico = o.id_tecnico WHERE MONTH(COALESCE(o.fecha_entrega_real, o.fecha_actualizacion, o.fecha_ingreso)) = MONTH(CURDATE()) AND YEAR(COALESCE(o.fecha_entrega_real, o.fecha_actualizacion, o.fecha_ingreso)) = YEAR(CURDATE()) GROUP BY COALESCE(t.nombre, 'Sin técnico') ORDER BY reparaciones DESC LIMIT 6") as $row) {
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
}

$fallas_urgentes = array_filter($ordenes_recientes, fn($o) => strtolower($o['estado']) === 'con falla' || str_contains(strtolower($o['estado']), 'falla'));
$total_fallas    = count($fallas_urgentes);

foreach ($nav_items as &$nav_item) {
    if (($nav_item['key'] ?? '') === 'garantias' && !empty($nav_item['badge'])) {
        $nav_item['badge']['valor'] = (int)($kpis[3]['valor'] ?? 0);
    }
}
unset($nav_item);

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
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ReparlyRD — Dashboard</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.19.0/dist/tabler-icons.min.css">
<style>
/* ── Reset ── */
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html{font-size:14px}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',system-ui,sans-serif;background:#F8F7F5;color:#1C1A17;height:100vh;display:flex;overflow:hidden;}

/* ── Sidebar ── */
.sidebar{width:190px;min-height:100vh;background:#0A2540;display:flex;flex-direction:column;flex-shrink:0;position:sticky;top:0;height:100vh;overflow-y:auto}
.sidebar-logo{padding:16px 14px 12px;border-bottom:0.5px solid rgba(255,255,255,0.08);display:flex;align-items:center;gap:9px}
.sidebar-logo-icon{width:30px;height:30px;background:#1F5C8B;border-radius:6px;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.sidebar-logo-icon i{color:#fff;font-size:14px}
.sidebar-logo-text{color:#fff;font-size:14px;font-weight:500;line-height:1.2}
.sidebar-logo-ver{color:rgba(255,255,255,0.35);font-size:9px;letter-spacing:0.05em}
.sidebar-nav{padding:10px 8px;flex:1}
.nav-section{font-size:8.5px;color:rgba(255,255,255,0.3);text-transform:uppercase;letter-spacing:0.1em;padding:10px 8px 6px}
.nav-section:first-child{padding-top:2px}
.nav-item{display:flex;align-items:center;gap:8px;padding:8px 10px;border-radius:7px;cursor:pointer;transition:background 0.15s;text-decoration:none;margin-bottom:1px}
.nav-item:hover{background:rgba(255,255,255,0.07)}
.nav-item.active{background:#1F5C8B}
.nav-item i{color:rgba(255,255,255,0.55);font-size:16px;flex-shrink:0}
.nav-item.active i{color:#fff}
.nav-item span{color:rgba(255,255,255,0.65);font-size:12.5px}
.nav-item.active span{color:#fff}
.nav-badge{margin-left:auto;font-size:9px;font-weight:600;padding:2px 6px;border-radius:10px;line-height:1.4}
.nav-dot{margin-left:auto;width:7px;height:7px;border-radius:50%}
.sidebar-user{padding:12px 14px;border-top:0.5px solid rgba(255,255,255,0.08);display:flex;align-items:center;gap:8px}
.user-avatar{width:28px;height:28px;border-radius:50%;background:#1F5C8B;display:flex;align-items:center;justify-content:center;font-size:10.5px;color:#fff;font-weight:600;flex-shrink:0}
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
.notif-dot{position:absolute;top:6px;right:6px;width:7px;height:7px;border-radius:50%;background:#B83232;border:1.5px solid #fff}
.topbar-date{font-size:11px;color:#6B6560;background:#F8F7F5;border:0.5px solid #D0CCC6;border-radius:7px;padding:6px 9px;white-space:nowrap;display:flex;align-items:center;gap:5px}
.topbar-date i{font-size:12px}

/* ── Contenido ── */
.content{padding:16px 20px;flex:1}

/* ── Alerta urgente ── */
.alert-falla{background:#FDF0F0;border:0.5px solid #B83232;border-radius:8px;padding:9px 14px;display:flex;align-items:center;gap:9px;margin-bottom:16px}
.alert-falla i{font-size:16px;color:#B83232;flex-shrink:0}
.alert-falla-txt{font-size:12px;color:#5C1414;font-weight:500}
.alert-falla-btn{margin-left:auto;font-size:11px;padding:4px 10px;border-radius:5px;background:#fff;border:0.5px solid #B83232;color:#B83232;cursor:pointer;transition:background 0.15s;text-decoration:none;white-space:nowrap}
.alert-falla-btn:hover{background:#FDF0F0}

/* ── KPI Grid ── */
.kpi-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:10px}
.kpi-grid:last-of-type{margin-bottom:18px}
.kpi-card{border-radius:10px;border:none;padding:16px;box-shadow:0 2px 8px rgba(0,0,0,0.08);transition:transform 0.2s,box-shadow 0.2s;position:relative;overflow:hidden}
.kpi-card::before{content:'';position:absolute;top:0;left:0;width:100%;height:3px;background:var(--kpi-color,#0052CC);opacity:0.8}
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
.table-head{display:grid;grid-template-columns:42px 1fr 75px 80px 65px;gap:8px;padding:7px 15px;background:#EDECEA;font-size:9.5px;font-weight:500;color:#6B6560;text-transform:uppercase;letter-spacing:0.07em}
.table-row{display:grid;grid-template-columns:42px 1fr 75px 80px 65px;gap:8px;padding:9px 15px;border-bottom:0.5px solid #EDECEA;align-items:center;transition:background 0.1s}
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
    .sidebar-logo{padding:14px;justify-content:center}
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
</style>
</head>
<body>

<!-- ════════════════════════════════════════════════
     SIDEBAR
═════════════════════════════════════════════════ -->
<aside class="sidebar" role="navigation" aria-label="Navegación principal">

    <div class="sidebar-logo">
        <div class="sidebar-logo-icon" aria-hidden="true">
            <i class="ti ti-tool"></i>
        </div>
        <div>
            <div class="sidebar-logo-text">RepairlyRD</div>
            <div class="sidebar-logo-ver">ERP</div>
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
        <i class="ti ti-logout user-logout" title="Cerrar sesión" aria-label="Cerrar sesión"></i>
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
            <div class="topbar-search" role="search">
                <i class="ti ti-search" aria-hidden="true"></i>
                Buscar orden...
            </div>
            <div class="topbar-btn" title="Notificaciones" role="button" aria-label="Notificaciones">
                <i class="ti ti-bell" aria-hidden="true"></i>
                <?php if ($total_fallas > 0): ?>
                    <span class="notif-dot" aria-label="Hay notificaciones nuevas"></span>
                <?php endif; ?>
            </div>
            <div class="topbar-date">
                <i class="ti ti-calendar" aria-hidden="true"></i>
                <?= htmlspecialchars($fecha_es) ?>
            </div>
        </div>
    </header>


    <!-- ── Contenido ── -->
    <div class="content">
        <?php if ($current_page === 'dashboard'): ?>

        <?php if (!$has_dashboard_core): ?>
        <div class="alert-falla" role="alert" style="background:#FFF3E0;border-color:#FF9500;">
            <i class="ti ti-database-alert" aria-hidden="true" style="color:#FF9500;"></i>
            <span class="alert-falla-txt" style="color:#5C3E00;">
                El dashboard está conectado, pero no encontró las tablas base: orden_reparacion, estado_servicio y equipo.
            </span>
        </div>
        <?php endif; ?>

        <!-- Alerta de fallas urgentes -->
        <?php if ($total_fallas > 0): ?>
        <div class="alert-falla" role="alert">
            <i class="ti ti-alert-triangle" aria-hidden="true"></i>
            <span class="alert-falla-txt">
                <?= $total_fallas ?> equipo<?= $total_fallas > 1 ? 's' : '' ?>
                con falla requiere<?= $total_fallas === 1 ? '' : 'n' ?> atención inmediata
            </span>
            <a href="#ordenes" class="alert-falla-btn">Revisar &rarr;</a>
        </div>
        <?php endif; ?>

        <!-- KPI row 1 -->
        <div class="kpi-grid">
            <?php foreach (array_slice($kpis, 0, 3) as $k): ?>
            <div class="kpi-card"
                 style="background:<?= $k['bg'] ?>;--kpi-color:<?= $k['color'] ?>;">
                <div class="kpi-label" style="color:<?= $k['color'] ?>;">
                    <i class="ti <?= htmlspecialchars($k['icono']) ?>" aria-hidden="true"></i>
                    <?= htmlspecialchars($k['label']) ?>
                </div>
                <div class="kpi-valor" style="color:<?= $k['texto'] ?>;">
                    <?= htmlspecialchars((string)$k['valor']) ?>
                </div>
                <div class="kpi-sub"><?= $k['sub'] /* puede contener HTML */ ?></div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- KPI row 2 -->
        <div class="kpi-grid">
            <?php foreach (array_slice($kpis, 3, 3) as $k): ?>
            <div class="kpi-card"
                 style="background:<?= $k['bg'] ?>;--kpi-color:<?= $k['color'] ?>;">
                <div class="kpi-label" style="color:<?= $k['color'] ?>;">
                    <i class="ti <?= htmlspecialchars($k['icono']) ?>" aria-hidden="true"></i>
                    <?= htmlspecialchars($k['label']) ?>
                </div>
                <div class="kpi-valor" style="color:<?= $k['texto'] ?>;">
                    <?= htmlspecialchars((string)$k['valor']) ?>
                </div>
                <div class="kpi-sub"><?= $k['sub'] ?></div>
            </div>
            <?php endforeach; ?>
        </div>


        <!-- ── Charts ── -->
        <div class="charts-row">

            <!-- Bar chart -->
            <div class="card">
                <div class="card-header">
                    <span class="card-title">Productividad por técnico</span>
                    <span class="card-pill">Mayo <?= date('Y') ?></span>
                </div>
                <div class="chart-legend">
                    <span class="legend-item">
                        <span class="legend-sq" style="background:#0052CC;"></span>
                        Reparaciones
                    </span>
                    <span class="legend-item">
                        <span class="legend-sq" style="background:#5BA3FF;"></span>
                        Ingresos ($100s)
                    </span>
                </div>
                <div class="chart-wrap" style="height:180px;">
                    <canvas id="barChart"
                            role="img"
                            aria-label="Productividad por técnico desde la base de datos">
                        Productividad por técnico desde la base de datos
                    </canvas>
                </div>
            </div>

            <!-- Donut chart -->
            <div class="card">
                <div class="card-header">
                    <span class="card-title">Distribución de estados</span>
                </div>
                <div class="chart-wrap" style="height:145px;">
                    <canvas id="donutChart"
                            role="img"
                            aria-label="Distribución de estados desde la base de datos">
                        Distribución de estados desde la base de datos
                    </canvas>
                </div>
                <div class="donut-legend">
                    <?php foreach ($chart_estado_labels as $idx => $label): ?>
                    <span class="donut-legend-item">
                        <span class="donut-dot" style="background:<?= h($chart_estado_colors[$idx] ?? '#424242') ?>;"></span>
                        <?= h((string)$label) ?> <?= (int)($chart_estado_values[$idx] ?? 0) ?>%
                    </span>
                    <?php endforeach; ?>
                    <?php if (empty($chart_estado_labels)): ?>
                    <span class="donut-legend-item"><span class="donut-dot" style="background:#D0CCC6;"></span>Sin datos 0%</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>


        <!-- ── Fila inferior ── -->
        <div class="bottom-row">

            <!-- Dispositivos -->
            <div class="dispositivos-card">
                <div class="card-title" style="margin-bottom:14px;">Por tipo de dispositivo</div>
                <?php if (empty($dispositivos)): ?>
                <div style="font-size:11.5px;color:#6B6560;line-height:1.5;">No hay equipos registrados en la base de datos.</div>
                <?php endif; ?>
                <?php foreach ($dispositivos as $d): ?>
                <div class="dispositivo-item">
                    <div class="dispositivo-header">
                        <div class="dispositivo-info">
                            <div class="dispositivo-icon"
                                 style="background:<?= $d['bg'] ?>;color:<?= $d['tc'] ?>;">
                                <i class="ti <?= htmlspecialchars($d['icono']) ?>" aria-hidden="true"></i>
                            </div>
                            <span class="dispositivo-nombre"><?= htmlspecialchars($d['tipo']) ?></span>
                        </div>
                        <span class="dispositivo-pct"><?= $d['pct'] ?>%</span>
                    </div>
                    <div class="barra-track">
                        <div class="barra-fill"
                             style="width:<?= $d['pct'] ?>%;background:<?= $d['color'] ?>;"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>


            <!-- Tabla de órdenes -->
            <div class="ordenes-card" id="ordenes">
                <div class="ordenes-header">
                    <span class="card-title">Órdenes recientes</span>
                    <a href="?page=ordenes" class="ordenes-ver-btn">Ver todas &rarr;</a>
                </div>
                <div class="table-head" role="row" aria-label="Encabezados de tabla">
                    <div>#</div>
                    <div>Cliente / Equipo</div>
                    <div class="col-tecnico">Técnico</div>
                    <div>Estado</div>
                    <div style="text-align:right;">Valor</div>
                </div>
                <div role="list" aria-label="Órdenes recientes">
                <?php foreach ($ordenes_recientes as $orden): ?>
                <div class="table-row" role="listitem">
                    <div class="order-id"><?= htmlspecialchars($orden['id']) ?></div>
                    <div>
                        <div class="order-cliente"><?= htmlspecialchars($orden['cliente']) ?></div>
                        <div class="order-equipo">
                            <i class="ti <?= htmlspecialchars($orden['icono_eq']) ?>" aria-hidden="true"></i>
                            <?= htmlspecialchars($orden['equipo']) ?>
                        </div>
                    </div>
                    <div class="order-tecnico col-tecnico"><?= htmlspecialchars($orden['tecnico']) ?></div>
                    <div>
                        <span class="status-badge"
                              style="background:<?= $orden['est_bg'] ?>;color:<?= $orden['est_color'] ?>;border-color:<?= $orden['est_borde'] ?>;">
                            <span class="status-dot" style="background:<?= $orden['est_dot'] ?>;"></span>
                            <?= htmlspecialchars($orden['estado']) ?>
                        </span>
                    </div>
                    <div class="order-valor"><?= htmlspecialchars($orden['valor']) ?></div>
                </div>
                <?php endforeach; ?>
                </div>
            </div>

        </div><!-- /bottom-row -->
        <?php else: ?>

        <?php if ($flash['msg']): ?>
            <div class="alert-falla" role="status" style="background:#fff;border-color:#D0CCC6;">
                <i class="ti <?= $flash['type'] === 'ok' ? 'ti-circle-check' : 'ti-alert-triangle' ?>" aria-hidden="true" style="color:<?= $flash['type'] === 'ok' ? '#1A7A4A' : '#B83232' ?>;"></i>
                <span class="alert-falla-txt" style="color:#322F2A;"><?= h($flash['msg']) ?></span>
                <a href="<?= h('?page=' . $current_page) ?>" class="alert-falla-btn" style="background:#F8F7F5;border-color:#D0CCC6;color:#4D4841;">OK</a>
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
                            <div class="kpi-ico" style="width:28px;height:28px;border-radius:8px;background:#F0F6FC;color:#1F5C8B;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
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
                        <div style="margin-top:12px;color:#B83232;font-size:12px;"><?= h($clientes_error) ?></div>
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
                                    <button class="ordenes-ver-btn" type="submit" style="background:#1F5C8B;border-color:#1F5C8B;color:#fff;">Guardar</button>
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
                                        <button class="ordenes-ver-btn" type="submit" style="background:#FDF0F0;border-color:#F5C2C2;color:#B83232;">Eliminar</button>
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
            if (file_exists($page_file)) {
                include $page_file;
            } else {
                echo '<div class="charts-card">Módulo en construcción</div>';
            }
        ?>

        <?php endif; ?>
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
                    backgroundColor: '#0052CC',
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
</body>
</html>