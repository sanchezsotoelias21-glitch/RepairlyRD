<?php

declare(strict_types=1);

if ($current_page === 'configuracion' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $action = $_POST['config_action'] ?? '';
    if ($action === 'update_user_role') {
        if (empty($auth_is_admin)) {
            header('Location: ?page=configuracion&t=err&m=Sin+permiso');
            exit;
        }
        $uid = (int)($_POST['id_usuario'] ?? 0);
        $newRol = trim((string)($_POST['rol'] ?? ''));
        $allowed = ['administrador', 'tecnico', 'supervisor', 'operador', 'cliente', 'pendiente'];
        $norm = repairly_normalize_role($newRol);
        $okRole = false;
        foreach ($allowed as $a) {
            if ($norm === repairly_normalize_role($a)) {
                $okRole = true;
                break;
            }
        }
        $table = repairly_usuario_table($conn);
        if ($uid <= 0 || !$okRole || $table === '') {
            header('Location: ?page=configuracion&t=err&m=Rol+inv%C3%A1lido');
            exit;
        }
        if ($uid === (int)($usuario_row['id_usuario'] ?? 0) && $norm !== 'administrador') {
            header('Location: ?page=configuracion&t=err&m=No+puedes+quitarte+admin+sin+otro');
            exit;
        }
        $cols = table_columns($conn, $table);
        $rolCol = isset($cols['rol']) ? 'rol' : (isset($cols['ROL']) ? 'ROL' : 'rol');
        $idCol = 'id_usuario';
        foreach (array_keys($cols) as $k) {
            if (strcasecmp((string)$k, 'id_usuario') === 0) {
                $idCol = $k;
                break;
            }
        }
        $sql = "UPDATE `{$table}` SET `{$rolCol}` = ? WHERE `{$idCol}` = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param('si', $newRol, $uid);
            $stmt->execute();
            $stmt->close();
        }
        header('Location: ?page=configuracion&t=ok&m=Rol+actualizado');
        exit;
    }
}
