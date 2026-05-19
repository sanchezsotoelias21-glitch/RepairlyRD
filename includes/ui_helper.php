<?php

function render_stats_cards(array $stats): void {

$palettes = [
    // Orden oficial (como dashboard): Proceso, Pendientes, Ingresos, Garantías, Completadas, Con falla
    ['bg' => '#E3F2FD', 'line' => '#2b7abc', 'text' => '#2b7abc'],
    ['bg' => '#FFF3E0', 'line' => '#FF9500', 'text' => '#FF9500'],
    ['bg' => '#E8F5E9', 'line' => '#00AA44', 'text' => '#00AA44'],
    ['bg' => '#F3E5F5', 'line' => '#7B4EC4', 'text' => '#7B4EC4'],
    ['bg' => '#F5F5F5', 'line' => '#424242', 'text' => '#424242'],
    ['bg' => '#FFEBEE', 'line' => '#FF4444', 'text' => '#FF4444'],
];

$render_row = function (array $row, int $offset) use ($palettes): void {
    echo '<div class="kpi-grid">';
    foreach ($row as $idx => $s) {
        if (!is_array($s)) {
            continue;
        }
        $p = $palettes[($offset + $idx) % count($palettes)];
        $icon = (string)($s['icon'] ?? $s['icono'] ?? 'ti-chart-bar');
        $label = (string)($s['label'] ?? '');
        $value = (string)($s['value'] ?? $s['valor'] ?? '');
        $sub = (string)($s['sub'] ?? '');

        echo '<div class="kpi-card" style="background:' . $p['bg'] . ';--kpi-color:' . $p['line'] . ';">';
        echo '<div class="kpi-label" style="color:' . $p['text'] . ';">';
        echo '<i class="ti ' . htmlspecialchars($icon) . '" aria-hidden="true"></i>';
        echo htmlspecialchars($label);
        echo '</div>';
        echo '<div class="kpi-valor" style="color:' . $p['text'] . ';">' . htmlspecialchars($value) . '</div>';
        echo '<div class="kpi-sub">' . $sub . '</div>';
        echo '</div>';
    }
    echo '</div>';
};

$total = count($stats);
if ($total === 0) {
    return;
}

// Misma distribución visual del dashboard: 2 filas de 3 cuando aplica.
$render_row(array_slice($stats, 0, 3), 0);
if ($total > 3) {
    $render_row(array_slice($stats, 3, 3), 3);
}

}

function render_ai_widget(): void {
?>

<style>

.repairly-ai{
    display:none;
    margin-top:24px;
    border-radius:24px;
    overflow:hidden;

    background:
    linear-gradient(
        135deg,
        #020617,
        #0F172A,
        #1E3A8A
    );

    box-shadow:0 20px 50px rgba(0,0,0,.25);

    color:#fff;
}
.repairly-ai.open{display:block;}

.ai-header{
    padding:24px;

    border-bottom:1px solid rgba(255,255,255,.08);

    display:flex;
    justify-content:space-between;
    align-items:center;
    flex-wrap:wrap;
    gap:12px;
}

.ai-badge{
    background:rgba(255,255,255,.08);

    border:1px solid rgba(255,255,255,.08);

    padding:10px 16px;

    border-radius:14px;
}

.ai-body{
    padding:24px;
}

.ai-analysis{
    display:grid;

    grid-template-columns:
    repeat(auto-fit,minmax(220px,1fr));

    gap:16px;

    margin-top:20px;
}

.ai-card{
    background:rgba(255,255,255,.06);

    border:1px solid rgba(255,255,255,.08);

    border-radius:18px;

    padding:18px;

    backdrop-filter:blur(10px);
}

.ai-title{
    font-size:13px;

    text-transform:uppercase;

    opacity:.7;

    margin-bottom:10px;

    letter-spacing:.5px;
}

.ai-value{
    font-size:18px;
    font-weight:700;
}

.ai-list{
    margin:0;
    padding-left:18px;

    line-height:1.8;
}

</style>

<div class="repairly-ai" id="repairly-ai-widget">

    <div class="ai-header">

        <div>

            <div
                style="
                    font-size:28px;
                    font-weight:800;
                "
            >

                RepairlyRD AI Analysis

            </div>

            <div
                style="
                    opacity:.7;
                    margin-top:4px;
                "
            >

                Análisis automático basado
                en los diagnósticos
                seleccionados

            </div>

        </div>

        <div class="ai-badge" id="ia-status">

            Esperando selección

        </div>

    </div>

    <div class="ai-body">

        <div
            id="ia-placeholder"
            style="opacity:.7"
        >

            Selecciona uno o varios registros
            y pulsa <strong>Analizar con IA</strong>
            para generar el análisis.

        </div>

        <div
            id="ia-result"
            style="display:none"
        ></div>

    </div>

</div>

<script>
window.REPAIRLY_AI_ENDPOINT = <?= json_encode(rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/') . '/ai_assistant.php') ?>;

function analizarRegistroIA(descripcion=''){
    var widget = document.getElementById('repairly-ai-widget');
    if (widget) widget.classList.add('open');
    const statusEl = document.getElementById('ia-status');
    const placeholderEl = document.getElementById('ia-placeholder');
    const resultEl = document.getElementById('ia-result');

    const text = String(descripcion || '').trim();
    if (!text) {
        if (statusEl) statusEl.textContent = 'Sin datos';
        if (placeholderEl) placeholderEl.style.display = 'block';
        if (resultEl) resultEl.style.display = 'none';
        return;
    }

    if (statusEl) statusEl.textContent = 'Analizando...';
    if (placeholderEl) placeholderEl.style.display = 'none';
    if (resultEl) {
        resultEl.style.display = 'block';
        resultEl.innerHTML = '<div style="opacity:.75;">Procesando diagnósticos seleccionados...</div>';
    }

    var endpoint = (window.REPAIRLY_AI_ENDPOINT || 'ai_assistant.php');
    fetch(endpoint, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
        body: new URLSearchParams({ text: text }).toString()
    })
        .then(function (r) {
            if (!r.ok) throw new Error('HTTP ' + r.status);
            return r.json();
        })
        .then(function (data) {
            if (!data || data.error) throw new Error((data && data.error) ? data.error : 'Error');

            const tipo = data.error || data.tipo || 'Falla general';
            const prioridad = data.prioridad || 'Media';
            const soluciones = Array.isArray(data.soluciones) ? data.soluciones : [];
            const resumen = data.resumen || '—';

            if (statusEl) statusEl.textContent = 'Análisis completado';

            const esc = function (s) {
                return String(s).replace(/[&<>"]/g, function (c) {
                    return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' })[c] || c;
                });
            };

            const list = soluciones.length
                ? ('<ul class="ai-list">' + soluciones.map(function (s) { return '<li>' + esc(s) + '</li>'; }).join('') + '</ul>')
                : '<div style="opacity:.8;">Sin recomendaciones.</div>';

            if (resultEl) {
                resultEl.innerHTML =
                    '<div class="ai-analysis">' +
                    '<div class="ai-card"><div class="ai-title">Error detectado</div><div class="ai-value">' + esc(tipo) + '</div></div>' +
                    '<div class="ai-card"><div class="ai-title">Nivel de prioridad</div><div class="ai-value">' + esc(prioridad) + '</div></div>' +
                    '<div class="ai-card"><div class="ai-title">Resumen</div><div class="ai-value" style="font-size:13px;line-height:1.3;">' + esc(resumen) + '</div></div>' +
                    '<div class="ai-card" style="grid-column:1/-1"><div class="ai-title">Recomendaciones de solución</div>' + list + '</div>' +
                    '<div class="ai-card" style="grid-column:1/-1"><div class="ai-title">Datos analizados</div><div style="opacity:.8;line-height:1.7;white-space:pre-wrap;">' + esc(text) + '</div></div>' +
                    '</div>';
            }
        })
        .catch(function (err) {
            if (statusEl) statusEl.textContent = 'Error IA';
            if (resultEl) {
                resultEl.innerHTML = '<div style="opacity:.85;">No se pudo generar el análisis: ' + String(err && err.message ? err.message : err) + '</div>';
            }
        });

    if (widget && typeof widget.scrollIntoView === 'function') {
        widget.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}

</script>

<?php
}
?>
