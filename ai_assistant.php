<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/includes/ai_helper.php';

$text = isset($_POST['text']) && is_string($_POST['text']) ? trim($_POST['text']) : '';

if ($text === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Texto vacío']);
    exit;
}

$result = repairly_ai_analyze($text);

echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
