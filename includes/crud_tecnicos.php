<?php
$tecnico_table = 'Tecnico';

if ($current_page === 'tecnicos' && $_SERVER['REQUEST_METHOD'] === 'POST') {

    require_csrf();

    $post_action = $_POST['tecnicos_action'] ?? '';

    $nombre = trim($_POST['nombre'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $especialidad = trim($_POST['especialidad'] ?? '');

    if ($post_action === 'create') {

        $stmt = $conn->prepare("INSERT INTO Tecnico (nombre, telefono, especialidad) VALUES (?, ?, ?)");
        $stmt->bind_param('sss', $nombre, $telefono, $especialidad);
        $stmt->execute();

        header('Location:?page=tecnicos&t=ok&m=Tecnico+creado');
        exit;
    }

    if ($post_action === 'update') {

        $id = (int)($_POST['id'] ?? 0);

        $stmt = $conn->prepare("UPDATE Tecnico SET nombre=?, telefono=?, especialidad=? WHERE id_tecnico=?");
        $stmt->bind_param('sssi', $nombre, $telefono, $especialidad, $id);
        $stmt->execute();

        header('Location:?page=tecnicos&t=ok&m=Tecnico+actualizado');
        exit;
    }

    if ($post_action === 'delete') {

        $id = (int)($_POST['id'] ?? 0);

        $stmt = $conn->prepare("DELETE FROM Tecnico WHERE id_tecnico=?");
        $stmt->bind_param('i', $id);
        $stmt->execute();

        header('Location:?page=tecnicos&t=ok&m=Tecnico+eliminado');
        exit;
    }
}
?>