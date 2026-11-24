<?php
declare(strict_types=1);
require_once __DIR__ . '/GaussianNaiveBayes.php';

final class Predictor
{
    public static function fromJson(string $path, array $input): ?array
    {
        if (!is_readable($path)) return null; $payload = json_decode((string)file_get_contents($path), true); if (!is_array($payload) || !isset($payload['model'])) return null; $model = new GaussianNaiveBayes(); $model->fromArray($payload['model']); return $model->predict($input);
    }
}
?>