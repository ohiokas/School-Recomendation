<?php
declare(strict_types=1);

final class DataPreprocessor
{
    public static function numeric(array $row, array $features): array
    {
        $result = []; foreach ($features as $feature) $result[$feature] = (float)str_replace(',', '.', trim((string)($row[$feature] ?? 0))); return $result;
    }
    public static function split(array $rows, float $trainRatio = .8): array
    {
        if (count($rows) < 2) return [$rows, []]; $split = max(1, min(count($rows) - 1, (int)floor(count($rows) * $trainRatio))); return [array_slice($rows, 0, $split), array_slice($rows, $split)];
    }
}
?>