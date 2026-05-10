<?php

declare(strict_types=1);

$pieza_table = pick_table($conn, [
    'pieza', 'Pieza', 'PIEZA',
    'inventario', 'Inventario', 'INVENTARIO',
    'repuesto', 'Repuesto', 'REPUESTO',
    'articulo', 'Articulo', 'ARTICULO', 'artículo',
    'producto', 'Producto', 'PRODUCTO',
    'stock_pieza', 'Stock_Pieza',
]);
$pieza_cols = $pieza_table ? table_columns($conn, $pieza_table) : [];

$pz_col_nombre = $pieza_table ? repairly_pick_column($pieza_cols, [
    'nombre', 'nombre_pieza', 'nombre_articulo', 'nombre_producto', 'nom_pieza', 'nom_articulo',
    'descripcion', 'descripcion_pieza', 'desc_pieza', 'desc_corta', 'descripcion_corta',
    'articulo', 'producto', 'item', 'titulo', 'etiqueta', 'pieza', 'label', 'name',
    'denominacion',
]) : null;
if ($pieza_table && $pz_col_nombre === null) {
    $pz_col_nombre = repairly_guess_pieza_nombre_column($pieza_cols);
}
$pz_col_ref = $pieza_table ? repairly_pick_column($pieza_cols, ['referencia', 'codigo', 'sku', 'ref']) : null;
$pz_col_pc = $pieza_table ? repairly_pick_column($pieza_cols, ['precio_compra', 'costo', 'precio_costo']) : null;
$pz_col_pv = $pieza_table ? repairly_pick_column($pieza_cols, ['precio_venta', 'precio', 'precio_publico']) : null;
$pz_col_stock = $pieza_table ? repairly_pick_column($pieza_cols, ['stock', 'cantidad', 'existencia', 'unidades']) : null;

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

    $nombre = isset($_POST['nombre']) && is_string($_POST['nombre']) ? trim($_POST['nombre']) : '';
    $referencia = isset($_POST['referencia']) && is_string($_POST['referencia']) ? trim($_POST['referencia']) : '';
    $precio_compra = (float)str_replace(',', '.', (string)($_POST['precio_compra'] ?? '0'));
    $precio_venta = (float)str_replace(',', '.', (string)($_POST['precio_venta'] ?? '0'));
    $stock = (int)($_POST['stock'] ?? 0);

    if ($post_action === 'create' || $post_action === 'update') {
        if ($pz_col_nombre === null) {
            header('Location: ?page=inventario&t=err&m=La+tabla+no+tiene+columna+nombre');
            exit;
        }

        if ($post_action === 'create') {
            if ($nombre === '') {
                header('Location: ?page=inventario&action=new&t=err&m=Nombre+obligatorio');
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

            $add($pz_col_nombre, 's', $nombre);
            if ($pz_col_ref !== null) {
                $add($pz_col_ref, 's', $referencia);
            }
            if ($pz_col_pc !== null) {
                $add($pz_col_pc, 'd', $precio_compra);
            }
            if ($pz_col_pv !== null) {
                $add($pz_col_pv, 'd', $precio_venta);
            }
            if ($pz_col_stock !== null) {
                $add($pz_col_stock, 'i', $stock);
            }

            if ($fields === []) {
                header('Location: ?page=inventario&t=err&m=Sin+columnas');
                exit;
            }

            $sql = 'INSERT INTO `' . $pieza_table . '` (' . implode(',', $fields) . ') VALUES (' . implode(',', array_fill(0, count($fields), '?')) . ')';
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                header('Location: ?page=inventario&t=err&m=No+se+pudo+crear+la+pieza');
                exit;
            }
            $stmt->bind_param($types, ...$vals);
            $ok = $stmt->execute();
            $stmt->close();
            header('Location: ?page=inventario&t=' . ($ok ? 'ok' : 'err') . '&m=' . ($ok ? 'Pieza+creada' : 'Error+al+crear'));
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

        $push = static function (string $col, string $t, int|float|string $v) use (&$sets, &$typesU, &$valsU): void {
            $sets[] = "`{$col}`=?";
            $typesU .= $t;
            $valsU[] = $v;
        };

        $push($pz_col_nombre, 's', $nombre);
        if ($pz_col_ref !== null) {
            $push($pz_col_ref, 's', $referencia);
        }
        if ($pz_col_pc !== null) {
            $push($pz_col_pc, 'd', $precio_compra);
        }
        if ($pz_col_pv !== null) {
            $push($pz_col_pv, 'd', $precio_venta);
        }
        if ($pz_col_stock !== null) {
            $push($pz_col_stock, 'i', $stock);
        }

        if ($sets === []) {
            header('Location: ?page=inventario&t=err&m=Nada+que+actualizar');
            exit;
        }

        $sql = "UPDATE `{$pieza_table}` SET " . implode(',', $sets) . " WHERE `{$idField}`=? LIMIT 1";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            header('Location: ?page=inventario&t=err&m=No+se+pudo+actualizar');
            exit;
        }
        $typesU .= 'i';
        $valsU[] = $id;
        $stmt->bind_param($typesU, ...$valsU);
        $ok = $stmt->execute();
        $stmt->close();
        header('Location: ?page=inventario&t=' . ($ok ? 'ok' : 'err') . '&m=' . ($ok ? 'Pieza+actualizada' : 'Error+al+actualizar'));
        exit;
    }

    if ($post_action === 'delete') {
        $id = (int)($_POST['id_pieza'] ?? 0);
        if ($id <= 0) {
            header('Location: ?page=inventario&t=err&m=ID+inv%C3%A1lido');
            exit;
        }
        $stmt = $conn->prepare("DELETE FROM `{$pieza_table}` WHERE `{$idField}`=? LIMIT 1");
        if (!$stmt) {
            header('Location: ?page=inventario&t=err&m=No+se+pudo+eliminar');
            exit;
        }
        $stmt->bind_param('i', $id);
        $ok = $stmt->execute();
        $stmt->close();
        header('Location: ?page=inventario&t=' . ($ok ? 'ok' : 'err') . '&m=' . ($ok ? 'Pieza+eliminada' : 'Error+al+eliminar'));
        exit;
    }
}
