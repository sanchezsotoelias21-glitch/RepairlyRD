<?php

declare(strict_types=1);

$pieza_table = pick_table($conn, ['pieza', 'Pieza']);
$pieza_cols = $pieza_table ? table_columns($conn, $pieza_table) : [];

if ($current_page === 'inventario' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $post_action = $_POST['piezas_action'] ?? '';
    if (!is_string($post_action)) {
        $post_action = '';
    }

    if (!$pieza_table) {
        header('Location: ?page=inventario&t=err&m=Tabla+pieza+no+encontrada');
        exit;
    }

    $idField = 'id_pieza';
    foreach (array_keys($pieza_cols) as $k) {
        if (strcasecmp((string)$k, 'id_pieza') === 0) {
            $idField = $k;
            break;
        }
    }

    $nombre = trim((string)($_POST['nombre'] ?? ''));
    $referencia = trim((string)($_POST['referencia'] ?? ''));
    $precio_compra = (float)str_replace(',', '.', (string)($_POST['precio_compra'] ?? '0'));
    $precio_venta = (float)str_replace(',', '.', (string)($_POST['precio_venta'] ?? '0'));
    $stock = (int)($_POST['stock'] ?? 0);

    if ($post_action === 'create' || $post_action === 'update') {
        $row = [
            'nombre' => $nombre,
            'referencia' => $referencia,
            'precio_compra' => $precio_compra,
            'precio_venta' => $precio_venta,
            'stock' => $stock,
        ];
        if ($post_action === 'create') {
            if ($nombre === '') {
                header('Location: ?page=inventario&action=new&t=err&m=Nombre+obligatorio');
                exit;
            }
            $fields = [];
            $types = '';
            $vals = [];
            foreach ($row as $col => $val) {
                if (!isset($pieza_cols[$col])) {
                    continue;
                }
                $fields[] = "`{$col}`";
                if ($col === 'stock') {
                    $types .= 'i';
                    $vals[] = (int)$val;
                } elseif (str_contains($col, 'precio')) {
                    $types .= 'd';
                    $vals[] = (float)$val;
                } else {
                    $types .= 's';
                    $vals[] = (string)$val;
                }
            }
            if (empty($fields)) {
                header('Location: ?page=inventario&t=err&m=Sin+columnas');
                exit;
            }
            $sql = "INSERT INTO `{$pieza_table}` (" . implode(',', $fields) . ') VALUES (' . implode(',', array_fill(0, count($fields), '?')) . ')';
            $stmt = $conn->prepare($sql);
            if ($stmt && $types !== '') {
                $stmt->bind_param($types, ...$vals);
                $stmt->execute();
                $stmt->close();
            }
            header('Location: ?page=inventario&t=ok&m=Pieza+creada');
            exit;
        }
        $id = (int)($_POST['id_pieza'] ?? 0);
        if ($id <= 0 || $nombre === '') {
            header('Location: ?page=inventario&t=err&m=Datos+inv%C3%A1lidos');
            exit;
        }
        $sets = [];
        $typesU = '';
        $valsU = [];
        foreach ($row as $col => $val) {
            if (!isset($pieza_cols[$col])) {
                continue;
            }
            $sets[] = "`{$col}`=?";
            if ($col === 'stock') {
                $typesU .= 'i';
                $valsU[] = (int)$val;
            } elseif (str_contains($col, 'precio')) {
                $typesU .= 'd';
                $valsU[] = (float)$val;
            } else {
                $typesU .= 's';
                $valsU[] = (string)$val;
            }
        }
        $sql = "UPDATE `{$pieza_table}` SET " . implode(',', $sets) . " WHERE `{$idField}`=? LIMIT 1";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $typesU .= 'i';
            $valsU[] = $id;
            $stmt->bind_param($typesU, ...$valsU);
            $stmt->execute();
            $stmt->close();
        }
        header('Location: ?page=inventario&t=ok&m=Pieza+actualizada');
        exit;
    }

    if ($post_action === 'delete') {
        $id = (int)($_POST['id_pieza'] ?? 0);
        if ($id <= 0) {
            header('Location: ?page=inventario&t=err&m=ID+inv%C3%A1lido');
            exit;
        }
        $stmt = $conn->prepare("DELETE FROM `{$pieza_table}` WHERE `{$idField}`=? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $stmt->close();
        }
        header('Location: ?page=inventario&t=ok&m=Pieza+eliminada');
        exit;
    }
}
