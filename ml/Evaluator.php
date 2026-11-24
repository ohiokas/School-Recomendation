<?php
declare(strict_types=1);

final class Evaluator
{
    public static function classification(array $expected, array $predicted): array
    {
        $classes = array_values(array_unique(array_merge($expected, $predicted)));
        sort($classes);
        $matrix = [];
        foreach ($classes as $actual) foreach ($classes as $predictedClass) $matrix[$actual][$predictedClass] = 0;
        foreach ($expected as $index => $actual) { $prediction = $predicted[$index] ?? ''; $matrix[$actual][$prediction] = ($matrix[$actual][$prediction] ?? 0) + 1; }
        $perClass = []; $precisionSum = 0; $recallSum = 0; $f1Sum = 0;
        foreach ($classes as $class) {
            $tp = $matrix[$class][$class] ?? 0; $fp = 0; $fn = 0;
            foreach ($classes as $other) { if ($other !== $class) { $fp += $matrix[$other][$class] ?? 0; $fn += $matrix[$class][$other] ?? 0; } }
            $precision = $tp / max(1, $tp + $fp); $recall = $tp / max(1, $tp + $fn); $f1 = ($precision + $recall) > 0 ? (2 * $precision * $recall) / ($precision + $recall) : 0;
            $perClass[$class] = ['true_positive'=>$tp,'false_positive'=>$fp,'false_negative'=>$fn,'precision'=>round($precision,4),'recall'=>round($recall,4),'f1_score'=>round($f1,4)]; $precisionSum += $precision; $recallSum += $recall; $f1Sum += $f1;
        }
        $correct = 0; foreach ($expected as $index => $actual) if (($predicted[$index] ?? null) === $actual) $correct++;
        $count = max(1, count($classes));
        return ['accuracy'=>round($correct / max(1,count($expected)),4),'precision'=>round($precisionSum/$count,4),'recall'=>round($recallSum/$count,4),'f1_score'=>round($f1Sum/$count,4),'per_class'=>$perClass,'confusion_matrix'=>$matrix,'evaluated_records'=>count($expected)];
    }
}
?>