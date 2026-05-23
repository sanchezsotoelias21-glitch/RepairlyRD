<?php

include 'libs/phpqrcode/qrlib.php';

$codigo = 'RPD-2026-001';

$url = 'https://repairlyrd-production.up.railway.app/seguimiento.php?codigo=' . $codigo;

$ruta = 'qrs/' . $codigo . '.png';

QRcode::png($url, $ruta, QR_ECLEVEL_H, 10);

echo "QR generado correctamente";
?>