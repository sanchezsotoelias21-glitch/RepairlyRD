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

<div class="repairly-ai">

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
                en la descripción del
                diagnóstico seleccionado

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

            Selecciona un registro
            para generar automáticamente
            el análisis inteligente.

        </div>

        <div
            id="ia-result"
            style="display:none"
        ></div>

    </div>

</div>

<script>

function analizarRegistroIA(descripcion=''){

    if(!descripcion || descripcion.trim()===''){

        document.getElementById(
            'ia-status'
        ).innerHTML='Sin datos';

        return;
    }

    const texto =
    descripcion.toLowerCase();

    let resultado = {

        problema:'Diagnóstico general',

        prioridad:'Media',

        probabilidad:'68%',

        acciones:[

            'Realizar revisión técnica completa',

            'Verificar componentes principales',

            'Ejecutar pruebas eléctricas'

        ]
    };

    if(texto.includes('no enciende')){

        resultado = {

            problema:'Falla de encendido',

            prioridad:'Crítica',

            probabilidad:'92%',

            acciones:[

                'Revisar línea principal de voltaje',

                'Comprobar PMIC y consumo',

                'Probar fuente DC'

            ]
        };
    }

    if(
        texto.includes('mojado') ||
        texto.includes('liquido')
    ){

        resultado = {

            problema:'Daño por líquido',

            prioridad:'Alta',

            probabilidad:'95%',

            acciones:[

                'Aplicar limpieza ultrasónica',

                'Eliminar sulfato',

                'Medir cortos en motherboard'

            ]
        };
    }

    if(texto.includes('pantalla')){

        resultado = {

            problema:'Daño de display',

            prioridad:'Media',

            probabilidad:'88%',

            acciones:[

                'Probar otra pantalla',

                'Revisar flex',

                'Verificar IC de imagen'

            ]
        };
    }

    if(
        texto.includes('bateria') ||
        texto.includes('carga')
    ){

        resultado = {

            problema:'Sistema de carga',

            prioridad:'Media',

            probabilidad:'84%',

            acciones:[

                'Comprobar batería',

                'Revisar pin de carga',

                'Medir amperaje'

            ]
        };
    }

    document.getElementById(
        'ia-status'
    ).innerHTML='Análisis completado';

    document.getElementById(
        'ia-placeholder'
    ).style.display='none';

    document.getElementById(
        'ia-result'
    ).style.display='block';

    document.getElementById(
        'ia-result'
    ).innerHTML = `

    <div class="ai-analysis">

        <div class="ai-card">

            <div class="ai-title">

                Problema detectado

            </div>

            <div class="ai-value">

                ${resultado.problema}

            </div>

        </div>

        <div class="ai-card">

            <div class="ai-title">

                Nivel de prioridad

            </div>

            <div class="ai-value">

                ${resultado.prioridad}

            </div>

        </div>

        <div class="ai-card">

            <div class="ai-title">

                Probabilidad IA

            </div>

            <div class="ai-value">

                ${resultado.probabilidad}

            </div>

        </div>

        <div
            class="ai-card"
            style="grid-column:1/-1"
        >

            <div class="ai-title">

                Acciones recomendadas

            </div>

            <ul class="ai-list">

                ${resultado.acciones
                    .map(a=>`<li>${a}</li>`)
                    .join('')}

            </ul>

        </div>

        <div
            class="ai-card"
            style="grid-column:1/-1"
        >

            <div class="ai-title">

                Descripción analizada

            </div>

            <div
                style="
                    opacity:.8;
                    line-height:1.7;
                "
            >

                ${descripcion}

            </div>

        </div>

    </div>

    `;
}

</script>

<?php
}
?>
