<?php

/** @var mysqli $conn */
require_once __DIR__ . '/../../includes/ui_helper.php';

// Cargar historial de mensajes enviados por Twilio
$mensajes = [];
$query = "SELECT * FROM WhatsAppMessages ORDER BY FechaEnvio DESC LIMIT 50";
$result = $conn->query($query);
if ($result) {
    while($row = $result->fetch_assoc()) {
        $mensajes[] = $row;
    }
}

// Invertir para mostrar en orden cronológico (más antiguo arriba)
$mensajes = array_reverse($mensajes);
?>

<!-- Interfaz estilo WhatsApp - Solo lectura -->
<div style="max-width:800px;margin:0 auto;background:#E5DDD5;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,0.1);">
    
    <!-- Header estilo WhatsApp -->
    <div style="background:#075E54;padding:15px 20px;display:flex;align-items:center;gap:15px;">
        <div style="width:40px;height:40px;background:#25D366;border-radius:50%;display:flex;align-items:center;justify-content:center;">
            <i class="ti ti-brand-whatsapp" style="color:white;font-size:20px;"></i>
        </div>
        <div style="flex:1;">
            <div style="color:white;font-weight:600;font-size:16px;">RepairlyRD</div>
            <div style="color:#128C7E;font-size:12px;">Notificaciones automáticas de órdenes</div>
        </div>
        <div style="color:white;font-size:24px;">
            <i class="ti ti-dots-vertical"></i>
        </div>
    </div>
    
    <!-- Contenedor principal - Solo chat -->
    <div style="height:600px;display:flex;flex-direction:column;background:#E5DDD5;">
        
        <!-- Header del chat -->
        <div style="background:#F0F0F0;padding:10px 20px;display:flex;align-items:center;gap:12px;border-bottom:1px solid #D1D7DB;">
            <div style="width:40px;height:40px;background:#128C7E;border-radius:50%;display:flex;align-items:center;justify-content:center;color:white;font-weight:600;">
                R
            </div>
            <div style="flex:1;">
                <div style="font-weight:500;color:#111B21;font-size:16px;">RepairlyRD</div>
                <div style="color:#667781;font-size:12px;">Sistema de notificaciones</div>
            </div>
        </div>
        
        <!-- Área de mensajes -->
        <div style="flex:1;padding:20px;overflow-y:auto;background-image:url('https://user-images.githubusercontent.com/15075759/28719144-86dc0f70-73b1-11e7-911d-60d70fcded21.png');background-size:contain;">
            
            <!-- Mensaje informativo -->
            <div style="text-align:center;margin-bottom:20px;">
                <span style="background:#E5DDD5;color:#667781;padding:8px 16px;border-radius:8px;font-size:12px;">
                    Este chat muestra las notificaciones automáticas enviadas por Twilio cuando se crean nuevas órdenes de reparación
                </span>
            </div>
            
            <!-- Mensajes del historial -->
            <?php foreach ($mensajes as $msg): ?>
                <div style="margin-bottom:10px;display:flex;justify-content:flex-start;">
                    <div style="background:#FFFFFF;padding:10px 15px;border-radius:8px;max-width:70%;box-shadow:0 1px 2px rgba(0,0,0,0.1);">
                        <div style="color:#111B21;font-size:14px;white-space:pre-wrap;"><?= htmlspecialchars($msg['Mensaje']) ?></div>
                        <div style="text-align:right;margin-top:4px;">
                            <span style="color:#667781;font-size:10px;"><?= date('H:i', strtotime($msg['FechaEnvio'])) ?></span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <?php if (empty($mensajes)): ?>
                <div style="text-align:center;color:#667781;padding:40px;">
                    <i class="ti ti-message-circle" style="font-size:48px;margin-bottom:10px;"></i>
                    <div style="font-size:14px;">No hay mensajes</div>
                    <div style="font-size:12px;">Los mensajes aparecerán aquí cuando se creen nuevas órdenes</div>
                </div>
            <?php endif; ?>
            
        </div>
        
        <!-- Footer informativo (sin input) -->
        <div style="background:#F0F0F0;padding:15px 20px;text-align:center;border-top:1px solid #D1D7DB;">
            <div style="color:#667781;font-size:12px;">
                <i class="ti ti-lock"></i> Chat de solo lectura - Las notificaciones se envían automáticamente
            </div>
        </div>
        
    </div>
</div>

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
