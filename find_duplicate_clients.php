<?php
/**
 * Script para identificar y limpiar clientes duplicados en RepairlyRD
 * Ejecutar manualmente desde terminal o panel de administración
 */



// Cargar conexión a BD
require_once __DIR__ . '/src/config/database.php';
require_once __DIR__ . '/includes/sql_helpers.php';

if (!$conn || $conn->connect_error) {
    die('Error de conexión: ' . $conn->connect_error);
}

// ==========================================
// PARTE 1: Identificar duplicados por nombre
// ==========================================
echo "=== ANÁLISIS DE CLIENTES DUPLICADOS ===\n\n";

$query = "SELECT nombre, COUNT(*) as total_duplicados, GROUP_CONCAT(id_cliente) as ids
          FROM Cliente 
          GROUP BY nombre 
          HAVING COUNT(*) > 1 
          ORDER BY total_duplicados DESC";

$result = $conn->query($query);

if (!$result) {
    die("Error en la consulta: " . $conn->error);
}

$duplicados = [];
while ($row = $result->fetch_assoc()) {
    $duplicados[] = $row;
    printf("Cliente: %s\n", $row['nombre']);
    printf("  Cantidad de registros: %d\n", $row['total_duplicados']);
    printf("  IDs: %s\n\n", $row['ids']);
}

// ==========================================
// PARTE 2: Revisar referencias de duplicados
// ==========================================
echo "\n=== REFERENCIAS DE LOS DUPLICADOS ===\n\n";

foreach ($duplicados as $dup) {
    $ids = $dup['ids'];
    $id_array = explode(',', $ids);
    
    printf("Cliente '%s' (IDs: %s):\n", $dup['nombre'], $ids);
    
    // Equipos
    $eq_query = "SELECT COUNT(*) as total FROM Equipo WHERE id_cliente IN (" . implode(',', array_map('intval', $id_array)) . ")";
    $eq_result = $conn->query($eq_query);
    if ($eq_result) {
        $eq_row = $eq_result->fetch_assoc();
        printf("  - Equipos vinculados: %d\n", $eq_row['total']);
    }
    
    // Órdenes (a través de equipos)
    $ord_query = "SELECT COUNT(*) as total FROM Orden WHERE id_equipo IN (
                    SELECT id_equipo FROM Equipo WHERE id_cliente IN (" . implode(',', array_map('intval', $id_array)) . ")
                  )";
    $ord_result = $conn->query($ord_query);
    if ($ord_result) {
        $ord_row = $ord_result->fetch_assoc();
        printf("  - Órdenes vinculadas: %d\n", $ord_row['total']);
    }
    
    echo "\n";
}

// ==========================================
// PARTE 3: Consulta para fusionar duplicados
// ==========================================
echo "\n=== INSTRUCCIONES PARA FUSIONAR DUPLICADOS ===\n\n";
echo "Para fusionar duplicados, necesitas:\n";
echo "1. Elegir el cliente principal (el que guardarás)\n";
echo "2. Vincular todos los equipos del cliente secundario al principal\n";
echo "3. Eliminar el cliente secundario\n\n";

echo "Ejemplo de SQL para fusionar:\n";
echo "-- Supongamos que quieres mantener id_cliente=1 y eliminar id_cliente=2\n";
echo "UPDATE Equipo SET id_cliente = 1 WHERE id_cliente = 2;\n";
echo "DELETE FROM Cliente WHERE id_cliente = 2;\n\n";

$conn->close();
?>
