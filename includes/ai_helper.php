<?php

function repairly_ai_analyze(string $text): array {
    $base = ia_diagnostico_local($text);
    $tipo = (string)($base['tipo'] ?? 'Falla general');
    return [
        'problema' => $tipo,
        'tipo' => $tipo,
        'prioridad' => (string)($base['prioridad'] ?? 'Media'),
        'soluciones' => array_values(array_filter($base['soluciones'] ?? [], function ($s): bool {
            return is_string($s) && trim($s) !== '';
        })),
        'resumen' => 'Análisis generado a partir de los diagnósticos seleccionados.',
    ];
}

function ia_diagnostico_local($texto) {
    $texto = strtolower(trim($texto));
    $tipo = "Falla general";
    $soluciones = [];

    $map = [
        'pantalla' => ['Pantalla / Display','Revisar flex de pantalla','Probar display nuevo','Verificar daño por golpes'],
        'no enciende' => ['Encendido','Verificar batería','Revisar centro de carga','Comprobar corto en motherboard'],
        'bateria' => ['Batería','Probar batería nueva','Revisar consumo','Verificar carga'],
        'calienta' => ['Sobrecalentamiento','Cambiar pasta térmica','Limpiar equipo','Revisar cortos'],
        'mojado' => ['Daño por líquido','Aplicar limpieza ultrasónica','Revisar sulfato','Medir voltajes'],
        'lento' => ['Rendimiento','Optimizar sistema','Cambiar SSD','Revisar RAM']
    ];

    foreach ($map as $key => $data) {
        if (strpos($texto, $key) !== false) {
            $tipo = $data[0];
            $soluciones = array_slice($data,1);
            break;
        }
    }

    if (empty($soluciones)) {
        $soluciones = [
            'Realizar diagnóstico eléctrico',
            'Revisar componentes principales',
            'Probar piezas compatibles'
        ];
    }

    return [
        'tipo' => $tipo,
        'soluciones' => $soluciones,
        'prioridad' => strlen($texto) > 120 ? 'Alta' : 'Media'
    ];
}
