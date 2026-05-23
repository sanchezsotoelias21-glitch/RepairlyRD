<?php
/**
 * Generador de QR dinámico para seguimiento de órdenes
 * No guarda archivos físicamente - ideal para Railway
 */

require_once 'libs/phpqrcode/qrlib.php';

// Obtener código de seguimiento del parámetro GET
$codigo = isset($_GET['codigo']) ? trim($_GET['codigo']) : '';

if (empty($codigo)) {
    header('HTTP/1.1 400 Bad Request');
    exit('Código no proporcionado');
}

// Construir URL de seguimiento
$baseUrl = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
$baseUrl .= $_SERVER['HTTP_HOST'];
$seguimientoUrl = $baseUrl . '/seguimiento.php?codigo=' . urlencode($codigo);

// Configuración del QR
$level = QR_ECLEVEL_H;  // Nivel de corrección de errores alto
$size = 10;             // Tamaño del QR
$margin = 2;            // Margen

// Generar QR y enviar como imagen PNG directamente al navegador
header('Content-Type: image/png');
QRcode::png($seguimientoUrl, null, $level, $size, $margin);
