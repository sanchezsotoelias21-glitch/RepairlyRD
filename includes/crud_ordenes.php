<?php

declare(strict_types=1);

$orden_table_name = pick_table($conn, ['orden_reparacion', 'Orden_Reparacion', 'orden']);
$orden_cols = $orden_table_name ? table_columns($conn, $orden_table_name) : [];

if ($current_page === 'ordenes' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $post_action = $_POST['orden_action'] ?? '';
    if (!is_string($post_action)) {
        $post_action = '';
    }

    if (!$orden_table_name) {
        header('Location: ?page=ordenes&t=err&m=Tabla+orden+no+encontrada');
        exit;
    }

    $idField = 'id_orden';
    foreach (array_keys($orden_cols) as $k) {
        if (strcasecmp((string)$k, 'id_orden') === 0) {
            $idField = $k;
            break;
        }
    }

    $codigo = trim((string)($_POST['codigo_seguimiento'] ?? ''));
    $id_equipo = (int)($_POST['id_equipo'] ?? 0);
    $id_tecnico = (int)($_POST['id_tecnico'] ?? 0);
    $id_estado = (int)($_POST['id_estado_actual'] ?? 0);
    $mano_obra = (float)str_replace(',', '.', (string)($_POST['mano_obra'] ?? '0'));
    $costo_total = (float)str_replace(',', '.', (string)($_POST['costo_total'] ?? '0'));
    $fecha_ingreso = trim((string)($_POST['fecha_ingreso'] ?? ''));
    $fecha_est = trim((string)($_POST['fecha_estimada_entrega'] ?? ''));
    $fecha_ent_real = trim((string)($_POST['fecha_entrega_real'] ?? ''));

    if ($post_action === 'create' || $post_action === 'update') {
        $data = [];
        if (isset($orden_cols['codigo_seguimiento'])) {
            $data['codigo_seguimiento'] = $codigo !== '' ? $codigo : ('ORD-' . strtoupper(bin2hex(random_bytes(3))));
        }
        if (isset($orden_cols['id_equipo'])) {
            $data['id_equipo'] = $id_equipo;
        }
        if (isset($orden_cols['id_tecnico'])) {
            $data['id_tecnico'] = $id_tecnico;
        }
        if (isset($orden_cols['id_estado_actual'])) {
            $data['id_estado_actual'] = $id_estado;
        }
        if (isset($orden_cols['mano_obra'])) {
            $data['mano_obra'] = $mano_obra;
        }
        if (isset($orden_cols['costo_total'])) {
            $data['costo_total'] = $costo_total;
        }
        if (isset($orden_cols['fecha_ingreso']) && $fecha_ingreso !== '') {
            $data['fecha_ingreso'] = $fecha_ingreso;
        }
        if (isset($orden_cols['fecha_estimada_entrega']) && $fecha_est !== '') {
            $data['fecha_estimada_entrega'] = $fecha_est;
        }
        if (isset($orden_cols['fecha_entrega_real']) && $fecha_ent_real !== '') {
            $data['fecha_entrega_real'] = $fecha_ent_real;
        }

        if ($post_action === 'create') {
            $fields = [];
            $types = '';
            $vals = [];
            foreach ($data as $col => $val) {
                $fields[] = "`{$col}`";
                if (is_int($val)) {
                    $types .= 'i';
                    $vals[] = $val;
                } elseif (is_float($val)) {
                    $types .= 'd';
                    $vals[] = $val;
                } else {
                    $types .= 's';
                    $vals[] = (string)$val;
                }
            }
            if (isset($orden_cols['fecha_creacion'])) {
                $fields[] = '`fecha_creacion`';
                $types .= 's';
                $vals[] = date('Y-m-d H:i:s');
            }
            if (isset($orden_cols['fecha_actualizacion'])) {
                $fields[] = '`fecha_actualizacion`';
                $types .= 's';
                $vals[] = date('Y-m-d H:i:s');
            }
            if (empty($fields)) {
                header('Location: ?page=ordenes&action=new&t=err&m=Datos+insuficientes');
                exit;
            }
            $sql = "INSERT INTO `{$orden_table_name}` (" . implode(',', $fields) . ') VALUES (' . implode(',', array_fill(0, count($fields), '?')) . ')';
            $stmt = $conn->prepare($sql);
            if ($stmt && $types !== '') {
                $stmt->bind_param($types, ...$vals);
                $stmt->execute();
                $stmt->close();
            }
            header('Location: ?page=ordenes&t=' . ($stmt ? 'ok' : 'err') . '&m=' . ($stmt ? 'Orden+creada' : 'Error'));
            exit;
        }

        $id = (int)($_POST['id_orden'] ?? 0);
        if ($id <= 0) {
            header('Location: ?page=ordenes&t=err&m=ID+inv%C3%A1lido');
            exit;
        }
        $sets = [];
        $typesU = '';
        $valsU = [];
        foreach ($data as $col => $val) {
            $sets[] = "`{$col}`=?";
            if (is_int($val)) {
                $typesU .= 'i';
                $valsU[] = $val;
            } elseif (is_float($val)) {
                $typesU .= 'd';
                $valsU[] = $val;
            } else {
                $typesU .= 's';
                $valsU[] = (string)$val;
            }
        }
        if (isset($orden_cols['fecha_actualizacion'])) {
            $sets[] = '`fecha_actualizacion`=?';
            $typesU .= 's';
            $valsU[] = date('Y-m-d H:i:s');
        }
        if (empty($sets)) {
            header('Location: ?page=ordenes&t=err&m=Nada+que+actualizar');
            exit;
        }
        $sql = "UPDATE `{$orden_table_name}` SET " . implode(',', $sets) . " WHERE `{$idField}`=? LIMIT 1";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $typesU .= 'i';
            $valsU[] = $id;
            $stmt->bind_param($typesU, ...$valsU);
            $stmt->execute();
            $stmt->close();
        }
        header('Location: ?page=ordenes&t=ok&m=Orden+actualizada');
        exit;
    }

    if ($post_action === 'delete') {
        $id = (int)($_POST['id_orden'] ?? 0);
        if ($id <= 0) {
            header('Location: ?page=ordenes&t=err&m=ID+inv%C3%A1lido');
            exit;
        }
        $stmt = $conn->prepare("DELETE FROM `{$orden_table_name}` WHERE `{$idField}`=? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $stmt->close();
        }
        header('Location: ?page=ordenes&t=ok&m=Orden+eliminada');
        exit;
    }
}
