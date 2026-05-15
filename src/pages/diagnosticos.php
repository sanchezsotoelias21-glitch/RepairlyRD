<?php

declare(strict_types=1);

/** @var mysqli $conn */

$action = $_GET['action'] ?? '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$search_q = isset($_GET['q']) && is_string($_GET['q']) ? trim($_GET['q']) : '';

if (empty($diag_table)) {
    echo '<div class="charts-card">
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
Tabla Diagnostico no encontrada.</div>';
    return;
}

$idField = 'id_diagnostico';
foreach (array_keys($diag_cols) as $k) {
    if (strcasecmp((string)$k, 'id_diagnostico') === 0) {
        $idField = $k;
        break;
    }
}

$orden_tbl = pick_table($conn, ['orden_reparacion', 'Orden_Reparacion', 'orden']);
$orden_cols_for_join = $orden_tbl !== '' ? table_columns($conn, $orden_tbl) : [];
$eq_tbl_diag = pick_table($conn, ['equipo', 'Equipo']);
$orden_id_col = 'id_orden';
foreach (array_keys($orden_cols_for_join) as $ok) {
    if (strcasecmp((string)$ok, 'id_orden') === 0) {
        $orden_id_col = $ok;
        break;
    }
}

$ordenes_opts = [];
if ($orden_tbl !== '') {
    $sel = ["o.`{$orden_id_col}` AS id_orden"];
    if (isset($orden_cols_for_join['codigo_seguimiento'])) {
        $sel[] = "COALESCE(o.`codigo_seguimiento`, CAST(o.`{$orden_id_col}` AS CHAR)) AS lbl";
    } else {
        $sel[] = "CAST(o.`{$orden_id_col}` AS CHAR) AS lbl";
    }
    $tipo_expr = "''";
    if ($eq_tbl_diag !== '' && isset($orden_cols_for_join['id_equipo'])) {
        $tipo_expr = "TRIM(CONCAT(COALESCE(e.`tipo`,''),' ',COALESCE(e.`marca`,''),' ',COALESCE(e.`modelo`,'')))";
    }
    $sel[] = "{$tipo_expr} AS orden_tipo";
    $precio_parts = [];
    if (isset($orden_cols_for_join['costo_total'])) {
        $precio_parts[] = 'o.`costo_total`';
    }
    if (isset($orden_cols_for_join['mano_obra'])) {
        $precio_parts[] = 'o.`mano_obra`';
    }
    $sel[] = $precio_parts !== []
        ? ('COALESCE(' . implode(',', $precio_parts) . ',0) AS orden_precio')
        : '0 AS orden_precio';
    if (isset($orden_cols_for_join['fecha_ingreso'])) {
        $sel[] = 'COALESCE(DATE(o.`fecha_ingreso`), CURDATE()) AS orden_fecha';
    } elseif (isset($orden_cols_for_join['fecha_creacion'])) {
        $sel[] = 'COALESCE(DATE(o.`fecha_creacion`), CURDATE()) AS orden_fecha';
    } else {
        $sel[] = 'CURDATE() AS orden_fecha';
    }
    $cod_ref = isset($orden_cols_for_join['codigo_seguimiento'])
        ? "CONCAT('Orden ', COALESCE(o.`codigo_seguimiento`, CAST(o.`{$orden_id_col}` AS CHAR)))"
        : "CONCAT('Orden #', o.`{$orden_id_col}`)";
    if ($eq_tbl_diag !== '' && isset($orden_cols_for_join['id_equipo'])) {
        $sel[] = "TRIM(CONCAT_WS(' · ', {$cod_ref}, NULLIF({$tipo_expr}, ''))) AS orden_descripcion";
    } else {
        $sel[] = "{$cod_ref} AS orden_descripcion";
    }
    $from = "FROM `{$orden_tbl}` o";
    $join = ($eq_tbl_diag !== '' && isset($orden_cols_for_join['id_equipo']))
        ? " LEFT JOIN `{$eq_tbl_diag}` e ON e.`id_equipo` = o.`id_equipo`"
        : '';
    $sql_ord = 'SELECT ' . implode(', ', $sel) . " {$from}{$join} ORDER BY o.`{$orden_id_col}` DESC LIMIT 300";
    $ordenes_opts = db_rows($conn, $sql_ord);
}

$rows = [];
$sql = "SELECT * FROM `{$diag_table}`";
$types = '';
$params = [];
if ($search_q !== '') {
    $like = '%' . $search_q . '%';
    $ors = [];
    $ors[] = "CAST(`{$idField}` AS CHAR) = ?";
    $types .= 's';
    $params[] = $search_q;
    if ($diag_col_orden !== null) {
        if (ctype_digit($search_q)) {
            $ors[] = "`{$diag_col_orden}` = ?";
            $types .= 'i';
            $params[] = (int)$search_q;
        } else {
            $ors[] = "CAST(`{$diag_col_orden}` AS CHAR) LIKE ?";
            $types .= 's';
            $params[] = $like;
        }
    }
    if ($diag_col_tipo !== null) {
        $ors[] = "`{$diag_col_tipo}` LIKE ?";
        $types .= 's';
        $params[] = $like;
    }
    if ($diag_col_desc !== null) {
        $ors[] = "`{$diag_col_desc}` LIKE ?";
        $types .= 's';
        $params[] = $like;
    }
    if ($ors !== []) {
        $sql .= ' WHERE ' . implode(' OR ', $ors);
    }
}
$sql .= " ORDER BY `{$idField}` DESC LIMIT 200";
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
    $st = $conn->prepare("SELECT * FROM `{$diag_table}` WHERE `{$idField}`=? LIMIT 1");
    if ($st) {
        $st->bind_param('i', $id);
        $st->execute();
        $rs = $st->get_result();
        $edit = $rs ? $rs->fetch_assoc() : null;
        $st->close();
    }
}

$edit_id_orden = 0;
if ($edit && $diag_col_orden !== null) {
    $edit_id_orden = (int)($edit[$diag_col_orden] ?? 0);
}
$ev_tipo = $diag_col_tipo !== null && $edit ? (string)($edit[$diag_col_tipo] ?? '') : '';
$ev_cost = $diag_col_cost !== null && $edit ? (string)($edit[$diag_col_cost] ?? '0') : '0';
$ev_fecha = $diag_col_fecha !== null && $edit ? substr((string)($edit[$diag_col_fecha] ?? date('Y-m-d')), 0, 10) : substr(date('Y-m-d'), 0, 10);
$ev_desc = $diag_col_desc !== null && $edit ? (string)($edit[$diag_col_desc] ?? '') : '';
?>
<div class="charts-row">
    <div class="charts-card">
        <div class="card-header">
            <div>
                <div class="card-title">Diagnósticos</div>
                <div class="card-sub">Por orden de reparación</div>
            </div>
            <a class="ordenes-ver-btn" href="?page=diagnosticos&action=new">Nuevo diagnóstico</a>
        </div>
        <div style="display:flex;gap:8px;align-items:center;margin-top:10px;flex-wrap:wrap;">
            <form method="get" style="display:flex;gap:8px;align-items:center;flex:1;min-width:240px;">
                <input type="hidden" name="page" value="diagnosticos">
                <div class="topbar-search" style="flex:1;min-width:220px;">
                    <i class="ti ti-search" aria-hidden="true"></i>
                    <input name="q" value="<?= h($search_q) ?>" placeholder="Buscar por orden, tipo o texto" style="border:0;background:transparent;outline:none;font:inherit;color:#4D4841;width:100%;">
                </div>
                <button class="ordenes-ver-btn" type="submit">Buscar</button>
                <?php if ($search_q !== ''): ?>
                    <a class="ordenes-ver-btn" href="?page=diagnosticos">Limpiar</a>
                <?php endif; ?>
            </form>
        </div>
        <?php if ($action === 'new' || $action === 'edit'): ?>
            <form method="post" style="margin-top:14px;display:grid;grid-template-columns:1fr 1fr;gap:10px;max-width:720px;">
                <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>">
                <input type="hidden" name="diag_action" value="<?= $action === 'edit' ? 'update' : 'create' ?>">
                <?php if ($action === 'edit'): ?>
                    <input type="hidden" name="id_diagnostico" value="<?= (int)($edit[$idField] ?? 0) ?>">
                <?php endif; ?>
                <?php if ($diag_col_orden !== null): ?>
                    <div style="grid-column:1/-1;">
                        <label style="font-size:10px;color:#6B6560;">Orden</label>
                        <select id="diag-id-orden" name="id_orden" required style="width:100%;padding:10px;border-radius:8px;border:0.5px solid #D0CCC6;">
                            <?php foreach ($ordenes_opts as $o): ?>
                                <?php
                                $ot = (string)($o['orden_tipo'] ?? '');
                                $op = isset($o['orden_precio']) ? (float)$o['orden_precio'] : 0.0;
                                $of = (string)($o['orden_fecha'] ?? '');
                                if ($of !== '' && strlen($of) > 10) {
                                    $of = substr($of, 0, 10);
                                }
                                $od = (string)($o['orden_descripcion'] ?? '');
                                ?>
                                <option
                                    value="<?= (int)$o['id_orden'] ?>"
                                    data-orden-tipo="<?= h($ot) ?>"
                                    data-orden-precio="<?= h((string)$op) ?>"
                                    data-orden-fecha="<?= h($of) ?>"
                                    data-orden-desc="<?= h($od) ?>"
                                    <?= $edit_id_orden === (int)$o['id_orden'] ? 'selected' : '' ?>
                                ><?= h((string)($o['lbl'] ?? $o['id_orden'])) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p style="font-size:10px;color:#6B6560;margin-top:6px;">Al elegir una orden se rellenan tipo, costo estimado, fecha y descripción con los datos de la orden y el equipo vinculado (puedes editarlos antes de guardar).</p>
                    </div>
                <?php endif; ?>
                <?php if ($diag_col_tipo !== null): ?>
                    <div><label style="font-size:10px;color:#6B6560;">Tipo</label>
                        <input name="tipo" style="width:100%;padding:10px;border-radius:8px;border:0.5px solid #D0CCC6;" value="<?= h($action === 'edit' ? $ev_tipo : '') ?>"></div>
                <?php endif; ?>
                <?php if ($diag_col_cost !== null): ?>
                    <div><label style="font-size:10px;color:#6B6560;">Costo estimado</label>
                        <input name="costo_estimado" type="number" step="0.01" style="width:100%;padding:10px;border-radius:8px;border:0.5px solid #D0CCC6;" value="<?= h($action === 'edit' ? $ev_cost : '0') ?>"></div>
                <?php endif; ?>
                <?php if ($diag_col_fecha !== null): ?>
                    <div><label style="font-size:10px;color:#6B6560;">Fecha</label>
                        <input name="fecha" type="date" style="width:100%;padding:10px;border-radius:8px;border:0.5px solid #D0CCC6;" value="<?= h($action === 'edit' ? $ev_fecha : substr(date('Y-m-d'), 0, 10)) ?>"></div>
                <?php endif; ?>
                <?php if ($diag_col_desc !== null): ?>
                    <div style="grid-column:1/-1;"><label style="font-size:10px;color:#6B6560;">Descripción</label>
                        <textarea name="descripcion" rows="3" style="width:100%;padding:10px;border-radius:8px;border:0.5px solid #D0CCC6;"><?= h($action === 'edit' ? $ev_desc : '') ?></textarea></div>
                <?php endif; ?>
                <div style="grid-column:1/-1;display:flex;gap:8px;justify-content:flex-end;">
                    <a class="ordenes-ver-btn" href="?page=diagnosticos">Cancelar</a>
                    <button type="submit" class="ordenes-ver-btn" style="background:#1F5C8B;color:#fff;border-color:#1F5C8B;">Guardar</button>
                </div>
            </form>
        <?php endif; ?>

        <div style="margin-top:14px;border-top:0.5px solid #EDECEA;padding-top:12px;">
            <div class="table-head" style="grid-template-columns:52px 80px 1fr 100px 120px;">
                <div>ID</div><div>Orden</div><div>Tipo / Descripción</div><div>Fecha</div><div style="text-align:right;">Acciones</div>
            </div>
            <?php foreach ($rows as $r): ?>
                <?php
                $rid_ord = $diag_col_orden !== null ? (int)($r[$diag_col_orden] ?? 0) : 0;
                $rtipo = $diag_col_tipo !== null ? (string)($r[$diag_col_tipo] ?? '') : '';
                $rdesc = $diag_col_desc !== null ? (string)($r[$diag_col_desc] ?? '') : '';
                $rfecha = $diag_col_fecha !== null ? (string)($r[$diag_col_fecha] ?? '') : '';
                ?>
                <div class="table-row" style="grid-template-columns:52px 80px 1fr 100px 120px;">
                    <div class="order-id"><?= (int)($r[$idField] ?? 0) ?></div>
                    <div><?= $rid_ord ?></div>
                    <div class="order-tecnico" style="font-size:11px;"><?= h($rtipo) ?> — <?= h(mb_substr($rdesc, 0, 80)) ?></div>
                    <div style="font-size:11px;"><?= h(substr($rfecha, 0, 10)) ?></div>
                    <div style="display:flex;gap:6px;justify-content:flex-end;">
                        <a class="ordenes-ver-btn" href="?page=diagnosticos&action=edit&id=<?= (int)($r[$idField] ?? 0) ?>">Editar</a>
                        <form method="post" style="display:inline;" onsubmit="return confirm('¿Eliminar?');">
                            <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>">
                            <input type="hidden" name="diag_action" value="delete">
                            <input type="hidden" name="id_diagnostico" value="<?= (int)($r[$idField] ?? 0) ?>">
                            <button type="submit" class="ordenes-ver-btn" style="background:#FDF0F0;color:#B83232;">Eliminar</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php if (($action === 'new' || $action === 'edit') && $diag_col_orden !== null): ?>
<script>
(function () {
    'use strict';
    var sel = document.getElementById('diag-id-orden');
    if (!sel) return;
    var ti = document.querySelector('input[name="tipo"]');
    var pr = document.querySelector('input[name="costo_estimado"]');
    var fe = document.querySelector('input[name="fecha"]');
    var de = document.querySelector('textarea[name="descripcion"]');
    function applyFromOrden() {
        var opt = sel.options[sel.selectedIndex];
        if (!opt) return;
        var t = opt.getAttribute('data-orden-tipo') || '';
        var p = opt.getAttribute('data-orden-precio') || '';
        var f = opt.getAttribute('data-orden-fecha') || '';
        var d = opt.getAttribute('data-orden-desc') || '';
        if (ti) ti.value = t;
        if (pr) pr.value = p;
        if (fe) fe.value = f.length > 10 ? f.slice(0, 10) : f;
        if (de) de.value = d;
    }
    sel.addEventListener('change', applyFromOrden);
    <?php if ($action === 'new'): ?>
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', applyFromOrden);
    } else {
        applyFromOrden();
    }
    <?php endif; ?>
})();
</script>
<?php endif; ?>
