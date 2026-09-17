<?php
declare(strict_types=1);
require_once __DIR__ . '/GaussianNaiveBayes.php';
require_once __DIR__ . '/DataPreprocessor.php';
require_once __DIR__ . '/Evaluator.php';

final class Trainer
{
    public static function train(array $rows, array $features, string $target, float $ratio = .8): array
    {
        [$trainRows, $testRows] = DataPreprocessor::split($rows, $ratio); $model = new GaussianNaiveBayes(); $model->train($trainRows, $features, $target); $expected = []; $predicted = [];
        foreach ($testRows as $row) { $expected[] = (string)$row[$target]; $predicted[] = (string)$model->predict(DataPreprocessor::numeric($row, $features))['class']; }
        return ['model'=>$model,'train_count'=>count($trainRows),'test_count'=>count($testRows),'metrics'=>Evaluator::classification($expected, $predicted)];
    }
}
?>