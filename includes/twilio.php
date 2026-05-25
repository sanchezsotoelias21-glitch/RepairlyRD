<?php

$sid = getenv('TWILIO_SID') ?: 'YOUR_TWILIO_SID';
$token = getenv('TWILIO_TOKEN') ?: 'YOUR_TWILIO_TOKEN';

$mensaje = "Hola desde RepairlyRD";

$telefono = "whatsapp:+18295921607";

$url = "https://api.twilio.com/2010-04-01/Accounts/$sid/Messages.json";

$data = [
    'From' => 'whatsapp:+14155238886',
    'To' => $telefono,
    'Body' => $mensaje
];

$options = [
    CURLOPT_URL => $url,
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_USERPWD => "$sid:$token",
    CURLOPT_POSTFIELDS => http_build_query($data),
];

$ch = curl_init();

curl_setopt_array($ch, $options);

$response = curl_exec($ch);

curl_close($ch);

echo $response;
?>
