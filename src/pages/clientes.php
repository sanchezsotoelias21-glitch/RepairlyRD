<h1>Clientes</h1>

<?php

$res = $conn->query("
    SELECT *
    FROM Cliente
    ORDER BY id_cliente DESC
");

?>

<table class="table-modern">

<tr>
    <th>ID</th>
    <th>Nombre</th>
    <th>Telefono</th>
    <th>Email</th>
</tr>

<?php while($row = $res->fetch_assoc()): ?>

<tr>

    <td><?= $row['id_cliente'] ?></td>

    <td><?= h($row['nombre']) ?></td>

    <td><?= h($row['telefono']) ?></td>

    <td><?= h($row['email']) ?></td>

</tr>

<?php endwhile; ?>

</table>