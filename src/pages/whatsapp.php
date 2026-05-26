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
                <div style="margin-bottom:10px;display:flex;justify-content:flex-end;">
                    <div style="background:#DCF8C6;padding:10px 15px;border-radius:8px;max-width:70%;box-shadow:0 1px 2px rgba(0,0,0,0.1);">
                        <div style="color:#111B21;font-size:14px;white-space:pre-wrap;"><?= htmlspecialchars($msg['Mensaje']) ?></div>
                        <div style="text-align:right;margin-top:4px;">
                            <span style="color:#667781;font-size:10px;"><?= date('H:i', strtotime($msg['FechaEnvio'])) ?></span>
                            <span style="color:#53BDEB;font-size:10px;margin-left:4px;">✓✓</span>
                        </div>
                        <div style="margin-top:8px;text-align:right;">
                            <button onclick="convertirACliente(<?= htmlspecialchars($msg['IdMensaje']) ?>, '<?= htmlspecialchars($msg['Telefono']) ?>')" style="background:#075E54;color:white;border:none;padding:6px 12px;border-radius:4px;font-size:11px;cursor:pointer;">
                                👤 Convertir a Cliente
                            </button>
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

<script>
function convertirACliente(idMensaje, telefono) {
    // Extraer información del mensaje
    const mensajeElement = event.target.closest('div[style*="background:#DCF8C6"]');
    const mensajeTexto = mensajeElement.querySelector('div[style*="white-space:pre-wrap"]').textContent;
    
    // Extraer nombre del mensaje
    const nombreMatch = mensajeTexto.match(/Cliente:\s*(.+)/);
    const nombre = nombreMatch ? nombreMatch[1].trim() : '';
    
    // Extraer código del mensaje
    const codigoMatch = mensajeTexto.match(/Código:\s*(.+)/);
    const codigo = codigoMatch ? codigoMatch[1].trim() : '';
    
    // Mostrar modal para completar datos del cliente
    const modal = document.createElement('div');
    modal.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);display:flex;align-items:center;justify-content:center;z-index:1000;';
    
    modal.innerHTML = `
        <div style="background:white;padding:30px;border-radius:12px;max-width:500px;width:90%;box-shadow:0 8px 32px rgba(0,0,0,0.2);">
            <h3 style="margin-bottom:20px;color:#075E54;">Convertir a Cliente</h3>
            <div style="margin-bottom:15px;">
                <label style="display:block;margin-bottom:5px;font-weight:600;">Nombre</label>
                <input type="text" id="cliente-nombre" value="${nombre}" style="width:100%;padding:10px;border:1px solid #ddd;border-radius:6px;">
            </div>
            <div style="margin-bottom:15px;">
                <label style="display:block;margin-bottom:5px;font-weight:600;">Teléfono</label>
                <input type="text" id="cliente-telefono" value="${telefono}" style="width:100%;padding:10px;border:1px solid #ddd;border-radius:6px;">
            </div>
            <div style="margin-bottom:15px;">
                <label style="display:block;margin-bottom:5px;font-weight:600;">Email</label>
                <input type="email" id="cliente-email" placeholder="cliente@email.com" style="width:100%;padding:10px;border:1px solid #ddd;border-radius:6px;">
            </div>
            <div style="margin-bottom:15px;">
                <label style="display:block;margin-bottom:5px;font-weight:600;">Dirección</label>
                <input type="text" id="cliente-direccion" placeholder="Dirección completa" style="width:100%;padding:10px;border:1px solid #ddd;border-radius:6px;">
            </div>
            <div style="margin-bottom:15px;">
                <label style="display:block;margin-bottom:5px;font-weight:600;">Contraseña temporal</label>
                <input type="text" id="cliente-password" value="cliente123" style="width:100%;padding:10px;border:1px solid #ddd;border-radius:6px;">
            </div>
            <div style="margin-bottom:15px;">
                <label style="display:block;margin-bottom:5px;font-weight:600;">Equipo (opcional)</label>
                <input type="text" id="cliente-equipo" placeholder="Marca y modelo del equipo" style="width:100%;padding:10px;border:1px solid #ddd;border-radius:6px;">
            </div>
            <div style="display:flex;gap:10px;margin-top:20px;">
                <button onclick="guardarCliente()" style="flex:1;background:#075E54;color:white;padding:12px;border:none;border-radius:6px;cursor:pointer;font-weight:600;">Guardar Cliente</button>
                <button onclick="this.closest('div[style*=\"position:fixed\"]').remove()" style="flex:1;background:#ddd;color:#333;padding:12px;border:none;border-radius:6px;cursor:pointer;font-weight:600;">Cancelar</button>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    
    window.guardarCliente = async function() {
        const nombre = document.getElementById('cliente-nombre').value.trim();
        const telefono = document.getElementById('cliente-telefono').value.trim();
        const email = document.getElementById('cliente-email').value.trim();
        const direccion = document.getElementById('cliente-direccion').value.trim();
        const password = document.getElementById('cliente-password').value.trim();
        const equipo = document.getElementById('cliente-equipo').value.trim();
        
        if (!nombre || !telefono || !password) {
            alert('Nombre, teléfono y contraseña son requeridos');
            return;
        }
        
        const formData = new FormData();
        formData.append('nombre', nombre);
        formData.append('telefono', telefono);
        formData.append('email', email);
        formData.append('direccion', direccion);
        formData.append('password', password);
        formData.append('equipo', equipo);
        formData.append('codigo_orden', codigo);
        
        try {
            const response = await fetch('convertir_cliente.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                alert('Cliente creado exitosamente' + (result.equipo_creado ? ' y equipo registrado' : ''));
                modal.remove();
                location.reload();
            } else {
                alert('Error: ' + result.error);
            }
        } catch (error) {
            alert('Error de conexión: ' + error.message);
        }
    };
}
</script>
