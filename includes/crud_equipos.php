// ---------------------------
// Equipos (CRUD)
// ---------------------------
$equipo_table = 'Equipo';
$equipo_cols = $equipo_table ? table_columns($conn, $equipo_table) : [];

if ($current_page === 'equipos' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $post_action = $_POST['equipos_action'] ?? '';

    $marca = trim($_POST['marca'] ?? '');
    $modelo = trim($_POST['modelo'] ?? '');
    $serial = trim($_POST['serial'] ?? '');
    $tipo = trim($_POST['tipo'] ?? '');

    if ($post_action === 'create') {

        $stmt = $conn->prepare("INSERT INTO Equipo (marca, modelo, serial, tipo) VALUES (?, ?, ?, ?)");
        $stmt->bind_param('ssss', $marca, $modelo, $serial, $tipo);
        $stmt->execute();

        header('Location:?page=equipos&t=ok&m=Equipo+creado');
        exit;
    }

    if ($post_action === 'update') {

        $id = (int)($_POST['id'] ?? 0);

        $stmt = $conn->prepare("UPDATE Equipo SET marca=?, modelo=?, serial=?, tipo=? WHERE id_equipo=?");
        $stmt->bind_param('ssssi', $marca, $modelo, $serial, $tipo, $id);
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