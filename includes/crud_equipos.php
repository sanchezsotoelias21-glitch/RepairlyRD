<?php

declare(strict_types=1);

$equipo_table = pick_table($conn, ['equipo', 'Equipo']);
$equipo_cols = $equipo_table ? table_columns($conn, $equipo_table) : [];

if ($current_page === 'equipos' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $post_action = $_POST['equipos_action'] ?? '';
    if (!is_string($post_action)) {
        $post_action = '';
    }

    if (!$equipo_table) {
        header('Location: ?page=equipos&t=err&m=Tabla+equipo+no+encontrada');
        exit;
    }

    $idField = 'id_equipo';
    foreach (array_keys($equipo_cols) as $k) {
        if (strcasecmp((string)$k, 'id_equipo') === 0) {
            $idField = $k;
            break;
        }
    }

    $map = [
        'tipo' => trim((string)($_POST['tipo'] ?? '')),
        'marca' => trim((string)($_POST['marca'] ?? '')),
        'modelo' => trim((string)($_POST['modelo'] ?? '')),
        'numero_identificacion' => trim((string)($_POST['numero_identificacion'] ?? $_POST['serial'] ?? '')),
        'tipo_identificacion' => trim((string)($_POST['tipo_identificacion'] ?? '')),
        'bloqueo_tipo' => trim((string)($_POST['bloqueo_tipo'] ?? '')),
        'requiere_desbloqueo' => !empty($_POST['requiere_desbloqueo']) ? 1 : 0,
        'observaciones_ingreso' => trim((string)($_POST['observaciones_ingreso'] ?? '')),
        'id_cliente' => (int)($_POST['id_cliente'] ?? 0),
    ];

    if ($post_action === 'create' || $post_action === 'update') {
        $fields = [];
        $values = [];
        $types = '';
        foreach ($map as $col => $val) {
            if (!isset($equipo_cols[$col])) {
                continue;
            }
            if ($col === 'requiere_desbloqueo') {
                $fields[] = "`{$col}`";
                $types .= 'i';
                $values[] = (int)$val;
                continue;
            }
            if ($col === 'id_cliente') {
                if ((int)$val <= 0) {
                    continue;
                }
                $fields[] = "`{$col}`";
                $types .= 'i';
                $values[] = (int)$val;
                continue;
            }
            $fields[] = "`{$col}`";
            $types .= 's';
            $values[] = (string)$val;
        }
        if ($post_action === 'create' && empty($fields)) {
            header('Location: ?page=equipos&action=new&t=err&m=Sin+columnas+v%C3%A1lidas');
            exit;
        }
        if ($post_action === 'create') {
            $placeholders = implode(',', array_fill(0, count($fields), '?'));
            $sql = "INSERT INTO `{$equipo_table}` (" . implode(',', $fields) . ") VALUES ({$placeholders})";
            $stmt = $conn->prepare($sql);
            $okIns = false;
            if ($stmt && $types !== '') {
                $stmt->bind_param($types, ...$values);
                $okIns = $stmt->execute();
                $stmt->close();
            }
            header('Location: ?page=equipos&t=' . ($okIns ? 'ok' : 'err') . '&m=' . ($okIns ? 'Equipo+creado' : 'Error'));
            exit;
        }
        $id = (int)($_POST['id_equipo'] ?? 0);
        if ($id <= 0) {
            header('Location: ?page=equipos&t=err&m=ID+inv%C3%A1lido');
            exit;
        }
        $sets = [];
        $typesU = '';
        $valsU = [];
        foreach ($map as $col => $val) {
            if (!isset($equipo_cols[$col])) {
                continue;
            }
            if ($col === 'requiere_desbloqueo') {
                $sets[] = "`{$col}`=?";
                $typesU .= 'i';
                $valsU[] = (int)$val;
                continue;
            }
            if ($col === 'id_cliente') {
                $sets[] = "`{$col}`=?";
                $typesU .= 'i';
                $valsU[] = (int)$val;
                continue;
            }
            $sets[] = "`{$col}`=?";
            $typesU .= 's';
            $valsU[] = (string)$val;
        }
        if (empty($sets)) {
            header('Location: ?page=equipos&t=err&m=Nada+que+actualizar');
            exit;
        }
        $sql = "UPDATE `{$equipo_table}` SET " . implode(',', $sets) . " WHERE `{$idField}`=? LIMIT 1";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $typesU .= 'i';
            $valsU[] = $id;
            $stmt->bind_param($typesU, ...$valsU);
            $stmt->execute();
            $stmt->close();
        }
        header('Location: ?page=equipos&t=ok&m=Equipo+actualizado');
        exit;
    }

    if ($post_action === 'delete') {
        $id = (int)($_POST['id_equipo'] ?? 0);
        if ($id <= 0) {
            header('Location: ?page=equipos&t=err&m=ID+inv%C3%A1lido');
            exit;
        }
        $stmt = $conn->prepare("DELETE FROM `{$equipo_table}` WHERE `{$idField}`=? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $stmt->close();
        }
        header('Location: ?page=equipos&t=ok&m=Equipo+eliminado');
        exit;
    }
}
