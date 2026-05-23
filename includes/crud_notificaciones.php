<?php

/**
 * CRUD para notificaciones
 * Funciones para eliminar y marcar como leídas
 */

function eliminar_notificacion(mysqli $conn, string $tabla, int $id): bool {
    $id_col = 'id_notificacion';
    $cols = table_columns($conn, $tabla);
    foreach (array_keys($cols) as $k) {
        if (strcasecmp((string)$k, 'id_notificacion') === 0) {
            $id_col = $k;
            break;
        }
    }
    $stmt = $conn->prepare("DELETE FROM `{$tabla}` WHERE `{$id_col}` = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('i', $id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
    return false;
}

function marcar_notificacion_leida(mysqli $conn, string $tabla, int $id): bool {
    $id_col = 'id_notificacion';
    $estado_col = 'estado';
    $cols = table_columns($conn, $tabla);
    foreach (array_keys($cols) as $k) {
        if (strcasecmp((string)$k, 'id_notificacion') === 0) {
            $id_col = $k;
            break;
        }
    }
    foreach (array_keys($cols) as $k) {
        if (strcasecmp((string)$k, 'estado') === 0) {
            $estado_col = $k;
            break;
        }
    }
    $stmt = $conn->prepare("UPDATE `{$tabla}` SET `{$estado_col}` = 'leida' WHERE `{$id_col}` = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('i', $id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
    return false;
}

function marcar_todas_leidas(mysqli $conn, string $tabla): bool {
    $estado_col = 'estado';
    $cols = table_columns($conn, $tabla);
    foreach (array_keys($cols) as $k) {
        if (strcasecmp((string)$k, 'estado') === 0) {
            $estado_col = $k;
            break;
        }
    }
    $stmt = $conn->prepare("UPDATE `{$tabla}` SET `{$estado_col}` = 'leida' WHERE `{$estado_col}` = 'pendiente'");
    if ($stmt) {
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
    return false;
}
