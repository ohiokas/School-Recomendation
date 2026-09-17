<?php
declare(strict_types=1);

const APP_NAME = 'SMAKITA';
const APP_URL = '/bleszsme';
const DB_HOST = '127.0.0.1';
const DB_NAME = 'smakkita';
const DB_USER = 'root';
const DB_PASSWORD = '';
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