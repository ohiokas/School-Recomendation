<?php
declare(strict_types=1);

const APP_NAME = 'SMAKITA';

define('APP_URL', getenv('APP_URL') ?: '/bleszsme');

define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'sma_kita');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASSWORD', getenv('DB_PASSWORD') ?: '');

const UPLOAD_PATH = __DIR__ . '/../uploads';
const MODEL_PATH = __DIR__ . '/../models/naive_bayes';

date_default_timezone_set('Asia/Jakarta');

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): never
{
    header('Location: ' . APP_URL . '/' . ltrim($path, '/'));
    exit;
}

function calculateAge(string $birthDate): int
{
    return (new DateTimeImmutable($birthDate))->diff(new DateTimeImmutable('today'))->y;
}
?>
