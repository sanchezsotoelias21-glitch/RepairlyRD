<?php

declare(strict_types=1);

$garantia_table_name = pick_table($conn, ['garantia', 'Garantia']);
$garantia_cols = $garantia_table_name ? table_columns($conn, $garantia_table_name) : [];

if ($current_page === 'garantias' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $post_action = $_POST['gar_action'] ?? '';
    if (!is_string($post_action)) {
        $post_action = '';
    }

    if (!$garantia_table_name) {
        header('Location: ?page=garantias&t=err&m=Tabla+no+encontrada');
        exit;
    }

    $idField = 'id_garantia';
    foreach (array_keys($garantia_cols) as $k) {
        if (strcasecmp((string)$k, 'id_garantia') === 0) {
            $idField = $k;
            break;
        }
    }

    $id_orden = (int)($_POST['id_orden'] ?? 0);
    $fecha_inicio = trim((string)($_POST['fecha_inicio'] ?? ''));
    $fecha_fin = trim((string)($_POST['fecha_fin'] ?? ''));
    $cobertura_dias = (int)($_POST['cobertura_dias'] ?? 0);
    $tipo = trim((string)($_POST['tipo'] ?? ''));
    $estado = trim((string)($_POST['estado'] ?? 'activa'));

    if ($post_action === 'create' || $post_action === 'update') {
        $map = [
            'id_orden' => $id_orden,
            'fecha_inicio' => $fecha_inicio,
            'fecha_fin' => $fecha_fin,
            'cobertura_dias' => $cobertura_dias,
            'tipo' => $tipo,
            'estado' => $estado,
        ];
        if ($post_action === 'create') {
            if ($id_orden <= 0) {
                header('Location: ?page=garantias&action=new&t=err&m=Orden+requerida');
                exit;
            }
            $fields = [];
            $types = '';
            $vals = [];
            foreach ($map as $col => $val) {
                if (!isset($garantia_cols[$col])) {
                    continue;
                }
                $fields[] = "`{$col}`";
                if ($col === 'id_orden' || $col === 'cobertura_dias') {
                    $types .= 'i';
                    $vals[] = (int)$val;
                } else {
                    $types .= 's';
                    $vals[] = (string)$val;
                }
            }
            if (empty($fields)) {
                header('Location: ?page=garantias&t=err&m=Sin+columnas');
                exit;
            }
            $sql = "INSERT INTO `{$garantia_table_name}` (" . implode(',', $fields) . ') VALUES (' . implode(',', array_fill(0, count($fields), '?')) . ')';
            $stmt = $conn->prepare($sql);
            if ($stmt && $types !== '') {
                $stmt->bind_param($types, ...$vals);
                $stmt->execute();
                $stmt->close();
            }
            header('Location: ?page=garantias&t=ok&m=Garant%C3%ADa+creada');
            exit;
        }
        $id = (int)($_POST['id_garantia'] ?? 0);
        if ($id <= 0) {
            header('Location: ?page=garantias&t=err&m=ID+inv%C3%A1lido');
            exit;
        }
        $sets = [];
        $typesU = '';
        $valsU = [];
        foreach ($map as $col => $val) {
            if (!isset($garantia_cols[$col])) {
                continue;
            }
            $sets[] = "`{$col}`=?";
            if ($col === 'id_orden' || $col === 'cobertura_dias') {
                $typesU .= 'i';
                $valsU[] = (int)$val;
            } else {
                $typesU .= 's';
                $valsU[] = (string)$val;
            }
        }
        $sql = "UPDATE `{$garantia_table_name}` SET " . implode(',', $sets) . " WHERE `{$idField}`=? LIMIT 1";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $typesU .= 'i';
            $valsU[] = $id;
            $stmt->bind_param($typesU, ...$valsU);
            $stmt->execute();
            $stmt->close();
        }
        header('Location: ?page=garantias&t=ok&m=Actualizada');
        exit;
    }

    if ($post_action === 'delete') {
        $id = (int)($_POST['id_garantia'] ?? 0);
        if ($id <= 0) {
            header('Location: ?page=garantias&t=err&m=ID+inv%C3%A1lido');
            exit;
        }
        $stmt = $conn->prepare("DELETE FROM `{$garantia_table_name}` WHERE `{$idField}`=? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $stmt->close();
        }
        header('Location: ?page=garantias&t=ok&m=Eliminada');
        exit;
    }
}
