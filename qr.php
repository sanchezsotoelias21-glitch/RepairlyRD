<?php
/**
 * Generador de QR dinámico para seguimiento de órdenes
 * Usando API externa para evitar problemas con librerías locales
 */

// Obtener código de seguimiento del parámetro GET
$codigo = isset($_GET['codigo']) ? trim($_GET['codigo']) : '';

if (empty($codigo)) {
    // Mostrar imagen de error si no hay código
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

// Usar API externa de Google Charts para generar el QR
$qrApiUrl = 'https://chart.googleapis.com/chart?cht=qr&chs=150x150&chl=' . urlencode($seguimientoUrl);

// Redirigir a la API de Google
header('Location: ' . $qrApiUrl);
exit;
