<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'secure' => !empty($_SERVER['HTTPS'])]);
    session_start();
}

function csrf_token(): string
{
    return $_SESSION['csrf_token'] ??= bin2hex(random_bytes(32));
}

function verify_csrf(?string $token): bool
{
    return is_string($token) && hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function require_login(): void
{
    $user = current_user();
    if (!$user || empty($user['id'])) {
        redirect('login.php');
    }
    try {
        $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASSWORD, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $check = $pdo->prepare('SELECT id FROM users WHERE id = ? LIMIT 1');
        $check->execute([(int)$user['id']]);
        if (!$check->fetchColumn()) {
            $_SESSION = [];
            session_regenerate_id(true);
            redirect('login.php');
        }
    } catch (Throwable $exception) {
        error_log('SMAKITA authentication database check failed: ' . $exception->getMessage());
        redirect('login.php');
    }
}
?>