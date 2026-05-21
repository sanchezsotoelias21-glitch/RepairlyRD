<?php



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

    $nombre = isset($_POST['nombre']) && is_string($_POST['nombre']) ? trim($_POST['nombre']) : '';
    $telefono = isset($_POST['telefono']) && is_string($_POST['telefono']) ? trim($_POST['telefono']) : '';
    $especialidad = isset($_POST['especialidad']) && is_string($_POST['especialidad']) ? trim($_POST['especialidad']) : '';
    $email = isset($_POST['email']) && is_string($_POST['email']) ? trim($_POST['email']) : '';
    $fecha_contrato = isset($_POST['fecha_contrato']) && is_string($_POST['fecha_contrato']) ? trim($_POST['fecha_contrato']) : '';
    $estado = isset($_POST['estado']) && is_string($_POST['estado']) ? trim($_POST['estado']) : 'activo';

    if ($post_action === 'create') {
        if (isset($tecnico_cols['nombre']) && $nombre === '') {
            header('Location: ?page=tecnicos&action=new&t=err&m=El+nombre+es+obligatorio');
            exit;
        }

        $fields = [];
        $placeholders = [];
        $types = '';
        $values = [];

        if (isset($tecnico_cols['nombre'])) {
            $fields[] = 'nombre';
            $placeholders[] = '?';
            $types .= 's';
            $values[] = $nombre;
        }
        if (isset($tecnico_cols['telefono'])) {
            $fields[] = 'telefono';
            $placeholders[] = '?';
            $types .= 's';
            $values[] = $telefono;
        }
        if (isset($tecnico_cols['especialidad'])) {
            $fields[] = 'especialidad';
            $placeholders[] = '?';
            $types .= 's';
            $values[] = $especialidad;
        }
        if (isset($tecnico_cols['email'])) {
            $fields[] = 'email';
            $placeholders[] = '?';
            $types .= 's';
            $values[] = $email;
        }
        if (isset($tecnico_cols['fecha_contrato'])) {
            $fields[] = 'fecha_contrato';
            $placeholders[] = '?';
            $types .= 's';
            $values[] = $fecha_contrato;
        }
        if (isset($tecnico_cols['estado'])) {
            $fields[] = 'estado';
            $placeholders[] = '?';
            $types .= 's';
            $values[] = $estado;
        }

        if (empty($fields)) {
            header('Location: ?page=tecnicos&action=new&t=err&m=Sin+columnas+v%C3%A1lidas');
            exit;
        }

        $sql = 'INSERT INTO `' . $tecnico_table . '` (' . implode(',', array_map(static fn ($f) => "`{$f}`", $fields)) . ') VALUES (' . implode(',', $placeholders) . ')';
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            header('Location: ?page=tecnicos&t=err&m=No+se+pudo+crear+el+t%C3%A9cnico');
            exit;
        }
        if ($types !== '') {
            $stmt->bind_param($types, ...$values);
        }
        $ok = $stmt->execute();
        $stmt->close();
        header('Location: ?page=tecnicos&t=' . ($ok ? 'ok' : 'err') . '&m=' . ($ok ? 'T%C3%A9cnico+creado' : 'Error+al+crear'));
        exit;
    }

    if ($post_action === 'update') {
        $id = isset($_POST['id_tecnico']) ? (int)$_POST['id_tecnico'] : 0;
        if ($id <= 0) {
            header('Location: ?page=tecnicos&t=err&m=ID+inv%C3%A1lido');
            exit;
        }
        if (isset($tecnico_cols['nombre']) && $nombre === '') {
            header('Location: ?page=tecnicos&action=edit&id=' . $id . '&t=err&m=El+nombre+es+obligatorio');
            exit;
        }

        $sets = [];
        $types = '';
        $values = [];

        if (isset($tecnico_cols['nombre'])) {
            $sets[] = '`nombre`=?';
            $types .= 's';
            $values[] = $nombre;
        }
        if (isset($tecnico_cols['telefono'])) {
            $sets[] = '`telefono`=?';
            $types .= 's';
            $values[] = $telefono;
        }
        if (isset($tecnico_cols['especialidad'])) {
            $sets[] = '`especialidad`=?';
            $types .= 's';
            $values[] = $especialidad;
        }
        if (isset($tecnico_cols['email'])) {
            $sets[] = '`email`=?';
            $types .= 's';
            $values[] = $email;
        }
        if (isset($tecnico_cols['fecha_contrato'])) {
            $sets[] = '`fecha_contrato`=?';
            $types .= 's';
            $values[] = $fecha_contrato;
        }
        if (isset($tecnico_cols['estado'])) {
            $sets[] = '`estado`=?';
            $types .= 's';
            $values[] = $estado;
        }

        if (empty($sets)) {
            header('Location: ?page=tecnicos&t=err&m=Nada+que+actualizar');
            exit;
        }

        $sql = "UPDATE `{$tecnico_table}` SET " . implode(',', $sets) . " WHERE `{$idField}`=? LIMIT 1";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            header('Location: ?page=tecnicos&t=err&m=No+se+pudo+actualizar');
            exit;
        }
        $types2 = $types . 'i';
        $values[] = $id;
        $stmt->bind_param($types2, ...$values);
        $ok = $stmt->execute();
        $stmt->close();
        header('Location: ?page=tecnicos&t=' . ($ok ? 'ok' : 'err') . '&m=' . ($ok ? 'T%C3%A9cnico+actualizado' : 'Error+al+actualizar'));
        exit;
    }

    if ($post_action === 'delete') {
        $id = isset($_POST['id_tecnico']) ? (int)$_POST['id_tecnico'] : 0;
        if ($id <= 0) {
            header('Location: ?page=tecnicos&t=err&m=ID+inv%C3%A1lido');
            exit;
        }
        $stmt = $conn->prepare("DELETE FROM `{$tecnico_table}` WHERE `{$idField}`=? LIMIT 1");
        if (!$stmt) {
            header('Location: ?page=tecnicos&t=err&m=No+se+pudo+eliminar');
            exit;
        }
        $stmt->bind_param('i', $id);
        $ok = $stmt->execute();
        $stmt->close();
        header('Location: ?page=tecnicos&t=' . ($ok ? 'ok' : 'err') . '&m=' . ($ok ? 'T%C3%A9cnico+eliminado' : 'Error+al+eliminar'));
        exit;
    }
}
