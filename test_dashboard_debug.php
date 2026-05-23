<?php
// Script temporal de diagnóstico del dashboard
require_once 'src/config/database.php';
require_once 'includes/sql_helpers.php';

echo "=== Diagnóstico del Dashboard ===\n\n";

// Intentar listar todas las tablas
echo "Tablas en la base de datos:\n";
$result = $conn->query('SHOW TABLES');
if ($result) {
    $tables = [];
    while ($row = $result->fetch_row()) {
        $tables[] = $row[0];
        echo "  - " . $row[0] . "\n";
    }
    $result->free();
    echo "\nTotal: " . count($tables) . " tablas\n";
}

echo "\n--- Búsqueda de tablas del dashboard ---\n";

// Probar pick_table con las mismas opciones actualizadas
$orden_table = pick_table($conn, ['orden_reparacion', 'Orden_Reparacion', 'reparacion', 'Reparacion', 'orden']);
$estado_table = pick_table($conn, ['estado_servicio', 'estado', 'Estado_Servicio']);
$equipo_table = pick_table($conn, ['equipo']);
$cliente_table = pick_table($conn, ['cliente']);
$tecnico_table = pick_table($conn, ['tecnico']);

echo "Orden:  " . ($orden_table !== '' ? "✓ " . $orden_table : "✗ NO ENCONTRADA") . "\n";
echo "Estado: " . ($estado_table !== '' ? "✓ " . $estado_table : "✗ NO ENCONTRADA") . "\n";
echo "Equipo: " . ($equipo_table !== '' ? "✓ " . $equipo_table : "✗ NO ENCONTRADA") . "\n";
echo "Cliente: " . ($cliente_table !== '' ? "✓ " . $cliente_table : "✗ NO ENCONTRADA") . "\n";
echo "Técnico: " . ($tecnico_table !== '' ? "✓ " . $tecnico_table : "✗ NO ENCONTRADA") . "\n";

$has_dashboard_core = table_exists($conn, $orden_table)
    && table_exists($conn, $estado_table)
    && table_exists($conn, $equipo_table);

echo "\n--- Verificación Final ---\n";
echo "has_dashboard_core: " . ($has_dashboard_core ? "✓ TRUE - Dashboard cargará datos" : "✗ FALSE - Dashboard mostrará arrays vacíos") . "\n";

if ($has_dashboard_core) {
    echo "\n--- Conteos de datos ---\n";
    $ordenes = $conn->query("SELECT COUNT(*) as cnt FROM `{$orden_table}`");
    if ($ordenes) {
        $row = $ordenes->fetch_assoc();
        echo "Órdenes en la BD: " . $row['cnt'] . "\n";
        $ordenes->free();
    }
    
    $estados = $conn->query("SELECT COUNT(*) as cnt FROM `{$estado_table}`");
    if ($estados) {
        $row = $estados->fetch_assoc();
        echo "Estados en la BD: " . $row['cnt'] . "\n";
        $estados->free();
    }
    
    $equipos = $conn->query("SELECT COUNT(*) as cnt FROM `{$equipo_table}`");
    if ($equipos) {
        $row = $equipos->fetch_assoc();
        echo "Equipos en la BD: " . $row['cnt'] . "\n";
        $equipos->free();
    }
}
?>
