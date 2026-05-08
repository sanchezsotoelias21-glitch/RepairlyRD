<?php
// ============================================================
//  FixMaster ERP — Dashboard Principal
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
        'valor'     => 24,
        'sub'       => 'órdenes activas',
        'icono'     => 'ti-loader',
        'color'     => '#1F5C8B',
        'bg'        => '#F0F6FC',
        'texto'     => '#0A2540',
    ],
    [
        'clave'     => 'pendientes',
        'label'     => 'Pendientes',
        'valor'     => 8,
        'sub'       => 'sin asignar',
        'icono'     => 'ti-clock',
        'color'     => '#D4991A',
        'bg'        => '#FFFBF0',
        'texto'     => '#5C3E00',
    ],
    [
        'clave'     => 'ingresos',
        'label'     => 'Ingresos hoy',
        'valor'     => '$4,820',
        'sub'       => '<span style="color:#1A7A4A;display:flex;align-items:center;gap:3px;"><i class="ti ti-trending-up" style="font-size:11px;"></i>+12% vs ayer</span>',
        'icono'     => 'ti-cash',
        'color'     => '#1A7A4A',
        'bg'        => '#F0FAF5',
        'texto'     => '#0B3D24',
    ],
    [
        'clave'     => 'garantias',
        'label'     => 'Garantías',
        'valor'     => 11,
        'sub'       => 'activas este mes',
        'icono'     => 'ti-shield',
        'color'     => '#5B42B0',
        'bg'        => '#F4F1FC',
        'texto'     => '#2A1E60',
    ],
    [
        'clave'     => 'completadas',
        'label'     => 'Completadas',
        'valor'     => 137,
        'sub'       => 'servicios este mes',
        'icono'     => 'ti-checks',
        'color'     => '#4D4841',
        'bg'        => '#FFFFFF',
        'texto'     => '#1C1A17',
    ],
    [
        'clave'     => 'con_falla',
        'label'     => 'Con falla',
        'valor'     => 3,
        'sub'       => 'requieren atención',
        'icono'     => 'ti-alert-triangle',
        'color'     => '#B83232',
        'bg'        => '#FDF0F0',
        'texto'     => '#5C1414',
    ],
];

$nav_items = [
    ['seccion' => true, 'label' => 'Principal'],
    ['label' => 'Dashboard',    'icono' => 'ti-layout-dashboard', 'activo' => true,  'badge' => null],
    ['label' => 'Clientes',     'icono' => 'ti-users',            'activo' => false, 'badge' => null],
    ['label' => 'Equipos',      'icono' => 'ti-device-laptop',    'activo' => false, 'badge' => null],
    ['label' => 'Técnicos',     'icono' => 'ti-user-check',       'activo' => false, 'badge' => null],
    ['seccion' => true, 'label' => 'Operaciones'],
    ['label' => 'Órdenes',      'icono' => 'ti-clipboard-list',   'activo' => false, 'badge' => ['valor'=>8,  'bg'=>'#D4991A','color'=>'#3A2600']],
    ['label' => 'Garantías',    'icono' => 'ti-shield-check',     'activo' => false, 'badge' => ['valor'=>11, 'bg'=>'rgba(91,66,176,0.35)','color'=>'#C4B8F0']],
    ['label' => 'Reportes',     'icono' => 'ti-chart-bar',        'activo' => false, 'badge' => null],
    ['seccion' => true, 'label' => 'Sistema'],
    ['label' => 'Configuración','icono' => 'ti-settings',         'activo' => false, 'badge' => null],
    ['label' => 'WhatsApp',     'icono' => 'ti-brand-whatsapp',   'activo' => false, 'badge' => ['dot' => true, 'bg'=>'#25D366']],
];

$ordenes_recientes = [
    [
        'id'       => '1042',
        'cliente'  => 'Ramón Díaz',
        'equipo'   => 'iPhone 14 Pro — pantalla',
        'icono_eq' => 'ti-device-mobile',
        'tecnico'  => 'Juan',
        'estado'   => 'En proceso',
        'est_bg'   => '#F0F6FC',
        'est_color'=> '#0A2540',
        'est_borde'=> '#1F5C8B',
        'est_dot'  => '#1F5C8B',
        'valor'    => '$850',
    ],
    [
        'id'       => '1041',
        'cliente'  => 'Laura Méndez',
        'equipo'   => 'MacBook Air — teclado',
        'icono_eq' => 'ti-device-laptop',
        'tecnico'  => 'María',
        'estado'   => 'Listo',
        'est_bg'   => '#F0FAF5',
        'est_color'=> '#0B3D24',
        'est_borde'=> '#1A7A4A',
        'est_dot'  => '#1A7A4A',
        'valor'    => '$1,200',
    ],
    [
        'id'       => '1040',
        'cliente'  => 'Pedro Santos',
        'equipo'   => 'PC Torre — fuente de poder',
        'icono_eq' => 'ti-device-desktop',
        'tecnico'  => 'Carlos',
        'estado'   => 'Con falla',
        'est_bg'   => '#FDF0F0',
        'est_color'=> '#5C1414',
        'est_borde'=> '#B83232',
        'est_dot'  => '#B83232',
        'valor'    => '$450',
    ],
    [
        'id'       => '1039',
        'cliente'  => 'Ana Castillo',
        'equipo'   => 'iPad Air — batería',
        'icono_eq' => 'ti-device-tablet',
        'tecnico'  => 'Ana',
        'estado'   => 'Pendiente',
        'est_bg'   => '#FFFBF0',
        'est_color'=> '#5C3E00',
        'est_borde'=> '#D4991A',
        'est_dot'  => '#D4991A',
        'valor'    => '$380',
    ],
    [
        'id'       => '1038',
        'cliente'  => 'Mario Reyes',
        'equipo'   => 'Samsung S23 — garantía',
        'icono_eq' => 'ti-device-mobile',
        'tecnico'  => 'Pedro',
        'estado'   => 'Garantía',
        'est_bg'   => '#F4F1FC',
        'est_color'=> '#2A1E60',
        'est_borde'=> '#5B42B0',
        'est_dot'  => '#5B42B0',
        'valor'    => '$0',
    ],
    [
        'id'       => '1037',
        'cliente'  => 'Sofía Herrera',
        'equipo'   => 'Dell XPS 15 — diagnóstico',
        'icono_eq' => 'ti-device-laptop',
        'tecnico'  => 'María',
        'estado'   => 'Diagnóstico',
        'est_bg'   => '#F5F4F2',
        'est_color'=> '#2C2925',
        'est_borde'=> '#8C8479',
        'est_dot'  => '#8C8479',
        'valor'    => '$200',
    ],
];

$dispositivos = [
    ['tipo'=>'Teléfonos',  'icono'=>'ti-device-mobile',  'pct'=>45, 'color'=>'#1F5C8B', 'bg'=>'#F0F6FC',  'tc'=>'#1F5C8B'],
    ['tipo'=>'Laptops',    'icono'=>'ti-device-laptop',  'pct'=>28, 'color'=>'#1A7A4A', 'bg'=>'#F0FAF5',  'tc'=>'#1A7A4A'],
    ['tipo'=>'Tablets',    'icono'=>'ti-device-tablet',  'pct'=>15, 'color'=>'#D4991A', 'bg'=>'#FFFBF0',  'tc'=>'#D4991A'],
    ['tipo'=>'PC Torre',   'icono'=>'ti-device-desktop', 'pct'=>12, 'color'=>'#5B42B0', 'bg'=>'#F4F1FC',  'tc'=>'#5B42B0'],
];

$fallas_urgentes = array_filter($ordenes_recientes, fn($o) => $o['estado'] === 'Con falla');
$total_fallas    = count($fallas_urgentes);

$fecha_es = (new IntlDateFormatter(
    'es_DO',
    IntlDateFormatter::FULL,
    IntlDateFormatter::NONE
))->format(new DateTime());
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
<title>FixMaster ERP — Dashboard</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.19.0/dist/tabler-icons.min.css">
<style>
/* ── Reset ── */
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html{font-size:14px}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',system-ui,sans-serif;background:#F8F7F5;color:#1C1A17;min-height:100vh;display:flex;overflow-x:hidden}

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
.main{flex:1;display:flex;flex-direction:column;min-width:0}
.topbar{background:#fff;border-bottom:0.5px solid #D0CCC6;padding:11px 20px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:10}
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
.kpi-card{border-radius:9px;border:0.5px solid #D0CCC6;padding:13px 15px;border-left-width:3px;border-left-style:solid}
.kpi-label{font-size:9px;font-weight:500;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:6px;display:flex;align-items:center;gap:4px}
.kpi-label i{font-size:11px}
.kpi-valor{font-size:28px;font-weight:600;font-variant-numeric:tabular-nums;line-height:1;font-family:'Courier New',Courier,monospace}
.kpi-sub{font-size:10.5px;color:#4D4841;margin-top:3px}

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
            <div class="sidebar-logo-text">FixMaster</div>
            <div class="sidebar-logo-ver">ERP v2.4</div>
        </div>
    </div>

    <nav class="sidebar-nav">
        <?php foreach ($nav_items as $item): ?>
            <?php if (!empty($item['seccion'])): ?>
                <div class="nav-section"><?= htmlspecialchars($item['label']) ?></div>
            <?php else: ?>
                <a href="#"
                   class="nav-item<?= !empty($item['activo']) ? ' active' : '' ?>"
                   <?= !empty($item['activo']) ? 'aria-current="page"' : '' ?>
                   title="<?= htmlspecialchars($item['label']) ?>">
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
            <div class="topbar-title">Dashboard</div>
            <div class="topbar-sub">Bienvenido al sistema de gestión de reparaciones</div>
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
                 style="background:<?= $k['bg'] ?>;border-left-color:<?= $k['color'] ?>;">
                <div class="kpi-label" style="color:<?= $k['color'] ?>;">
                    <i class="ti <?= htmlspecialchars($k['icono']) ?>"></i>
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
                 style="background:<?= $k['bg'] ?>;border-left-color:<?= $k['color'] ?>;">
                <div class="kpi-label" style="color:<?= $k['color'] ?>;">
                    <i class="ti <?= htmlspecialchars($k['icono']) ?>"></i>
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
                        <span class="legend-sq" style="background:#1F5C8B;"></span>
                        Reparaciones
                    </span>
                    <span class="legend-item">
                        <span class="legend-sq" style="background:#A8C3DC;"></span>
                        Ingresos ($100s)
                    </span>
                </div>
                <div class="chart-wrap" style="height:180px;">
                    <canvas id="barChart"
                            role="img"
                            aria-label="Productividad por técnico: Juan 23 reparaciones, María 19, Carlos 21, Ana 20, Pedro 18">
                        Juan 23 rep / $2,300 · María 19 / $2,100 · Carlos 21 / $2,200 · Ana 20 / $2,000 · Pedro 18 / $2,050
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
                            aria-label="Distribución de estados de órdenes: Entregado 45%, En reparación 20%, Diagnóstico 15%, Listo 10%, Recibido 7%, Con falla 3%">
                        Entregado 45%, En reparación 20%, Diagnóstico 15%, Listo 10%, Recibido 7%, Con falla 3%
                    </canvas>
                </div>
                <div class="donut-legend">
                    <span class="donut-legend-item"><span class="donut-dot" style="background:#D4991A;"></span>Recibido 7%</span>
                    <span class="donut-legend-item"><span class="donut-dot" style="background:#1F5C8B;"></span>Diagnóstico 15%</span>
                    <span class="donut-legend-item"><span class="donut-dot" style="background:#1A7A4A;"></span>Reparación 20%</span>
                    <span class="donut-legend-item"><span class="donut-dot" style="background:#5B42B0;"></span>Listo 10%</span>
                    <span class="donut-legend-item"><span class="donut-dot" style="background:#8C8479;"></span>Entregado 45%</span>
                    <span class="donut-legend-item"><span class="donut-dot" style="background:#B83232;"></span>Con falla 3%</span>
                </div>
            </div>
        </div>


        <!-- ── Fila inferior ── -->
        <div class="bottom-row">

            <!-- Dispositivos -->
            <div class="dispositivos-card">
                <div class="card-title" style="margin-bottom:14px;">Por tipo de dispositivo</div>
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
                    <a href="ordenes.php" class="ordenes-ver-btn">Ver todas &rarr;</a>
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
    </div><!-- /content -->
</main><!-- /main -->


<!-- ════════════════════════════════════════════════
     Chart.js
═════════════════════════════════════════════════ -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.js"></script>
<script>
(function () {
    'use strict';

    // ── Bar chart ──────────────────────────────────────
    new Chart(document.getElementById('barChart'), {
        type: 'bar',
        data: {
            labels: ['Juan', 'María', 'Carlos', 'Ana', 'Pedro'],
            datasets: [
                {
                    label: 'Reparaciones',
                    data: [23, 19, 21, 20, 18],
                    backgroundColor: '#1F5C8B',
                    borderRadius: 4,
                    barPercentage: 0.55,
                    categoryPercentage: 0.8,
                },
                {
                    label: 'Ingresos ($100s)',
                    data: [23, 21, 22, 20, 21],
                    backgroundColor: '#A8C3DC',
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
    new Chart(document.getElementById('donutChart'), {
        type: 'doughnut',
        data: {
            labels: ['Recibido', 'Diagnóstico', 'En reparación', 'Listo', 'Entregado', 'Con falla'],
            datasets: [{
                data: [7, 15, 20, 10, 45, 3],
                backgroundColor: ['#D4991A', '#1F5C8B', '#1A7A4A', '#5B42B0', '#8C8479', '#B83232'],
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