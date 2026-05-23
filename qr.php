<?php
/**
 * Generador de QR dinámico para seguimiento de órdenes
 */

// Habilitar reporte de errores
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Verificar si la extensión GD está disponible
if (!extension_loaded('gd')) {
    die('Error: La extensión GD de PHP no está habilitada. Es necesaria para generar imágenes QR.');
}

require_once __DIR__ . '/libs/phpqrcode/qrlib.php';

// Obtener código de seguimiento del parámetro GET
$codigo = isset($_GET['codigo']) ? trim($_GET['codigo']) : '';

if (empty($codigo)) {
    header('Content-Type: image/png');
    $img = imagecreatetruecolor(60, 60);
    $bg = imagecolorallocate($img, 240, 240, 240);
    $text = imagecolorallocate($img, 150, 150, 150);
    imagefill($img, 0, 0, $bg);
    imagestring($img, 1, 5, 25, 'Sin codigo', $text);
    imagepng($img);
    imagedestroy($img);
    exit;
}

// Construir URL de seguimiento
$isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
$proto = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '';
if (is_string($proto) && strtolower(trim(explode(',', $proto)[0])) === 'https') {
    $isHttps = true;
}
$baseUrl = ($isHttps ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'];
$seguimientoUrl = $baseUrl . '/seguimiento.php?codigo=' . urlencode($codigo);

// Configuración del QR
$level = QR_ECLEVEL_H;
$size = 10;
$margin = 2;

// Generar QR y enviar como imagen PNG
header('Content-Type: image/png');
QRcode::png($seguimientoUrl, null, $level, $size, $margin);
