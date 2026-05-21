<?php



if ($current_page === 'configuracion' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $action = $_POST['config_action'] ?? '';
    if (!is_string($action)) {
        $action = '';
    }
    if ($action === 'update_user_role') {
        $uid = (int)($_POST['id_usuario'] ?? 0);
        $newRol = isset($_POST['rol']) && is_string($_POST['rol']) ? $_POST['rol'] : '';
        $res = repairly_try_update_usuario_rol_admin($conn, (bool)$auth_is_admin, $usuario_row, $uid, $newRol);
        $q = $res['ok'] ? 'ok' : 'err';
        $m = rawurlencode($res['msg']);
        header('Location: ?page=configuracion&t=' . $q . '&m=' . $m);
        exit;
    }
}
