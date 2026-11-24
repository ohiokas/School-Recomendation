<?php
declare(strict_types=1);

final class DataPreprocessor
{
    public static function numeric(array $row, array $features): array
    {
        $result = [];
        foreach ($features as $feature) {
            $result[$feature] = (float)str_replace(',', '.', trim((string)($row[$feature] ?? 0)));
        }
        return $result;
    }

    /**
     * Stratified holdout split: each class is split independently so that
     * every class is represented in both training and testing data.
     */
    public static function split(array $rows, float $trainRatio = .8, string $target = 'Job profession', int $seed = 42): array
    {
        if (count($rows) < 2) return [$rows, []];
        $groups = [];
        foreach ($rows as $row) {
            $class = trim((string)($row[$target] ?? ''));
            if ($class === '') continue;
            $groups[$class][] = $row;
        }

        mt_srand($seed);
        $trainRows = [];
        $testRows = [];
        foreach ($groups as $items) {
            for ($i = count($items) - 1; $i > 0; $i--) {
                $j = mt_rand(0, $i);
                [$items[$i], $items[$j]] = [$items[$j], $items[$i]];
            }
            $count = count($items);
            $split = max(1, min($count - 1, (int)floor($count * $trainRatio)));
            array_push($trainRows, ...array_slice($items, 0, $split));
            array_push($testRows, ...array_slice($items, $split));
        }

        // Shuffle the combined sets to avoid retaining class-block order.
        for ($i = count($trainRows) - 1; $i > 0; $i--) {
            $j = mt_rand(0, $i);
            [$trainRows[$i], $trainRows[$j]] = [$trainRows[$j], $trainRows[$i]];
        }
        for ($i = count($testRows) - 1; $i > 0; $i--) {
            $j = mt_rand(0, $i);
            [$testRows[$i], $testRows[$j]] = [$testRows[$j], $testRows[$i]];
        }
        return [$trainRows, $testRows];
    }
}
?>
