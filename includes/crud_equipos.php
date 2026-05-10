<?php
$equipo_table = 'Equipo';
$equipo_cols = $equipo_table ? table_columns($conn, $equipo_table) : [];

if ($current_page === 'equipos' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $post_action = $_POST['equipos_action'] ?? '';

    $tipo = trim($_POST['tipo'] ?? '');
    $marca = trim($_POST['marca'] ?? '');
    $modelo = trim($_POST['modelo'] ?? '');
    $numero_identificacion = trim($_POST['numero_identificacion'] ?? '');
    $tipo_identificacion = trim($_POST['tipo_identificacion'] ?? '');
    $bloqueo_tipo = trim($_POST['bloqueo_tipo'] ?? '');
    $requiere_desbloqueo = isset($_POST['requiere_desbloqueo']) ? 1 : 0;
    $observaciones_ingreso = trim($_POST['observaciones_ingreso'] ?? '');
    $id_cliente = (int)($_POST['id_cliente'] ?? 0);

    if ($post_action === 'create') {

        $stmt = $conn->prepare("INSERT INTO Equipo (tipo, marca, modelo, numero_identificacion, tipo_identificacion, bloqueo_tipo, requiere_desbloqueo, observaciones_ingreso, id_cliente) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('ssssssisi', $tipo, $marca, $modelo, $numero_identificacion, $tipo_identificacion, $bloqueo_tipo, $requiere_desbloqueo, $observaciones_ingreso, $id_cliente);
        $stmt->execute();

        header('Location:?page=equipos&t=ok&m=Equipo+creado');
        exit;
    }

    if ($post_action === 'update') {

        $id = (int)($_POST['id'] ?? 0);

        $stmt = $conn->prepare("UPDATE Equipo SET tipo=?, marca=?, modelo=?, numero_identificacion=?, tipo_identificacion=?, bloqueo_tipo=?, requiere_desbloqueo=?, observaciones_ingreso=?, id_cliente=? WHERE id_equipo=?");
        $stmt->bind_param('ssssssisii', $tipo, $marca, $modelo, $numero_identificacion, $tipo_identificacion, $bloqueo_tipo, $requiere_desbloqueo, $observaciones_ingreso, $id_cliente, $id);
        $stmt->execute();

        header('Location:?page=equipos&t=ok&m=Equipo+actualizado');
        exit;
    }

    if ($post_action === 'delete') {

        $id = (int)($_POST['id'] ?? 0);

        $stmt = $conn->prepare("DELETE FROM Equipo WHERE id_equipo=?");
        $stmt->bind_param('i', $id);
        $stmt->execute();

        header('Location:?page=equipos&t=ok&m=Equipo+eliminado');
        exit;
    }
}
?>
