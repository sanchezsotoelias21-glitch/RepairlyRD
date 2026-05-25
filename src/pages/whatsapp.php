<?php

/** @var mysqli $conn */
require_once __DIR__ . '/../../includes/ui_helper.php';

$success = '';
$error = '';

// Manejo de envío de mensajes
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_message') {
    $telefono = trim($_POST['telefono'] ?? '');
    $mensaje = trim($_POST['mensaje'] ?? '');
    
    if ($telefono !== '' && $mensaje !== '') {
        $sid = getenv('TWILIO_SID') ?: 'YOUR_TWILIO_SID';
        $token = getenv('TWILIO_TOKEN') ?: 'YOUR_TWILIO_TOKEN';
        
        // Formatear número de teléfono
        if (!preg_match('/^whatsapp:\+/', $telefono)) {
            $telefono = 'whatsapp:+' . preg_replace('/\D+/', '', $telefono);
        }
        
        $url = "https://api.twilio.com/2010-04-01/Accounts/$sid/Messages.json";
        
        $data = [
            'From' => 'whatsapp:+14155238886',
            'To' => $telefono,
            'Body' => $mensaje
        ];
        
        $options = [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERPWD => "$sid:$token",
            CURLOPT_POSTFIELDS => http_build_query($data),
        ];
        
        $ch = curl_init();
        curl_setopt_array($ch, $options);
        $response = curl_exec($ch);
        curl_close($ch);
        
        $response_data = json_decode($response, true);
        
        if (isset($response_data['sid'])) {
            $success = 'Mensaje enviado exitosamente';
            
            // Guardar mensaje en historial
            $stmt = $conn->prepare("INSERT INTO WhatsAppMessages (Telefono, Mensaje, Estado, FechaEnvio, TwilioSid) VALUES (?, ?, 'Enviado', NOW(), ?)");
            if ($stmt) {
                $stmt->bind_param('sss', $telefono, $mensaje, $response_data['sid']);
                $stmt->execute();
                $stmt->close();
            }
        } else {
            $error = 'Error al enviar mensaje: ' . ($response_data['message'] ?? 'Error desconocido');
        }
    } else {
        $error = 'Debes completar el número y el mensaje';
    }
}

// Cargar historial de mensajes
$mensajes = [];
$query = "SELECT * FROM WhatsAppMessages ORDER BY FechaEnvio DESC LIMIT 50";
$result = $conn->query($query);
if ($result) {
    while($row = $result->fetch_assoc()) {
        $mensajes[] = $row;
    }
}

// Cargar clientes para el selector
$clientes = [];
$clientes_table = '';
$tables_to_check = ['Cliente', 'clientes', 'cliente', 'Clientes'];
foreach ($tables_to_check as $table) {
    $check = $conn->query("SHOW TABLES LIKE '{$table}'");
    if ($check && $check->num_rows > 0) {
        $clientes_table = $table;
        break;
    }
}

if ($clientes_table !== '') {
    $query_clientes = "SELECT * FROM `{$clientes_table}` ORDER BY nombre ASC LIMIT 100";
    $result_clientes = $conn->query($query_clientes);
    if ($result_clientes) {
        while($row = $result_clientes->fetch_assoc()) {
            $clientes[] = $row;
        }
    }
}
?>

<!-- Interfaz estilo WhatsApp -->
<div style="max-width:1200px;margin:0 auto;background:#E5DDD5;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,0.1);">
    
    <!-- Header estilo WhatsApp -->
    <div style="background:#075E54;padding:15px 20px;display:flex;align-items:center;gap:15px;">
        <div style="width:40px;height:40px;background:#25D366;border-radius:50%;display:flex;align-items:center;justify-content:center;">
            <i class="ti ti-brand-whatsapp" style="color:white;font-size:20px;"></i>
        </div>
        <div style="flex:1;">
            <div style="color:white;font-weight:600;font-size:16px;">WhatsApp Integration</div>
            <div style="color:#128C7E;font-size:12px;">Conectado con Twilio</div>
        </div>
        <div style="color:white;font-size:24px;">
            <i class="ti ti-dots-vertical"></i>
        </div>
    </div>
    
    <!-- Contenedor principal -->
    <div style="display:flex;height:600px;">
        
        <!-- Panel lateral - Lista de contactos -->
        <div style="width:300px;background:white;border-right:1px solid #E5DDD5;display:flex;flex-direction:column;">
            <div style="padding:15px;background:#F0F0F0;">
                <input type="text" placeholder="Buscar contacto..." style="width:100%;padding:8px 12px;border:none;border-radius:20px;background:#E5DDD5;font-size:14px;">
            </div>
            
            <div style="flex:1;overflow-y:auto;">
                <?php foreach ($clientes as $cliente): ?>
                    <div style="padding:12px 15px;border-bottom:1px solid #F0F0F0;cursor:pointer;display:flex;align-items:center;gap:12px;" onclick="seleccionarCliente('<?= htmlspecialchars($cliente['telefono'] ?? '') ?>', '<?= htmlspecialchars($cliente['nombre'] ?? '') ?>')">
                        <div style="width:45px;height:45px;background:#128C7E;border-radius:50%;display:flex;align-items:center;justify-content:center;color:white;font-weight:600;">
                            <?= strtoupper(substr($cliente['nombre'] ?? 'U', 0, 1)) ?>
                        </div>
                        <div style="flex:1;">
                            <div style="font-weight:500;color:#111B21;font-size:14px;"><?= htmlspecialchars($cliente['nombre'] ?? 'Sin nombre') ?></div>
                            <div style="color:#667781;font-size:12px;"><?= htmlspecialchars($cliente['telefono'] ?? '') ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Panel principal - Chat -->
        <div style="flex:1;display:flex;flex-direction:column;background:#E5DDD5;">
            
            <!-- Header del chat -->
            <div style="background:#F0F0F0;padding:10px 20px;display:flex;align-items:center;gap:12px;border-bottom:1px solid #D1D7DB;">
                <div style="width:40px;height:40px;background:#128C7E;border-radius:50%;display:flex;align-items:center;justify-content:center;color:white;font-weight:600;">
                    R
                </div>
                <div style="flex:1;">
                    <div style="font-weight:500;color:#111B21;font-size:16px;">RepairlyRD</div>
                    <div style="color:#667781;font-size:12px;">En línea</div>
                </div>
            </div>
            
            <!-- Área de mensajes -->
            <div style="flex:1;padding:20px;overflow-y:auto;background-image:url('https://user-images.githubusercontent.com/15075759/28719144-86dc0f70-73b1-11e7-911d-60d70fcded21.png');background-size:contain;">
                
                <?php if ($success): ?>
                    <div style="text-align:center;margin-bottom:10px;">
                        <span style="background:#DCF8C6;color:#075E54;padding:8px 16px;border-radius:8px;font-size:12px;">
                            <?= htmlspecialchars($success) ?>
                        </span>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div style="text-align:center;margin-bottom:10px;">
                        <span style="background:#FEE2E2;color:#991B1B;padding:8px 16px;border-radius:8px;font-size:12px;">
                            <?= htmlspecialchars($error) ?>
                        </span>
                    </div>
                <?php endif; ?>
                
                <!-- Mensajes del historial -->
                <?php foreach ($mensajes as $msg): ?>
                    <div style="margin-bottom:10px;display:flex;justify-content:flex-end;">
                        <div style="background:#DCF8C6;padding:10px 15px;border-radius:8px;max-width:70%;box-shadow:0 1px 2px rgba(0,0,0,0.1);">
                            <div style="color:#111B21;font-size:14px;"><?= htmlspecialchars($msg['Mensaje']) ?></div>
                            <div style="text-align:right;margin-top:4px;">
                                <span style="color:#667781;font-size:10px;"><?= date('H:i', strtotime($msg['FechaEnvio'])) ?></span>
                                <span style="color:#53BDEB;font-size:10px;margin-left:4px;">✓✓</span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <?php if (empty($mensajes)): ?>
                    <div style="text-align:center;color:#667781;padding:40px;">
                        <i class="ti ti-message-circle" style="font-size:48px;margin-bottom:10px;"></i>
                        <div style="font-size:14px;">No hay mensajes</div>
                        <div style="font-size:12px;">Envía tu primer mensaje de WhatsApp</div>
                    </div>
                <?php endif; ?>
                
            </div>
            
            <!-- Área de input -->
            <div style="background:#F0F0F0;padding:10px 20px;display:flex;align-items:center;gap:10px;">
                <form method="POST" style="flex:1;display:flex;gap:10px;">
                    <input type="hidden" name="action" value="send_message">
                    <input type="text" name="telefono" id="telefono_input" placeholder="Número de teléfono" style="flex:1;padding:10px;border:none;border-radius:20px;background:#E5DDD5;font-size:14px;" value="whatsapp:+18295921607">
                    <input type="text" name="mensaje" id="mensaje_input" placeholder="Escribe un mensaje..." style="flex:2;padding:10px;border:none;border-radius:20px;background:#E5DDD5;font-size:14px;">
                    <button type="submit" style="background:#25D366;color:white;border:none;padding:10px 20px;border-radius:20px;cursor:pointer;">
                        <i class="ti ti-send"></i>
                    </button>
                </form>
            </div>
            
        </div>
    </div>
</div>

<script>
function seleccionarCliente(telefono, nombre) {
    document.getElementById('telefono_input').value = 'whatsapp:+' + telefono.replace(/\D/g, '');
}
</script>

<style>
/* Estilos adicionales para WhatsApp */
::-webkit-scrollbar {
    width: 6px;
}
::-webkit-scrollbar-track {
    background: #f1f1f1;
}
::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 3px;
}
::-webkit-scrollbar-thumb:hover {
    background: #555;
}
</style>
