<?php
/** @var mysqli $conn */
require_once __DIR__ . '/../../includes/ui_helper.php';

$webhook_url = 'https://repairlyrdoficial.app.n8n.cloud/webhook/send-whatsapp';

function whatsapp_find_clients_table(mysqli $conn): string {
    foreach (['clientes', 'cliente', 'Clientes'] as $table) {
        $check = $conn->query("SHOW TABLES LIKE '{$table}'");
        if ($check && $check->num_rows > 0) {
            return $table;
        }
    }
    return '';
}

function whatsapp_columns(mysqli $conn, string $table): array {
    $columns = [];
    $res = $conn->query("SHOW COLUMNS FROM `{$table}`");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $columns[] = $row['Field'];
        }
    }
    return $columns;
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'send_whatsapp') {
    $number = preg_replace('/\D+/', '', $_POST['number'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if ($number === '' || $message === '') {
        $error = 'Debes completar el número y el mensaje.';
    } else {
        $payload = json_encode([
            'number' => $number,
            'message' => $message
        ]);

        $ch = curl_init($webhook_url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_TIMEOUT => 30
        ]);

        $response = curl_exec($ch);
        $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if ($curl_error) {
            $error = 'Error enviando mensaje: ' . $curl_error;
        } elseif ($http >= 200 && $http < 300) {
            $success = 'Mensaje enviado correctamente por WhatsApp 🚀';
        } else {
            $error = 'El webhook respondió con código ' . $http;
        }
    }
}

$clients_table = whatsapp_find_clients_table($conn);
$clients = [];

if ($clients_table !== '') {
    $columns = whatsapp_columns($conn, $clients_table);

    $id_col = in_array('id_cliente', $columns) ? 'id_cliente' : (in_array('id', $columns) ? 'id' : $columns[0]);
    $name_col = in_array('nombre', $columns) ? 'nombre' : (in_array('nombres', $columns) ? 'nombres' : $columns[1]);
    $phone_col = in_array('telefono', $columns) ? 'telefono' : (in_array('celular', $columns) ? 'celular' : (in_array('whatsapp', $columns) ? 'whatsapp' : ''));

    if ($phone_col !== '') {
        $query = "SELECT `{$id_col}` as id, `{$name_col}` as nombre, `{$phone_col}` as telefono FROM `{$clients_table}` ORDER BY `{$name_col}` ASC LIMIT 300";
        $res = $conn->query($query);

        if ($res) {
            while ($row = $res->fetch_assoc()) {
                if (!empty($row['telefono'])) {
                    $clients[] = $row;
                }
            }
        }
    }
}
?>

<div class="kpi-grid">
    <div class="kpi-card" style="background:#E8FFF1;--kpi-color:#25D366;">
        <div class="kpi-label" style="color:#25D366;">
            <i class="ti ti-brand-whatsapp"></i>
            WhatsApp conectado
        </div>
        <div class="kpi-valor" style="color:#25D366;">
            <?= count($clients) ?>
        </div>
        <div class="kpi-sub">Clientes disponibles</div>
    </div>

    <div class="kpi-card" style="background:#EEF4FF;--kpi-color:#2563EB;">
        <div class="kpi-label" style="color:#2563EB;">
            <i class="ti ti-api"></i>
            Integración n8n
        </div>
        <div class="kpi-valor" style="color:#2563EB;">
            Activa
        </div>
        <div class="kpi-sub">Webhook configurado</div>
    </div>

    <div class="kpi-card" style="background:#FFF7ED;--kpi-color:#F97316;">
        <div class="kpi-label" style="color:#F97316;">
            <i class="ti ti-message-circle"></i>
            Estado
        </div>
        <div class="kpi-valor" style="color:#F97316;">
            Online
        </div>
        <div class="kpi-sub">Servicio listo</div>
    </div>
</div>

<div class="charts-grid" style="margin-top:20px;">
    <section class="charts-card" style="grid-column: span 8;">
        <div class="section-header">
            <div>
                <h3>Enviar mensaje por WhatsApp</h3>
                <span>Selecciona un cliente y envía un mensaje instantáneo.</span>
            </div>
        </div>

        <?php if ($success): ?>
            <div style="background:#E8FFF1;color:#166534;padding:14px 16px;border-radius:14px;margin-bottom:16px;">
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div style="background:#FEE2E2;color:#991B1B;padding:14px 16px;border-radius:14px;margin-bottom:16px;">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="action" value="send_whatsapp">

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
                <div>
                    <label style="display:block;font-weight:600;margin-bottom:8px;">Cliente</label>
                    <select id="cliente_select" class="table-search" style="width:100%;">
                        <option value="">Seleccionar cliente</option>
                        <?php foreach ($clients as $client): ?>
                            <option 
                                value="<?= htmlspecialchars($client['telefono']) ?>"
                                data-name="<?= htmlspecialchars($client['nombre']) ?>"
                            >
                                <?= htmlspecialchars($client['nombre']) ?> — <?= htmlspecialchars($client['telefono']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label style="display:block;font-weight:600;margin-bottom:8px;">Número</label>
                    <input 
                        type="text" 
                        name="number" 
                        id="number_input"
                        class="table-search"
                        placeholder="1809XXXXXXX"
                        required
                    >
                </div>
            </div>

            <div style="margin-bottom:16px;">
                <label style="display:block;font-weight:600;margin-bottom:8px;">Mensaje</label>
                <textarea 
                    name="message"
                    class="table-search"
                    style="width:100%;min-height:160px;padding:16px;resize:vertical;"
                    placeholder="Hola, tu equipo está listo para retirar 🚀"
                    required
                ></textarea>
            </div>

            <button type="submit" class="btn btn-primary">
                <i class="ti ti-send"></i>
                Enviar mensaje
            </button>
        </form>
    </section>

    <section class="charts-card" style="grid-column: span 4;">
        <div class="section-header">
            <div>
                <h3>Plantillas rápidas</h3>
                <span>Mensajes listos para usar.</span>
            </div>
        </div>

        <div style="display:flex;flex-direction:column;gap:12px;">
            <button type="button" class="btn-template" data-message="Hola, tu equipo ya está listo para retirar 🚀">
                Equipo listo
            </button>

            <button type="button" class="btn-template" data-message="Tu equipo está en proceso de reparación. Te mantendremos informado.">
                Equipo en proceso
            </button>

            <button type="button" class="btn-template" data-message="Hemos recibido tu equipo correctamente en RepairlyRD ✅">
                Equipo recibido
            </button>
        </div>
    </section>
</div>

<style>
.btn-template{
    border:none;
    background:#F4F7FB;
    border-radius:14px;
    padding:14px;
    text-align:left;
    cursor:pointer;
    font-weight:600;
    transition:.2s ease;
}
.btn-template:hover{
    background:#E8FFF1;
    transform:translateY(-1px);
}
</style>

<script>
document.getElementById('cliente_select')?.addEventListener('change', function() {
    document.getElementById('number_input').value = this.value || '';
});

document.querySelectorAll('.btn-template').forEach(btn => {
    btn.addEventListener('click', () => {
        const textarea = document.querySelector('textarea[name="message"]');
        textarea.value = btn.dataset.message;
    });
});
</script>
