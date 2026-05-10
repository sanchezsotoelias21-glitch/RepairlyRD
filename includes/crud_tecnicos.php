<?php

declare(strict_types=1);

$tecnico_table = pick_table($conn, ['tecnico', 'Tecnico']);
$tecnico_cols = $tecnico_table ? table_columns($conn, $tecnico_table) : [];

if ($current_page === 'tecnicos' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $post_action = $_POST['tecnicos_action'] ?? '';
    if (!is_string($post_action)) {
        $post_action = '';
    }

    if (!$tecnico_table) {
        header('Location: ?page=tecnicos&t=err&m=Tabla+t%C3%A9cnico+no+encontrada');
        exit;
    }

    $idField = 'id_tecnico';
    foreach (array_keys($tecnico_cols) as $k) {
        if (strcasecmp((string)$k, 'id_tecnico') === 0) {
            $idField = $k;
            break;
        }
    }

    $nombre = trim((string)($_POST['nombre'] ?? ''));
    $telefono = trim((string)($_POST['telefono'] ?? ''));
    $especialidad = trim((string)($_POST['especialidad'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));
    $fecha_contrato = trim((string)($_POST['fecha_contrato'] ?? ''));
    $estado = trim((string)($_POST['estado'] ?? 'activo'));

    if ($post_action === 'create' || $post_action === 'update') {
        $map = [
            'nombre' => $nombre,
            'telefono' => $telefono,
            'especialidad' => $especialidad,
            'email' => $email,
            'fecha_contrato' => $fecha_contrato,
            'estado' => $estado,
        ];

        if ($post_action === 'create') {
            $fields = [];
            $types = '';
            $vals = [];
            foreach ($map as $col => $val) {
                if (!isset($tecnico_cols[$col])) {
                    continue;
                }
                $fields[] = "`{$col}`";
                $types .= 's';
                $vals[] = (string)$val;
            }
            if (empty($fields)) {
                header('Location: ?page=tecnicos&t=err&m=Sin+columnas');
                exit;
            }
            $sql = "INSERT INTO `{$tecnico_table}` (" . implode(',', $fields) . ') VALUES (' . implode(',', array_fill(0, count($fields), '?')) . ')';
            $stmt = $conn->prepare($sql);
            if ($stmt && $types !== '') {
                $stmt->bind_param($types, ...$vals);
                $stmt->execute();
                $stmt->close();
            }
            header('Location: ?page=tecnicos&t=' . ($stmt ? 'ok' : 'err') . '&m=' . ($stmt ? 'Tecnico+creado' : 'Error'));
            exit;
        }

        $id = (int)($_POST['id_tecnico'] ?? 0);
        if ($id <= 0 || $nombre === '') {
            header('Location: ?page=tecnicos&t=err&m=Datos+inv%C3%A1lidos');
            exit;
        }
        $sets = [];
        $typesU = '';
        $valsU = [];
        foreach ($map as $col => $val) {
            if (!isset($tecnico_cols[$col])) {
                continue;
            }
            $sets[] = "`{$col}`=?";
            $typesU .= 's';
            $valsU[] = (string)$val;
        }
        $sql = "UPDATE `{$tecnico_table}` SET " . implode(',', $sets) . " WHERE `{$idField}`=? LIMIT 1";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $typesU .= 'i';
            $valsU[] = $id;
            $stmt->bind_param($typesU, ...$valsU);
            $stmt->execute();
            $stmt->close();
        }
        header('Location: ?page=tecnicos&t=ok&m=Tecnico+actualizado');
        exit;
    }

    if ($post_action === 'delete') {
        $id = (int)($_POST['id_tecnico'] ?? 0);
        if ($id <= 0) {
            header('Location: ?page=tecnicos&t=err&m=ID+inv%C3%A1lido');
            exit;
        }
        $stmt = $conn->prepare("DELETE FROM `{$tecnico_table}` WHERE `{$idField}`=? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $stmt->close();
        }
        header('Location: ?page=tecnicos&t=ok&m=Tecnico+eliminado');
        exit;
    }
}
