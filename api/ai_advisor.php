<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../advisor/AdvisorService.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

try {
    require_login();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new RuntimeException('Method not allowed.');
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf'] ?? null);
    if (!verify_csrf(is_string($token) ? $token : null)) throw new RuntimeException('Sesi keamanan tidak valid.');
    $payload = json_decode((string)file_get_contents('php://input'), true);
    $question = is_array($payload) ? ($payload['message'] ?? '') : ($_POST['message'] ?? '');
    $user = current_user();
    $result = AdvisorService::answer((string)$question, $user ?: []);
    echo json_encode(['success' => true] + $result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (InvalidArgumentException $exception) {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => $exception->getMessage()], JSON_UNESCAPED_UNICODE);
} catch (Throwable $exception) {
    error_log('AI School Advisor error: ' . $exception->getMessage());
    http_response_code(500);
    $message = str_contains($exception->getMessage(), 'API key ditolak') ? 'API key OpenAI ditolak. Periksa OPENAI_API_KEY pada konfigurasi Apache.' : (str_contains($exception->getMessage(), 'Quota OpenAI habis') ? 'Kuota OpenAI sedang habis. Periksa quota dan billing OpenAI.' : 'AI School Advisor sedang tidak tersedia. Periksa koneksi server OpenAI.');
    echo json_encode(['success' => false, 'error' => $message], JSON_UNESCAPED_UNICODE);
}
