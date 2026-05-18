<?php
$deliveries = [];
$query = "SELECT * FROM Deliveries ORDER BY FechaCreacion DESC";
$result = $conn->query($query);
if ($result) {
    while($row = $result->fetch_assoc()) {
        $deliveries[] = $row;
    }
}
?>
<div class="card">
<h2>🚚 Deliveries</h2>
<p>Gestión de deliveries y tracking.</p>
<table class="table">
<thead><tr><th>ID</th><th>Tracking</th><th>Estado</th><th>Salida</th></tr></thead>
<tbody>
<?php foreach($deliveries as $delivery): ?>
<tr>
<td><?= htmlspecialchars($delivery['IdDelivery']) ?></td>
<td><?= htmlspecialchars($delivery['CodigoTracking']) ?></td>
<td><?= htmlspecialchars($delivery['Estado']) ?></td>
<td><?= htmlspecialchars($delivery['FechaSalida']) ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>