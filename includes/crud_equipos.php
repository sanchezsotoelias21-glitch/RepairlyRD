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

    $tipo = isset($_POST['tipo']) && is_string($_POST['tipo']) ? trim($_POST['tipo']) : '';
    $marca = isset($_POST['marca']) && is_string($_POST['marca']) ? trim($_POST['marca']) : '';
    $modelo = isset($_POST['modelo']) && is_string($_POST['modelo']) ? trim($_POST['modelo']) : '';
    $numero_identificacion = '';
    if (isset($_POST['numero_identificacion']) && is_string($_POST['numero_identificacion'])) {
        $numero_identificacion = trim($_POST['numero_identificacion']);
    } elseif (isset($_POST['serial']) && is_string($_POST['serial'])) {
        $numero_identificacion = trim($_POST['serial']);
    }
    $tipo_identificacion = isset($_POST['tipo_identificacion']) && is_string($_POST['tipo_identificacion']) ? trim($_POST['tipo_identificacion']) : '';
    $bloqueo_tipo = isset($_POST['bloqueo_tipo']) && is_string($_POST['bloqueo_tipo']) ? trim($_POST['bloqueo_tipo']) : '';
    $requiere_desbloqueo = !empty($_POST['requiere_desbloqueo']) ? 1 : 0;
    $observaciones_ingreso = isset($_POST['observaciones_ingreso']) && is_string($_POST['observaciones_ingreso']) ? trim($_POST['observaciones_ingreso']) : '';
    $id_cliente = isset($_POST['id_cliente']) ? (int)$_POST['id_cliente'] : 0;

    $map = [
        'tipo' => $tipo,
        'marca' => $marca,
        'modelo' => $modelo,
        'numero_identificacion' => $numero_identificacion,
        'tipo_identificacion' => $tipo_identificacion,
        'bloqueo_tipo' => $bloqueo_tipo,
        'requiere_desbloqueo' => $requiere_desbloqueo,
        'observaciones_ingreso' => $observaciones_ingreso,
        'id_cliente' => $id_cliente,
    ];

    $has_some_descripcion = false;
    if (isset($equipo_cols['tipo']) && $tipo !== '') {
        $has_some_descripcion = true;
    }
    if (isset($equipo_cols['marca']) && $marca !== '') {
        $has_some_descripcion = true;
    }
    if (isset($equipo_cols['modelo']) && $modelo !== '') {
        $has_some_descripcion = true;
    }

    if ($post_action === 'create' || $post_action === 'update') {
        $needs_desc = isset($equipo_cols['tipo']) || isset($equipo_cols['marca']) || isset($equipo_cols['modelo']);
        if ($needs_desc && !$has_some_descripcion) {
            $back = $post_action === 'update'
                ? ('?page=equipos&action=edit&id=' . (int)($_POST['id_equipo'] ?? 0))
                : '?page=equipos&action=new';
            header('Location: ' . $back . '&t=err&m=Indica+al+menos+tipo%2C+marca+o+modelo');
            exit;
        }
        if (isset($equipo_cols['id_cliente']) && $id_cliente <= 0) {
            $back = $post_action === 'update'
                ? ('?page=equipos&action=edit&id=' . (int)($_POST['id_equipo'] ?? 0))
                : '?page=equipos&action=new';
            header('Location: ' . $back . '&t=err&m=Debes+seleccionar+un+cliente');
            exit;
        }
    }

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
        if ($post_action === 'create') {
            if (empty($fields)) {
                header('Location: ?page=equipos&action=new&t=err&m=Sin+columnas+v%C3%A1lidas');
                exit;
            }
            $placeholders = implode(',', array_fill(0, count($fields), '?'));
            $sql = "INSERT INTO `{$equipo_table}` (" . implode(',', $fields) . ") VALUES ({$placeholders})";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                header('Location: ?page=equipos&t=err&m=No+se+pudo+crear+el+equipo');
                exit;
            }
            if ($types !== '') {
                $stmt->bind_param($types, ...$values);
            }
            $ok = $stmt->execute();
            $stmt->close();
            header('Location: ?page=equipos&t=' . ($ok ? 'ok' : 'err') . '&m=' . ($ok ? 'Equipo+creado' : 'Error+al+crear'));
            exit;
        }
        $id = isset($_POST['id_equipo']) ? (int)$_POST['id_equipo'] : 0;
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
        if (!$stmt) {
            header('Location: ?page=equipos&t=err&m=No+se+pudo+actualizar');
            exit;
        }
        $typesU .= 'i';
        $valsU[] = $id;
        $stmt->bind_param($typesU, ...$valsU);
        $ok = $stmt->execute();
        $stmt->close();
        header('Location: ?page=equipos&t=' . ($ok ? 'ok' : 'err') . '&m=' . ($ok ? 'Equipo+actualizado' : 'Error+al+actualizar'));
        exit;
    }

    if ($post_action === 'delete') {
        $id = isset($_POST['id_equipo']) ? (int)$_POST['id_equipo'] : 0;
        if ($id <= 0) {
            header('Location: ?page=equipos&t=err&m=ID+inv%C3%A1lido');
            exit;
        }
        $stmt = $conn->prepare("DELETE FROM `{$equipo_table}` WHERE `{$idField}`=? LIMIT 1");
        if (!$stmt) {
            header('Location: ?page=equipos&t=err&m=No+se+pudo+eliminar');
            exit;
        }
        $stmt->bind_param('i', $id);
        $ok = $stmt->execute();
        $stmt->close();
        header('Location: ?page=equipos&t=' . ($ok ? 'ok' : 'err') . '&m=' . ($ok ? 'Equipo+eliminado' : 'Error+al+eliminar'));
        exit;
    }
}
