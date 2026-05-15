<?php

declare(strict_types=1);

/** @var mysqli $conn */
require_once __DIR__ . '/../../includes/ui_helper.php';

$action = $_GET['action'] ?? '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$search_q = isset($_GET['q']) && is_string($_GET['q']) ? trim($_GET['q']) : '';

if (empty($equipo_table)) {
    echo '<div class="charts-card"><div class="card-title">Equipos</div><p style="padding:12px;color:#B83232;">No se encontró la tabla Equipo.</p></div>';
    return;
}

$idField = 'id_equipo';
foreach (array_keys($equipo_cols) as $k) {
    if (strcasecmp((string)$k, 'id_equipo') === 0) {
        $idField = $k;
        break;
    }
}

$cliente_pick = pick_table($conn, ['cliente', 'Cliente']);
$clientes_opts = [];
if ($cliente_pick !== '') {
    $cc = table_columns($conn, $cliente_pick);
    $cid = 'id_cliente';
    foreach (array_keys($cc) as $k) {
        if (strcasecmp((string)$k, 'id_cliente') === 0) {
            $cid = $k;
            break;
        }
    }
    $clientes_opts = db_rows($conn, "SELECT `{$cid}` AS id, `nombre` FROM `{$cliente_pick}` ORDER BY `nombre` ASC LIMIT 500");
}

$rows = [];
$sql = "SELECT * FROM `{$equipo_table}`";
$types = '';
$params = [];
if ($search_q !== '') {
    $like = '%' . $search_q . '%';
    $ors = [];
    $ors[] = "CAST(`{$idField}` AS CHAR) = ?";
    $types .= 's';
    $params[] = $search_q;

    foreach (['tipo', 'marca', 'modelo', 'numero_identificacion'] as $c) {
        if (isset($equipo_cols[$c])) {
            $ors[] = "`{$c}` LIKE ?";
            $types .= 's';
            $params[] = $like;
        }
    }
    if (isset($equipo_cols['id_cliente']) && ctype_digit($search_q)) {
        $ors[] = "`id_cliente` = ?";
        $types .= 'i';
        $params[] = (int)$search_q;
    }
    if ($ors !== []) {
        $sql .= ' WHERE ' . implode(' OR ', $ors);
    }
}
$sql .= " ORDER BY `{$idField}` DESC LIMIT 150";
if ($types !== '') {
    $st = $conn->prepare($sql);
    if ($st) {
        $st->bind_param($types, ...$params);
        $st->execute();
        $res = $st->get_result();
        $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
        $st->close();
    }
} else {
    $rows = db_rows($conn, $sql);
}

$edit = null;
if ($action === 'edit' && $id > 0) {
    $st = $conn->prepare("SELECT * FROM `{$equipo_table}` WHERE `{$idField}`=? LIMIT 1");
    if ($st) {
        $st->bind_param('i', $id);
        $st->execute();
        $rs = $st->get_result();
        $edit = $rs ? $rs->fetch_assoc() : null;
        $st->close();
    }
}
?>
<div class="charts-row">
<?php render_stats_cards([[
'label'=>'Panel activo','value'=>count($rows ?? []),'sub'=>'Registros cargados','icon'=>'ti-chart-bar','bg'=>'linear-gradient(135deg,#111827,#1f2937)','color'=>'#93C5FD'],
['label'=>'Estado','value'=>'Online','sub'=>'Sistema sincronizado','icon'=>'ti-activity-heartbeat','bg'=>'linear-gradient(135deg,#14532d,#166534)','color'=>'#86EFAC'],
['label'=>'RepairlyRD','value'=>'IA','sub'=>'Automatización habilitada','icon'=>'ti-cpu','bg'=>'linear-gradient(135deg,#4c1d95,#5b21b6)','color'=>'#C4B5FD'],
]); ?>

    <div class="charts-card">
        <div class="card-header">
            <div>
                <div class="card-title">Equipos</div>
                <div class="card-sub">Registro de equipos y vínculo con clientes</div>
            </div>
            <a class="ordenes-ver-btn" href="?page=equipos&action=new">Nuevo equipo</a>
        </div>
        <div style="display:flex;gap:8px;align-items:center;margin-top:10px;flex-wrap:wrap;">
            <form method="get" style="display:flex;gap:8px;align-items:center;flex:1;min-width:240px;">
                <input type="hidden" name="page" value="equipos">
                <div class="topbar-search" style="flex:1;min-width:220px;">
                    <i class="ti ti-search" aria-hidden="true"></i>
                    <input name="q" value="<?= h($search_q) ?>" placeholder="Buscar por tipo, marca, modelo o ID" style="border:0;background:transparent;outline:none;font:inherit;color:#4D4841;width:100%;">
                </div>
                <button class="ordenes-ver-btn" type="submit">Buscar</button>
                <?php if ($search_q !== ''): ?>
                    <a class="ordenes-ver-btn" href="?page=equipos">Limpiar</a>
                <?php endif; ?>
            </form>
        </div>
        
<?php require_once __DIR__ . '/../../includes/ai_helper.php'; ?>

<div id="ia-panel" style="display:none;margin-top:14px;padding:18px;border-radius:16px;background:linear-gradient(135deg,#10233d,#1F5C8B);color:white;box-shadow:0 10px 25px rgba(0,0,0,.15);">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
        <div>
            <div style="font-size:18px;font-weight:700;">Asistente IA RepairlyRD</div>
            <div style="font-size:12px;opacity:.8;">Análisis inteligente de fallas electrónicas</div>
        </div>
        <div id="ia-status" style="padding:6px 10px;background:rgba(255,255,255,.15);border-radius:999px;font-size:11px;">
            Analizando...
        </div>
    </div>

    <textarea id="ia-input" placeholder="Describe la falla del equipo..." style="width:100%;min-height:90px;border:none;border-radius:12px;padding:12px;resize:vertical;"></textarea>

    <div style="display:flex;gap:10px;margin-top:12px;flex-wrap:wrap;">
        <button type="button" onclick="analizarIA()" class="ordenes-ver-btn" style="background:#22c55e;color:white;border:none;">
            Analizar con IA
        </button>

        <button type="button" onclick="copiarResultadoIA()" class="ordenes-ver-btn">
            Copiar resultado
        </button>
    </div>

    <div id="ia-result" style="margin-top:16px;display:none;background:rgba(255,255,255,.08);padding:14px;border-radius:14px;">
        <div id="ia-html"></div>
    </div>
</div>

<script>
async function analizarIA() {
    const texto = document.getElementById('ia-input').value.trim();

    if(!texto){
        alert('Escribe una descripción del problema.');
        return;
    }

    document.getElementById('ia-panel').style.display = 'block';
    document.getElementById('ia-result').style.display = 'block';
    document.getElementById('ia-status').innerText = 'Procesando...';

    const fallas = [
        {key:'pantalla',tipo:'Pantalla / Display',sol:['Revisar flex','Cambiar display','Probar touch']},
        {key:'bateria',tipo:'Batería',sol:['Cambiar batería','Revisar pin de carga','Verificar consumo']},
        {key:'no enciende',tipo:'Encendido',sol:['Medir voltajes','Revisar motherboard','Probar fuente']},
        {key:'calienta',tipo:'Sobrecalentamiento',sol:['Limpieza interna','Cambiar pasta térmica','Revisar cortos']},
        {key:'mojado',tipo:'Daño por líquido',sol:['Limpieza ultrasónica','Eliminar sulfato','Revisar pistas']}
    ];

    let encontrado = {
        tipo:'Falla general',
        sol:['Realizar diagnóstico técnico','Probar componentes','Verificar alimentación']
    };

    fallas.forEach(f => {
        if(texto.toLowerCase().includes(f.key)){
            encontrado = f;
        }
    });

    setTimeout(() => {
        document.getElementById('ia-status').innerText = 'Diagnóstico listo';

        document.getElementById('ia-html').innerHTML = `
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;">
                <div style="background:rgba(255,255,255,.06);padding:12px;border-radius:12px;">
                    <div style="font-size:11px;opacity:.7;">TIPO DE FALLA</div>
                    <div style="font-size:18px;font-weight:700;">${encontrado.tipo}</div>
                </div>

                <div style="background:rgba(255,255,255,.06);padding:12px;border-radius:12px;">
                    <div style="font-size:11px;opacity:.7;">NIVEL</div>
                    <div style="font-size:18px;font-weight:700;">${texto.length > 100 ? 'ALTO' : 'MEDIO'}</div>
                </div>
            </div>

            <div style="margin-top:16px;">
                <div style="font-weight:700;margin-bottom:10px;">Posibles soluciones</div>
                ${encontrado.sol.map(s => `
                    <div style="padding:10px;margin-bottom:8px;background:rgba(255,255,255,.05);border-radius:10px;">
                        ✔ ${s}
                    </div>
                `).join('')}
            </div>
        `;
    }, 1000);
}

function copiarResultadoIA(){
    const texto = document.getElementById('ia-html').innerText;
    navigator.clipboard.writeText(texto);
}
</script>

<?php if ($action === 'new' || $action === 'edit'): ?>
            <form method="post" style="margin-top:14px;display:grid;grid-template-columns:1fr 1fr;gap:10px;max-width:720px;">
                <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>">
                <input type="hidden" name="equipos_action" value="<?= $action === 'edit' ? 'update' : 'create' ?>">
                <?php if ($action === 'edit'): ?>
                    <input type="hidden" name="id_equipo" value="<?= (int)($edit[$idField] ?? 0) ?>">
                <?php endif; ?>
                <?php if (isset($equipo_cols['tipo'])): ?>
                    <div><label style="font-size:10px;color:#6B6560;">Tipo</label><input name="tipo" style="width:100%;padding:10px;border-radius:8px;border:0.5px solid #D0CCC6;background:#fff;" value="<?= h((string)($edit['tipo'] ?? '')) ?>"></div>
                <?php endif; ?>
                <?php if (isset($equipo_cols['marca'])): ?>
                    <div><label style="font-size:10px;color:#6B6560;">Marca</label><input name="marca" style="width:100%;padding:10px;border-radius:8px;border:0.5px solid #D0CCC6;" value="<?= h((string)($edit['marca'] ?? '')) ?>"></div>
                <?php endif; ?>
                <?php if (isset($equipo_cols['modelo'])): ?>
                    <div><label style="font-size:10px;color:#6B6560;">Modelo</label><input name="modelo" style="width:100%;padding:10px;border-radius:8px;border:0.5px solid #D0CCC6;" value="<?= h((string)($edit['modelo'] ?? '')) ?>"></div>
                <?php endif; ?>
                <?php if (isset($equipo_cols['numero_identificacion'])): ?>
                    <div><label style="font-size:10px;color:#6B6560;">Nº identificación</label><input name="numero_identificacion" style="width:100%;padding:10px;border-radius:8px;border:0.5px solid #D0CCC6;" value="<?= h((string)($edit['numero_identificacion'] ?? '')) ?>"></div>
                <?php endif; ?>
                <?php if (isset($equipo_cols['tipo_identificacion'])): ?>
                    <div><label style="font-size:10px;color:#6B6560;">Tipo ID</label><input name="tipo_identificacion" style="width:100%;padding:10px;border-radius:8px;border:0.5px solid #D0CCC6;" value="<?= h((string)($edit['tipo_identificacion'] ?? '')) ?>"></div>
                <?php endif; ?>
                <?php if (isset($equipo_cols['bloqueo_tipo'])): ?>
                    <div><label style="font-size:10px;color:#6B6560;">Bloqueo</label><input name="bloqueo_tipo" style="width:100%;padding:10px;border-radius:8px;border:0.5px solid #D0CCC6;" value="<?= h((string)($edit['bloqueo_tipo'] ?? '')) ?>"></div>
                <?php endif; ?>
                <?php if (isset($equipo_cols['requiere_desbloqueo'])): ?>
                    <div style="display:flex;align-items:center;gap:8px;margin-top:18px;">
                        <input type="checkbox" name="requiere_desbloqueo" value="1" <?= !empty($edit['requiere_desbloqueo']) ? 'checked' : '' ?>>
                        <span style="font-size:12px;">Requiere desbloqueo</span>
                    </div>
                <?php endif; ?>
                <?php if (isset($equipo_cols['observaciones_ingreso'])): ?>
                    <div style="grid-column:1/-1;"><label style="font-size:10px;color:#6B6560;">Observaciones ingreso</label><textarea name="observaciones_ingreso" rows="3" style="width:100%;padding:10px;border-radius:8px;border:0.5px solid #D0CCC6;"><?= h((string)($edit['observaciones_ingreso'] ?? '')) ?></textarea></div>
                <?php endif; ?>
                <?php if (isset($equipo_cols['id_cliente']) && $cliente_pick !== ''): ?>
                    <div style="grid-column:1/-1;">
                        <label style="font-size:10px;color:#6B6560;">Cliente</label>
                        <select name="id_cliente" required style="width:100%;padding:10px;border-radius:8px;border:0.5px solid #D0CCC6;">
                            <option value="0">— Seleccionar —</option>
                            <?php foreach ($clientes_opts as $c): ?>
                                <option value="<?= (int)$c['id'] ?>" <?= (int)($edit['id_cliente'] ?? 0) === (int)$c['id'] ? 'selected' : '' ?>><?= h((string)$c['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>
                <div style="grid-column:1/-1;display:flex;gap:8px;justify-content:flex-end;">
                    <a class="ordenes-ver-btn" href="?page=equipos">Cancelar</a>
                    <button type="submit" class="ordenes-ver-btn" style="background:#1F5C8B;color:#fff;border-color:#1F5C8B;">Guardar</button>
                </div>
            </form>
        <?php endif; ?>

        <div style="margin-top:14px;border-top:0.5px solid #EDECEA;padding-top:12px;">
            <div class="table-head" style="grid-template-columns:56px 1fr 1fr 100px 140px;">
                <div>ID</div><div>Tipo / Marca</div><div>Modelo</div><div>Cliente</div><div style="text-align:right;">Acciones</div>
            </div>
            <?php foreach ($rows as $r): ?>
                <?php
                $idc = (int)($r['id_cliente'] ?? 0);
                $nomCli = '';
                foreach ($clientes_opts as $c) {
                    if ((int)$c['id'] === $idc) {
                        $nomCli = (string)$c['nombre'];
                        break;
                    }
                }
                ?>
                <div class="table-row" style="grid-template-columns:56px 1fr 1fr 100px 140px;">
                    <div class="order-id"><?= (int)($r[$idField] ?? 0) ?></div>
                    <div class="order-cliente"><?= h(trim(((string)($r['tipo'] ?? '')) . ' ' . ((string)($r['marca'] ?? '')))) ?></div>
                    <div class="order-tecnico"><?= h((string)($r['modelo'] ?? '')) ?></div>
                    <div class="order-tecnico" style="font-size:11px;"><?= $nomCli !== '' ? h($nomCli) : '—' ?></div>
                    <div style="display:flex;gap:6px;justify-content:flex-end;">
                        <a class="ordenes-ver-btn" href="?page=equipos&action=edit&id=<?= (int)($r[$idField] ?? 0) ?>">Editar</a>
                        <form method="post" style="display:inline;" onsubmit="return confirm('¿Eliminar equipo?');">
                            <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>">
                            <input type="hidden" name="equipos_action" value="delete">
                            <input type="hidden" name="id_equipo" value="<?= (int)($r[$idField] ?? 0) ?>">
                            <button type="submit" class="ordenes-ver-btn" style="background:#FDF0F0;color:#B83232;border-color:#F5C2C2;">Eliminar</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php if (empty($rows)): ?>
                <div style="padding:14px;color:#6B6560;font-size:12px;">Sin equipos registrados.</div>
            <?php endif; ?>
        </div>
    </div>
</div>
