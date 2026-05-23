<?php
/**
 * Generador de QR dinámico para seguimiento de órdenes
 * No guarda archivos físicamente - ideal para Railway
 */

// Habilitar reporte de errores para debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);

try {
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
    $level = QR_ECLEVEL_H;  // Nivel de corrección de errores alto
    $size = 10;             // Tamaño del QR
    $margin = 2;            // Margen

    // Generar QR y enviar como imagen PNG directamente al navegador
    header('Content-Type: image/png');
    QRcode::png($seguimientoUrl, null, $level, $size, $margin);
} catch (Exception $e) {
    // En caso de error, mostrar una imagen con mensaje de error
    header('Content-Type: image/png');
    $img = imagecreatetruecolor(60, 60);
    $bg = imagecolorallocate($img, 255, 240, 240);
    $text = imagecolorallocate($img, 200, 50, 50);
    imagefill($img, 0, 0, $bg);
    imagestring($img, 1, 5, 25, 'Error QR', $text);
    imagepng($img);
    imagedestroy($img);
}
