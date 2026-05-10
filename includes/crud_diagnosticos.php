<?php

declare(strict_types=1);

$diag_table = pick_table($conn, ['diagnostico', 'Diagnostico']);
$diag_cols = $diag_table ? table_columns($conn, $diag_table) : [];

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
    $tipo = trim((string)($_POST['tipo'] ?? ''));
    $descripcion = trim((string)($_POST['descripcion'] ?? ''));
    $costo_estimado = (float)str_replace(',', '.', (string)($_POST['costo_estimado'] ?? '0'));
    $fecha = trim((string)($_POST['fecha'] ?? date('Y-m-d')));

    if ($post_action === 'create' || $post_action === 'update') {
        if ($post_action === 'create') {
            $fields = [];
            $types = '';
            $vals = [];
            $map = [
                'id_orden' => $id_orden,
                'tipo' => $tipo,
                'descripcion' => $descripcion,
                'costo_estimado' => $costo_estimado,
                'fecha' => $fecha,
            ];
            foreach ($map as $col => $val) {
                if (!isset($diag_cols[$col])) {
                    continue;
                }
                $fields[] = "`{$col}`";
                if ($col === 'id_orden') {
                    $types .= 'i';
                    $vals[] = (int)$val;
                } elseif ($col === 'costo_estimado') {
                    $types .= 'd';
                    $vals[] = (float)$val;
                } else {
                    $types .= 's';
                    $vals[] = (string)$val;
                }
            }
            if (empty($fields) || $id_orden <= 0) {
                header('Location: ?page=diagnosticos&action=new&t=err&m=Datos+insuficientes');
                exit;
            }
            $sql = "INSERT INTO `{$diag_table}` (" . implode(',', $fields) . ') VALUES (' . implode(',', array_fill(0, count($fields), '?')) . ')';
            $stmt = $conn->prepare($sql);
            if ($stmt && $types !== '') {
                $stmt->bind_param($types, ...$vals);
                $stmt->execute();
                $stmt->close();
            }
            header('Location: ?page=diagnosticos&t=ok&m=Diagn%C3%B3stico+creado');
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
        foreach (
            [
            'id_orden' => $id_orden,
            'tipo' => $tipo,
            'descripcion' => $descripcion,
            'costo_estimado' => $costo_estimado,
            'fecha' => $fecha,
            ] as $col => $val
        ) {
            if (!isset($diag_cols[$col])) {
                continue;
            }
            $sets[] = "`{$col}`=?";
            if ($col === 'id_orden') {
                $typesU .= 'i';
                $valsU[] = (int)$val;
            } elseif ($col === 'costo_estimado') {
                $typesU .= 'd';
                $valsU[] = (float)$val;
            } else {
                $typesU .= 's';
                $valsU[] = (string)$val;
            }
        }
        $sql = "UPDATE `{$diag_table}` SET " . implode(',', $sets) . " WHERE `{$idField}`=? LIMIT 1";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $typesU .= 'i';
            $valsU[] = $id;
            $stmt->bind_param($typesU, ...$valsU);
            $stmt->execute();
            $stmt->close();
        }
        header('Location: ?page=diagnosticos&t=ok&m=Actualizado');
        exit;
    }

    if ($post_action === 'delete') {
        $id = (int)($_POST['id_diagnostico'] ?? 0);
        if ($id <= 0) {
            header('Location: ?page=diagnosticos&t=err&m=ID+inv%C3%A1lido');
            exit;
        }
        $stmt = $conn->prepare("DELETE FROM `{$diag_table}` WHERE `{$idField}`=? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $stmt->close();
        }
        header('Location: ?page=diagnosticos&t=ok&m=Eliminado');
        exit;
    }
}
