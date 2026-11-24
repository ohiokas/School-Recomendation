<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../ml/Trainer.php';
require_login();
if ((current_user()['role'] ?? '') !== 'admin') redirect('dashboard.php');
$message = ''; $metrics = null;
$features = ['Linguistic','Musical','Bodily','Logical - Mathematical','Spatial-Visualization','Interpersonal','Intrapersonal','Naturalist'];
$targetName = 'Job profession'; $xlsx = __DIR__ . '/../Dataset Project 404.xlsx';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['csrf'] ?? null)) {
    $autoload = __DIR__ . '/../vendor/autoload.php';
    if (!is_readable($autoload)) $message = 'PhpSpreadsheet is not installed. Run composer install first.';
    elseif (!is_readable($xlsx)) $message = 'Primary XLSX dataset was not found.';
    else {
        require_once $autoload;
        try {
            $sheet = PhpOffice\PhpSpreadsheet\IOFactory::load($xlsx)->getSheetByName('original');
            if (!$sheet) throw new RuntimeException('Sheet original was not found.');
            $raw = $sheet->toArray(null, true, true, true); $headers = array_map(static fn($value) => trim((string)$value), array_shift($raw) ?: []); $index = array_flip($headers); $targetColumn = $index[$targetName] ?? null; $featureIndexes = array_map(static fn($name) => $index[$name] ?? null, $features);
            if ($targetColumn === null || in_array(null, $featureIndexes, true)) throw new RuntimeException('Required headers were not found in the original sheet.');
            $rows = [];
            foreach ($raw as $row) { $targetValue = trim((string)($row[$targetColumn] ?? '')); if ($targetValue === '') continue; $item = [$targetName=>$targetValue]; foreach ($featureIndexes as $position=>$column) $item[$features[$position]] = (float)str_replace(',', '.', (string)($row[$column] ?? 0)); $rows[] = $item; }
            if (count($rows) < 2) throw new RuntimeException('The dataset has too few valid rows.');
            $training = Trainer::train($rows, $features, $targetName); $metrics = $training['metrics'] + ['rows'=>count($rows),'train'=>$training['train_count'],'test'=>$training['test_count']];
            if (!is_dir(MODEL_PATH)) mkdir(MODEL_PATH, 0755, true); $version = 'NB-' . date('Ymd-His'); $path = MODEL_PATH . '/' . $version . '.json'; file_put_contents($path, json_encode(['model_type'=>'Gaussian Naive Bayes','version'=>$version,'dataset'=>'Dataset Project 404.xlsx','target'=>$targetName,'features'=>$features,'metrics'=>$metrics,'model'=>$training['model']->toArray()], JSON_PRETTY_PRINT));
            $pdo = db(); $pdo->beginTransaction(); $pdo->prepare('INSERT INTO datasets(name,file_type,source_path,row_count,status) VALUES(?,?,?,?,?)')->execute(['Dataset Project 404.xlsx','XLSX','Dataset Project 404.xlsx',count($rows),'validated']); $datasetId = (int)$pdo->lastInsertId(); $pdo->prepare('INSERT INTO dataset_versions(dataset_id,version_name) VALUES(?,?)')->execute([$datasetId,$version]); $versionId = (int)$pdo->lastInsertId(); $pdo->prepare('INSERT INTO ml_models(version_name,model_type,dataset_version_id,target_name,features,metrics,model_path) VALUES(?,?,?,?,?,?,?)')->execute([$version,'Gaussian Naive Bayes',$versionId,$targetName,json_encode($features),json_encode($metrics),$path]); $modelId = (int)$pdo->lastInsertId(); $pdo->prepare('INSERT INTO training_runs(ml_model_id,train_count,test_count,metrics) VALUES(?,?,?,?)')->execute([$modelId,$training['train_count'],$training['test_count'],json_encode($metrics)]); $pdo->commit(); $message = 'Training completed using Job profession labels. Model version: ' . $version;
        } catch (Throwable $exception) { if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) $pdo->rollBack(); $message = 'Training failed: ' . $exception->getMessage(); }
    }
}
$title = 'Model training'; require __DIR__ . '/../includes/header.php';
?><div class="container py-5"><div class="eyebrow">Admin · machine learning</div><h1 class="section-title mb-4">Train a real model.</h1><?php if ($message): ?><div class="alert alert-info"><?= e($message) ?></div><?php endif; ?><div class="panel mb-4"><h4>Primary dataset</h4><p class="text-muted">Dataset Project 404.xlsx · sheet <strong>original</strong> · target <strong><?= e($targetName) ?></strong> · eight intelligence indicators as numeric features.</p><form method="post"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><button class="btn btn-accent rounded-pill">Validate, train, and save <i class="bi bi-cpu"></i></button></form></div><?php if ($metrics): ?><div class="row g-3"><?php foreach (['rows'=>'Records','train'=>'Training','test'=>'Testing','accuracy'=>'Accuracy','precision'=>'Macro precision','recall'=>'Macro recall','f1_score'=>'Macro F1'] as $key=>$label): ?><div class="col-6 col-lg-3"><div class="panel"><div class="eyebrow"><?= $label ?></div><div class="stat mt-2"><?= in_array($key,['accuracy','precision','recall','f1_score'],true) ? round($metrics[$key]*100,2).'%' : $metrics[$key] ?></div></div></div><?php endforeach; ?></div><div class="panel mt-4"><h4>Confusion matrix</h4><pre class="small mb-0"><?= e(json_encode($metrics['confusion_matrix'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre></div><?php endif; ?></div><?php require __DIR__ . '/../includes/footer.php'; ?>