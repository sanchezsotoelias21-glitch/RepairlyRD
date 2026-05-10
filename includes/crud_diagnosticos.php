<?php

declare(strict_types=1);

$diag_table = pick_table($conn, ['diagnostico', 'Diagnostico', 'DIAGNOSTICO']);
$diag_cols = $diag_table ? table_columns($conn, $diag_table) : [];

$diag_col_orden = $diag_table ? repairly_pick_column($diag_cols, ['id_orden', 'orden_id']) : null;
$diag_col_tipo = $diag_table ? repairly_pick_column($diag_cols, ['tipo', 'tipo_diagnostico', 'categoria']) : null;
$diag_col_desc = $diag_table ? repairly_pick_column($diag_cols, ['descripcion', 'detalle', 'notas', 'comentario']) : null;
$diag_col_cost = $diag_table ? repairly_pick_column($diag_cols, ['costo_estimado', 'costo', 'precio_estimado', 'monto_estimado']) : null;
$diag_col_fecha = $diag_table ? repairly_pick_column($diag_cols, ['fecha', 'fecha_diagnostico', 'fecha_registro']) : null;

if ($current_page === 'diagnosticos' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $post_action = $_POST['diag_action'] ?? '';
    if (!is_string($post_action)) {
        $post_action = '';
    }

    if (!$diag_table) {
        header('Location: ?page=diagnosticos&t=err&m=Tabla+no+encontrada');
        exit;
    }

    $idField = 'id_diagnostico';
    foreach (array_keys($diag_cols) as $k) {
        if (strcasecmp((string)$k, 'id_diagnostico') === 0) {
            $idField = $k;
            break;
        }
    }

    $id_orden = (int)($_POST['id_orden'] ?? 0);
    $tipo = isset($_POST['tipo']) && is_string($_POST['tipo']) ? trim($_POST['tipo']) : '';
    $descripcion = isset($_POST['descripcion']) && is_string($_POST['descripcion']) ? trim($_POST['descripcion']) : '';
    $costo_estimado = (float)str_replace(',', '.', (string)($_POST['costo_estimado'] ?? '0'));
    $fecha = isset($_POST['fecha']) && is_string($_POST['fecha']) ? trim($_POST['fecha']) : '';

    if ($post_action === 'create' || $post_action === 'update') {
        if ($diag_col_orden === null) {
            header('Location: ?page=diagnosticos&t=err&m=La+tabla+no+tiene+columna+de+orden');
            exit;
        }

        if ($post_action === 'create') {
            if ($id_orden <= 0) {
                header('Location: ?page=diagnosticos&action=new&t=err&m=Selecciona+una+orden');
                exit;
            }

            $fields = [];
            $types = '';
            $vals = [];

            $add = static function (string $col, string $t, int|float|string $v) use (&$fields, &$types, &$vals): void {
                $fields[] = "`{$col}`";
                $types .= $t;
                $vals[] = $v;
            };

            $add($diag_col_orden, 'i', $id_orden);
            if ($diag_col_tipo !== null) {
                $add($diag_col_tipo, 's', $tipo);
            }
            if ($diag_col_desc !== null) {
                $add($diag_col_desc, 's', $descripcion !== '' ? $descripcion : '—');
            }
            if ($diag_col_cost !== null) {
                $add($diag_col_cost, 'd', $costo_estimado);
            }
            if ($diag_col_fecha !== null) {
                $fechaIns = $fecha !== '' ? $fecha : date('Y-m-d');
                $add($diag_col_fecha, 's', $fechaIns);
            }

            $sql = 'INSERT INTO `' . $diag_table . '` (' . implode(',', $fields) . ') VALUES (' . implode(',', array_fill(0, count($fields), '?')) . ')';
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                header('Location: ?page=diagnosticos&t=err&m=No+se+pudo+crear+el+diagn%C3%B3stico');
                exit;
            }
            $stmt->bind_param($types, ...$vals);
            $ok = $stmt->execute();
            $stmt->close();
            header('Location: ?page=diagnosticos&t=' . ($ok ? 'ok' : 'err') . '&m=' . ($ok ? 'Diagn%C3%B3stico+creado' : 'Error+al+crear'));
            exit;
        }

        $id = (int)($_POST['id_diagnostico'] ?? 0);
        if ($id <= 0) {
            header('Location: ?page=diagnosticos&t=err&m=ID+inv%C3%A1lido');
            exit;
        }

        $sets = [];
        $typesU = '';
        $valsU = [];

        $push = static function (string $col, string $t, int|float|string $v) use (&$sets, &$typesU, &$valsU): void {
            $sets[] = "`{$col}`=?";
            $typesU .= $t;
            $valsU[] = $v;
        };

        if ($id_orden > 0) {
            $push($diag_col_orden, 'i', $id_orden);
        }
        if ($diag_col_tipo !== null) {
            $push($diag_col_tipo, 's', $tipo);
        }
        if ($diag_col_desc !== null) {
            $push($diag_col_desc, 's', $descripcion !== '' ? $descripcion : '—');
        }
        if ($diag_col_cost !== null) {
            $push($diag_col_cost, 'd', $costo_estimado);
        }
        if ($diag_col_fecha !== null) {
            $push($diag_col_fecha, 's', $fecha !== '' ? $fecha : date('Y-m-d'));
        }

        if ($sets === []) {
            header('Location: ?page=diagnosticos&t=err&m=Nada+que+actualizar');
            exit;
        }

        $sql = "UPDATE `{$diag_table}` SET " . implode(',', $sets) . " WHERE `{$idField}`=? LIMIT 1";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            header('Location: ?page=diagnosticos&t=err&m=No+se+pudo+actualizar');
            exit;
        }
        $typesU .= 'i';
        $valsU[] = $id;
        $stmt->bind_param($typesU, ...$valsU);
        $ok = $stmt->execute();
        $stmt->close();
        header('Location: ?page=diagnosticos&t=' . ($ok ? 'ok' : 'err') . '&m=' . ($ok ? 'Actualizado' : 'Error+al+actualizar'));
        exit;
    }

    if ($post_action === 'delete') {
        $id = (int)($_POST['id_diagnostico'] ?? 0);
        if ($id <= 0) {
            header('Location: ?page=diagnosticos&t=err&m=ID+inv%C3%A1lido');
            exit;
        }
        $stmt = $conn->prepare("DELETE FROM `{$diag_table}` WHERE `{$idField}`=? LIMIT 1");
        if (!$stmt) {
            header('Location: ?page=diagnosticos&t=err&m=No+se+pudo+eliminar');
            exit;
        }
        $stmt->bind_param('i', $id);
        $ok = $stmt->execute();
        $stmt->close();
        header('Location: ?page=diagnosticos&t=' . ($ok ? 'ok' : 'err') . '&m=' . ($ok ? 'Eliminado' : 'Error+al+eliminar'));
        exit;
    }
}
