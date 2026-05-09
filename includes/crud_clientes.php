<?php
if ($current_page === 'clientes' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $action = $_POST['clientes_action'] ?? '';
    $nombre = trim($_POST['nombre'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');

    if ($action === 'create') {
        $stmt = $conn->prepare("INSERT INTO Cliente(nombre, telefono, email, direccion) VALUES(?,?,?,?)");
        $stmt->bind_param('ssss', $nombre, $telefono, $email, $direccion);
        $stmt->execute();
    }

    if ($action === 'update') {
        $id = (int)($_POST['id_cliente'] ?? 0);
        $stmt = $conn->prepare("UPDATE Cliente SET nombre=?, telefono=?, email=?, direccion=? WHERE id_cliente=?");
        $stmt->bind_param('ssssi', $nombre, $telefono, $email, $direccion, $id);
        $stmt->execute();
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id_cliente'] ?? 0);
        $stmt = $conn->prepare("DELETE FROM Cliente WHERE id_cliente=?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
    }

    header('Location: ?page=clientes');
    exit;
}
