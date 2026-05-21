<?php



$garantia_table_name = pick_table($conn, ['garantia', 'Garantia', 'GARANTIA']);
$garantia_cols = $garantia_table_name ? table_columns($conn, $garantia_table_name) : [];

$gar_col_orden = $garantia_table_name ? repairly_pick_column($garantia_cols, ['id_orden', 'orden_id']) : null;
$gar_col_fi = $garantia_table_name ? repairly_pick_column($garantia_cols, ['fecha_inicio', 'inicio', 'fecha_ini']) : null;
$gar_col_ff = $garantia_table_name ? repairly_pick_column($garantia_cols, ['fecha_fin', 'fin', 'fecha_vencimiento', 'vencimiento']) : null;
$gar_col_cob = $garantia_table_name ? repairly_pick_column($garantia_cols, ['cobertura_dias', 'dias', 'duracion_dias']) : null;
$gar_col_tipo = $garantia_table_name ? repairly_pick_column($garantia_cols, ['tipo', 'tipo_garantia']) : null;
$gar_col_est = $garantia_table_name ? repairly_pick_column($garantia_cols, ['estado']) : null;

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
    $fecha_inicio = isset($_POST['fecha_inicio']) && is_string($_POST['fecha_inicio']) ? trim($_POST['fecha_inicio']) : '';
    $fecha_fin = isset($_POST['fecha_fin']) && is_string($_POST['fecha_fin']) ? trim($_POST['fecha_fin']) : '';
    $cobertura_dias = (int)($_POST['cobertura_dias'] ?? 0);
    $tipo = isset($_POST['tipo']) && is_string($_POST['tipo']) ? trim($_POST['tipo']) : '';
    $estado = isset($_POST['estado']) && is_string($_POST['estado']) ? trim($_POST['estado']) : 'activa';

    if ($post_action === 'create' || $post_action === 'update') {
        if ($gar_col_orden === null) {
            header('Location: ?page=garantias&t=err&m=La+tabla+no+tiene+columna+de+orden');
            exit;
        }

        if ($gar_col_ff !== null && $fecha_fin === '') {
            $baseIni = $fecha_inicio !== '' ? $fecha_inicio : date('Y-m-d');
            if ($cobertura_dias > 0) {
                $fecha_fin = date('Y-m-d', strtotime('+' . max(0, $cobertura_dias) . ' days', strtotime($baseIni)));
            } else {
                $fecha_fin = $baseIni;
            }
        }

        if ($post_action === 'create') {
            if ($id_orden <= 0) {
                header('Location: ?page=garantias&action=new&t=err&m=Orden+requerida');
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

            $add($gar_col_orden, 'i', $id_orden);
            if ($gar_col_fi !== null) {
                $add($gar_col_fi, 's', $fecha_inicio !== '' ? $fecha_inicio : date('Y-m-d'));
            }
            if ($gar_col_ff !== null) {
                $add($gar_col_ff, 's', $fecha_fin);
            }
            if ($gar_col_cob !== null) {
                $add($gar_col_cob, 'i', $cobertura_dias);
            }
            if ($gar_col_tipo !== null) {
                $add($gar_col_tipo, 's', $tipo !== '' ? $tipo : 'estándar');
            }
            if ($gar_col_est !== null) {
                $add($gar_col_est, 's', $estado !== '' ? $estado : 'activa');
            }

            if ($fields === []) {
                header('Location: ?page=garantias&t=err&m=Sin+columnas');
                exit;
            }

            $sql = 'INSERT INTO `' . $garantia_table_name . '` (' . implode(',', $fields) . ') VALUES (' . implode(',', array_fill(0, count($fields), '?')) . ')';
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                header('Location: ?page=garantias&t=err&m=No+se+pudo+crear+la+garant%C3%ADa');
                exit;
            }
            $stmt->bind_param($types, ...$vals);
            $ok = $stmt->execute();
            $stmt->close();
            header('Location: ?page=garantias&t=' . ($ok ? 'ok' : 'err') . '&m=' . ($ok ? 'Garant%C3%ADa+creada' : 'Error+al+crear'));
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

        $push = static function (string $col, string $t, int|float|string $v) use (&$sets, &$typesU, &$valsU): void {
            $sets[] = "`{$col}`=?";
            $typesU .= $t;
            $valsU[] = $v;
        };

        if ($id_orden > 0) {
            $push($gar_col_orden, 'i', $id_orden);
        }
        if ($gar_col_fi !== null) {
            $push($gar_col_fi, 's', $fecha_inicio !== '' ? $fecha_inicio : date('Y-m-d'));
        }
        if ($gar_col_ff !== null) {
            $push($gar_col_ff, 's', $fecha_fin);
        }
        if ($gar_col_cob !== null) {
            $push($gar_col_cob, 'i', $cobertura_dias);
        }
        if ($gar_col_tipo !== null) {
            $push($gar_col_tipo, 's', $tipo);
        }
        if ($gar_col_est !== null) {
            $push($gar_col_est, 's', $estado);
        }

        if ($sets === []) {
            header('Location: ?page=garantias&t=err&m=Nada+que+actualizar');
            exit;
        }

        $sql = "UPDATE `{$garantia_table_name}` SET " . implode(',', $sets) . " WHERE `{$idField}`=? LIMIT 1";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            header('Location: ?page=garantias&t=err&m=No+se+pudo+actualizar');
            exit;
        }
        $typesU .= 'i';
        $valsU[] = $id;
        $stmt->bind_param($typesU, ...$valsU);
        $ok = $stmt->execute();
        $stmt->close();
        header('Location: ?page=garantias&t=' . ($ok ? 'ok' : 'err') . '&m=' . ($ok ? 'Actualizada' : 'Error+al+actualizar'));
        exit;
    }

    if ($post_action === 'delete') {
        $id = (int)($_POST['id_garantia'] ?? 0);
        if ($id <= 0) {
            header('Location: ?page=garantias&t=err&m=ID+inv%C3%A1lido');
            exit;
        }
        $stmt = $conn->prepare("DELETE FROM `{$garantia_table_name}` WHERE `{$idField}`=? LIMIT 1");
        if (!$stmt) {
            header('Location: ?page=garantias&t=err&m=No+se+pudo+eliminar');
            exit;
        }
        $stmt->bind_param('i', $id);
        $ok = $stmt->execute();
        $stmt->close();
        header('Location: ?page=garantias&t=' . ($ok ? 'ok' : 'err') . '&m=' . ($ok ? 'Eliminada' : 'Error+al+eliminar'));
        exit;
    }
}
