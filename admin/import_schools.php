<?php
require_once __DIR__ . '/../config/database.php';
require_login();
if ((current_user()['role'] ?? '') !== 'admin') redirect('dashboard.php');

$source = __DIR__ . '/../SMKSMA404.csv';
$message = '';
$error = '';
function csvCapacity(?string $value): ?int
{
    $digits = preg_replace('/[^0-9]/', '', (string)$value);
    return $digits === '' ? null : (int)$digits;
}

if (is_readable($source) && ($handle = fopen($source, 'r'))) {
    $firstLine = fgets($handle);
    $delimiter = (substr_count($firstLine, ',') >= substr_count($firstLine, ';')) ? ',' : ';';
    rewind($handle);
    $headers = fgetcsv($handle, 0, $delimiter);
    if (isset($headers[0])) $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', $headers[0]);
    
    $pdo = db();
    $pdo->beginTransaction();
    
    try {
        $upsert = $pdo->prepare('INSERT INTO school_data(npsn,name,address,domicile,school_type,average_score,accreditation,source_file,admission_prestasi_akademik,admission_prestasi_nonakademik,admission_tahap_kedua,admission_afirmasi,admission_zonasi,capacity_prestasi_akademik,capacity_prestasi_nonakademik,capacity_tahap_kedua,capacity_afirmasi,capacity_zonasi) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE name=VALUES(name),address=VALUES(address),domicile=VALUES(domicile),school_type=VALUES(school_type),average_score=VALUES(average_score),accreditation=VALUES(accreditation),source_file=VALUES(source_file),admission_prestasi_akademik=VALUES(admission_prestasi_akademik),admission_prestasi_nonakademik=VALUES(admission_prestasi_nonakademik),admission_tahap_kedua=VALUES(admission_tahap_kedua),admission_afirmasi=VALUES(admission_afirmasi),admission_zonasi=VALUES(admission_zonasi),capacity_prestasi_akademik=VALUES(capacity_prestasi_akademik),capacity_prestasi_nonakademik=VALUES(capacity_prestasi_nonakademik),capacity_tahap_kedua=VALUES(capacity_tahap_kedua),capacity_afirmasi=VALUES(capacity_afirmasi),capacity_zonasi=VALUES(capacity_zonasi),is_active=1');
        $find = $pdo->prepare('SELECT id FROM school_data WHERE npsn = ?');
        $clear = $pdo->prepare('DELETE FROM school_competencies WHERE school_id = ?');
        $competency = $pdo->prepare('INSERT INTO school_competencies(school_id,competency) VALUES(?,?)');
        
        $count = 0;
        $rowCount = 0;
        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $rowCount++;
            if (count($row) < 20) continue;
            
            $type = stripos(trim($row[1]), 'SMA') === 0 ? 'SMA' : 'SMK';
            $accreditation = trim($row[19] ?? '') ?: 'N/A';
            
            try {
                $upsert->execute([
                    $row[0], 
                    trim($row[1]), 
                    trim($row[2]), 
                    trim($row[13]), 
                    $type, 
                    is_numeric(str_replace(',', '.', trim($row[14]))) ? (float)str_replace(',', '.', trim($row[14])) : null,
                    $accreditation, 
                    'SMKSMA404.csv',
                    is_numeric(str_replace(',', '.', trim($row[14]))) ? (float)str_replace(',', '.', trim($row[14])) : null,
                    is_numeric(str_replace(',', '.', trim($row[15]))) ? (float)str_replace(',', '.', trim($row[15])) : null,
                    is_numeric(str_replace(',', '.', trim($row[16]))) ? (float)str_replace(',', '.', trim($row[16])) : null,
                    is_numeric(str_replace(',', '.', trim($row[17]))) ? (float)str_replace(',', '.', trim($row[17])) : null,
                    is_numeric(str_replace(',', '.', trim($row[18]))) ? (float)str_replace(',', '.', trim($row[18])) : null,
                    csvCapacity($row[20] ?? null), csvCapacity($row[21] ?? null), csvCapacity($row[22] ?? null), csvCapacity($row[23] ?? null), csvCapacity($row[24] ?? null)
                ]);
                
                $find->execute([$row[0]]); 
                $schoolId = (int)$find->fetchColumn(); 
                $clear->execute([$schoolId]);
                
                foreach (array_slice($row, 3, 10) as $value) {
                    if (trim((string)$value) !== '') {
                        $competency->execute([$schoolId, trim($value)]);
                    }
                }
                
                $count++;
            } catch (Throwable $e) {
                // Skip problematic rows but continue
                continue;
            }
        }
        
        $pdo->commit();
        $message = $count . ' schools imported successfully from SMKSMA404.csv (total rows: ' . $rowCount . ')';
    } catch (Throwable $exception) {
        $pdo->rollBack();
        $error = 'Import failed: ' . $exception->getMessage();
    }
    
    fclose($handle);
} else {
    $error = 'Cannot read SMKSMA404.csv file';
}

$title = 'Import Schools'; 
require __DIR__ . '/../includes/header.php';
?>
<div class="container py-5">
<div class="panel">
<h2>Import Schools from SMKSMA404.csv</h2>
<?php if ($message): ?>
<div class="alert alert-success"><?= e($message) ?></div>
<?php endif; ?>
<?php if ($error): ?>
<div class="alert alert-danger"><?= e($error) ?></div>
<?php endif; ?>
<a href="<?= APP_URL ?>/admin/index.php" class="btn btn-primary">Back to Admin Dashboard</a>
</div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
