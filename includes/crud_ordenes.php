<?php
$orden_table = 'Orden_Reparacion';

if ($current_page === 'ordenes' && $_SERVER['REQUEST_METHOD'] === 'POST') {

    require_csrf();

    $post_action = $_POST['orden_action'] ?? '';

    $cliente = (int)($_POST['cliente'] ?? 0);
    $equipo = (int)($_POST['equipo'] ?? 0);
    $tecnico = (int)($_POST['tecnico'] ?? 0);
    $problema = trim($_POST['problema'] ?? '');
    $estado = trim($_POST['estado'] ?? 'Pendiente');
    $costo = (float)($_POST['costo'] ?? 0);

    if ($post_action === 'create') {

        $stmt = $conn->prepare("INSERT INTO Orden_Reparacion (id_cliente, id_equipo, id_tecnico, problema, estado, costo) VALUES (?, ?, ?, ?, ?, ?)");

        $stmt->bind_param('iiissd', $cliente, $equipo, $tecnico, $problema, $estado, $costo);

        $stmt->execute();

        header('Location:?page=ordenes&t=ok&m=Orden+creada');
        exit;
    }

    if ($post_action === 'update') {

        $id = (int)($_POST['id'] ?? 0);

        $stmt = $conn->prepare("UPDATE Orden_Reparacion SET id_cliente=?, id_equipo=?, id_tecnico=?, problema=?, estado=?, costo=? WHERE id_orden=?");

        $stmt->bind_param('iiissdi', $cliente, $equipo, $tecnico, $problema, $estado, $costo, $id);

        $stmt->execute();

        header('Location:?page=ordenes&t=ok&m=Orden+actualizada');
        exit;
    }

    if ($post_action === 'delete') {

        $id = (int)($_POST['id'] ?? 0);

        $stmt = $conn->prepare("DELETE FROM Orden_Reparacion WHERE id_orden=?");
        $stmt->bind_param('i', $id);
        $stmt->execute();

        header('Location:?page=ordenes&t=ok&m=Orden+eliminada');
        exit;
    }
}
?>