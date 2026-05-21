<?php



header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/includes/ai_helper.php';

set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});

$text = isset($_POST['text']) && is_string($_POST['text']) ? trim($_POST['text']) : '';

if ($text === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Texto vacío']);
    exit;
}

try {
    $result = repairly_ai_analyze($text);
    echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error interno: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
