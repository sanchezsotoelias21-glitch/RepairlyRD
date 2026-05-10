<?php
$clientes = $conn->query("SELECT id_cliente, nombre FROM Cliente ORDER BY nombre ASC");
$equipos = $conn->query("SELECT e.*, c.nombre AS cliente FROM Equipo e LEFT JOIN Cliente c ON e.id_cliente = c.id_cliente ORDER BY e.id_equipo DESC");
?>

<div class="charts-card">
    <div class="card-title">Registro de Equipos</div>

    <form method="POST" style="display:grid;grid-template-columns:repeat(2,1fr);gap:10px;margin-top:15px;">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <input type="hidden" name="equipos_action" value="create">

        <input type="text" name="tipo" placeholder="Tipo" required>
        <input type="text" name="marca" placeholder="Marca" required>
        <input type="text" name="modelo" placeholder="Modelo" required>
        <input type="text" name="numero_identificacion" placeholder="IMEI / Serial">

        <select name="tipo_identificacion">
            <option value="IMEI">IMEI</option>
            <option value="SERIAL">SERIAL</option>
            <option value="MAC">MAC</option>
        </select>

        <input type="text" name="bloqueo_tipo" placeholder="Bloqueo">

        <select name="id_cliente" required>
            <option value="">Cliente</option>
            <?php while($c = $clientes->fetch_assoc()): ?>
                <option value="<?= $c['id_cliente'] ?>"><?= h($c['nombre']) ?></option>
            <?php endwhile; ?>
        </select>

        <label>
            <input type="checkbox" name="requiere_desbloqueo"> Requiere desbloqueo
        </label>

        <textarea name="observaciones_ingreso" placeholder="Observaciones" style="grid-column:1/3"></textarea>

        <button type="submit">Guardar Equipo</button>
    </form>
</div>

<div class="charts-card" style="margin-top:20px;">
    <div class="card-title">Lista de Equipos</div>

    <table border="0" width="100%" cellpadding="10">
        <tr>
            <th>ID</th>
            <th>Cliente</th>
            <th>Marca</th>
            <th>Modelo</th>
            <th>Identificación</th>
            <th>Acciones</th>
        </tr>

        <?php while($r = $equipos->fetch_assoc()): ?>
            <tr>
                <td><?= $r['id_equipo'] ?></td>
                <td><?= h($r['cliente']) ?></td>
                <td><?= h($r['marca']) ?></td>
                <td><?= h($r['modelo']) ?></td>
                <td><?= h($r['numero_identificacion']) ?></td>
                <td>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                        <input type="hidden" name="equipos_action" value="delete">
                        <input type="hidden" name="id" value="<?= $r['id_equipo'] ?>">
                        <button type="submit">Eliminar</button>
                    </form>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>
</div>
