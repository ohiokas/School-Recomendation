<?php
require_once __DIR__ . '/../config/database.php';
require_login();
if ((current_user()['role'] ?? '') !== 'admin') redirect('dashboard.php');
$source = __DIR__ . '/../SMKSMA404.csv';
$message = '';
$error = '';
$preview = [];
$editing = null;
function csvCapacity(?string $value): ?int
{
    $digits = preg_replace('/[^0-9]/', '', (string)$value);
    return $digits === '' ? null : (int)$digits;
}

// Handle CSV import
if (is_readable($source) && ($handle = fopen($source, 'r'))) {
    $firstLine = fgets($handle);
    $delimiter = (substr_count($firstLine, ',') >= substr_count($firstLine, ';')) ? ',' : ';';
    rewind($handle);
    $headers = fgetcsv($handle, 0, $delimiter);
    if (isset($headers[0])) $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', $headers[0]);
    while (($row = fgetcsv($handle, 0, $delimiter)) !== false) if (count($row) >= 15) $preview[] = $row;
    fclose($handle);
}

// Handle CRUD operations
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['csrf'] ?? null)) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'import') {
        $pdo = db();
        $pdo->beginTransaction();
        try {
            $upsert = $pdo->prepare('INSERT INTO school_data(npsn,name,address,domicile,school_type,average_score,accreditation,source_file,admission_prestasi_akademik,admission_prestasi_nonakademik,admission_tahap_kedua,admission_afirmasi,admission_zonasi,capacity_prestasi_akademik,capacity_prestasi_nonakademik,capacity_tahap_kedua,capacity_afirmasi,capacity_zonasi) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE name=VALUES(name),address=VALUES(address),domicile=VALUES(domicile),school_type=VALUES(school_type),average_score=VALUES(average_score),accreditation=VALUES(accreditation),source_file=VALUES(source_file),admission_prestasi_akademik=VALUES(admission_prestasi_akademik),admission_prestasi_nonakademik=VALUES(admission_prestasi_nonakademik),admission_tahap_kedua=VALUES(admission_tahap_kedua),admission_afirmasi=VALUES(admission_afirmasi),admission_zonasi=VALUES(admission_zonasi),capacity_prestasi_akademik=VALUES(capacity_prestasi_akademik),capacity_prestasi_nonakademik=VALUES(capacity_prestasi_nonakademik),capacity_tahap_kedua=VALUES(capacity_tahap_kedua),capacity_afirmasi=VALUES(capacity_afirmasi),capacity_zonasi=VALUES(capacity_zonasi),is_active=1');
            $find = $pdo->prepare('SELECT id FROM school_data WHERE npsn = ?');
            $clear = $pdo->prepare('DELETE FROM school_competencies WHERE school_id = ?');
            $competency = $pdo->prepare('INSERT INTO school_competencies(school_id,competency) VALUES(?,?)');
            foreach ($preview as $row) {
                $type = stripos(trim($row[1]), 'SMA') === 0 ? 'SMA' : 'SMK';
                $accreditation = trim($row[19] ?? '') ?: 'N/A';
                $upsert->execute([$row[0], trim($row[1]), trim($row[2]), trim($row[13]), $type, is_numeric(str_replace(',', '.', trim($row[14]))) ? (float)str_replace(',', '.', trim($row[14])) : null, $accreditation, 'SMKSMA404.csv', (float)$row[14], (float)$row[15], (float)$row[16], (float)$row[17], (float)$row[18], csvCapacity($row[20] ?? null), csvCapacity($row[21] ?? null), csvCapacity($row[22] ?? null), csvCapacity($row[23] ?? null), csvCapacity($row[24] ?? null)]);
                $find->execute([$row[0]]); $schoolId = (int)$find->fetchColumn(); $clear->execute([$schoolId]);
                foreach (array_slice($row, 3, 10) as $value) if (trim((string)$value) !== '') $competency->execute([$schoolId, trim($value)]);
            }
            $pdo->prepare("INSERT INTO datasets(name,file_type,source_path,row_count,status) VALUES('SMKSMA404.csv','CSV',?,?, 'imported')")->execute(['SMKSMA404.csv', count($preview)]);
            $pdo->commit(); $message = count($preview) . ' schools imported successfully.';
        } catch (Throwable $exception) { $pdo->rollBack(); $error = 'Import failed: ' . $exception->getMessage(); }
    }
    
    elseif ($action === 'add' || $action === 'edit') {
        try {
            $npsn = trim($_POST['npsn']);
            $name = trim($_POST['name']);
            $address = trim($_POST['address']);
            $domicile = trim($_POST['domicile']);
            $schoolType = $_POST['school_type'];
            $averageScore = is_numeric($_POST['average_score']) ? (float)$_POST['average_score'] : null;
            $accreditation = trim($_POST['accreditation'] ?? 'N/A');
            $capacity = is_numeric($_POST['capacity']) ? (int)$_POST['capacity'] : null;
            
            if ($action === 'add') {
                $insert = db()->prepare('INSERT INTO school_data(npsn,name,address,domicile,school_type,average_score,accreditation,capacity,source_file) VALUES(?,?,?,?,?,?,?,?,?)');
                $insert->execute([$npsn, $name, $address, $domicile, $schoolType, $averageScore, $accreditation, $capacity, 'manual']);
                $schoolId = (int)db()->lastInsertId();
            } else {
                $schoolId = (int)$_POST['school_id'];
                $update = db()->prepare('UPDATE school_data SET npsn=?,name=?,address=?,domicile=?,school_type=?,average_score=?,accreditation=?,capacity=? WHERE id=?');
                $update->execute([$npsn, $name, $address, $domicile, $schoolType, $averageScore, $accreditation, $capacity, $schoolId]);
                db()->prepare('DELETE FROM school_competencies WHERE school_id=?')->execute([$schoolId]);
            }
            
            // Add competencies
            if (!empty($_POST['competencies'])) {
                $compInsert = db()->prepare('INSERT INTO school_competencies(school_id,competency) VALUES(?,?)');
                foreach ($_POST['competencies'] as $comp) {
                    if (trim($comp) !== '') $compInsert->execute([$schoolId, trim($comp)]);
                }
            }
            
            $message = 'School ' . ($action === 'add' ? 'added' : 'updated') . ' successfully.';
        } catch (Throwable $exception) { $error = 'Error: ' . $exception->getMessage(); }
    }
    
    elseif ($action === 'delete') {
        try {
            $schoolId = (int)$_POST['school_id'];
            db()->prepare('DELETE FROM school_competencies WHERE school_id=?')->execute([$schoolId]);
            db()->prepare('DELETE FROM school_data WHERE id=?')->execute([$schoolId]);
            $message = 'School deleted successfully.';
        } catch (Throwable $exception) { $error = 'Error: ' . $exception->getMessage(); }
    }
    
    elseif ($action === 'edit_form') {
        $editing = db()->prepare('SELECT * FROM school_data WHERE id=?');
        $editing->execute([(int)$_POST['school_id']]);
        $editing = $editing->fetch();
        $comps = db()->prepare('SELECT competency FROM school_competencies WHERE school_id=?');
        $comps->execute([(int)$_POST['school_id']]);
        $editing['competencies'] = $comps->fetchAll(PDO::FETCH_COLUMN);
    }
}

// Get all active schools, optionally filtered by the admin search.
$schoolSearch = trim((string)($_GET['q'] ?? ''));
$schoolTypeFilter = strtoupper(trim((string)($_GET['type'] ?? '')));
$schoolDomicileFilter = trim((string)($_GET['domicile'] ?? ''));
$schoolSql = 'SELECT s.*, (SELECT COUNT(*) FROM school_competencies WHERE school_id=s.id) as comp_count FROM school_data s WHERE s.is_active=1';
$schoolArgs = [];
if ($schoolSearch !== '') {
    $schoolSql .= ' AND (s.npsn LIKE ? OR s.name LIKE ? OR s.address LIKE ? OR s.domicile LIKE ? OR s.school_type LIKE ? OR s.accreditation LIKE ?)';
    $searchLike = '%' . $schoolSearch . '%';
    $schoolArgs = array_fill(0, 6, $searchLike);
}
if (in_array($schoolTypeFilter, ['SMA', 'SMK'], true)) { $schoolSql .= ' AND s.school_type=?'; $schoolArgs[] = $schoolTypeFilter; }
if ($schoolDomicileFilter !== '') { $schoolSql .= ' AND s.domicile=?'; $schoolArgs[] = $schoolDomicileFilter; }
$schoolSql .= ' ORDER BY s.name';
$schoolQuery = db()->prepare($schoolSql);
$schoolQuery->execute($schoolArgs);
$existingSchools = $schoolQuery->fetchAll();
$title = 'School Management'; require __DIR__ . '/../includes/header.php';
?>
<style>
.school-crud-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 2rem; border-radius: 16px; margin-bottom: 2rem; }
.crud-card { background: white; border-radius: 12px; padding: 1.5rem; box-shadow: 0 2px 15px rgba(0,0,0,0.08); margin-bottom: 1rem; }
.btn-action { padding: 0.4rem 0.8rem; font-size: 0.85rem; border-radius: 8px; }
</style>
<div class="container py-5">
<div class="school-crud-header">
<div class="row align-items-center">
<div class="col-lg-8">
<div class="eyebrow mb-2" style="color: rgba(255,255,255,0.8);">Admin · School Management</div>
<h1 class="mb-0" style="font-size: 2rem; font-weight: 700;">School Database Control</h1>
<p class="mb-0 mt-2" style="opacity: 0.9;">Import from SMKSMA404.csv or manage schools manually</p>
</div>
<div class="col-lg-4 text-lg-end">
<div class="d-flex gap-2 justify-content-lg-end">
<button class="btn btn-light rounded-pill" data-bs-toggle="modal" data-bs-target="#addModal"><i class="bi bi-plus-lg"></i> Add School</button>
</div>
</div>
</div>
</div>

<?php if ($message): ?><div class="alert alert-success"><?= e($message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

<!-- CSV Import Section -->
<div class="crud-card">
<div class="d-flex justify-content-between align-items-center mb-3">
<h4 class="mb-0"><i class="bi bi-file-earmark-spreadsheet"></i> CSV Import (SMKSMA404.csv)</h4>
<span class="badge bg-primary"><?= count($preview) ?> rows detected</span>
</div>
<p class="text-muted mb-3">Import school data from SMKSMA404.csv with automatic field mapping including accreditation.</p>
<form method="post">
<input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
<input type="hidden" name="action" value="import">
<button class="btn btn-primary rounded-pill" onclick="return confirm('Import all schools from SMKSMA404.csv? This will update existing records.')">
<i class="bi bi-database-down"></i> Import Dataset
</button>
</form>
</div>

<!-- Existing Schools Table -->
<div class="crud-card">
<div class="d-flex justify-content-between align-items-center mb-3">
<h4 class="mb-0"><i class="bi bi-building"></i> Existing Schools (<?= count($existingSchools) ?>)</h4>
</div>
<form method="get" class="row g-2 mb-3">
<div class="col-lg-6"><label class="visually-hidden" for="schoolSearch">Search schools</label><input id="schoolSearch" class="form-control" name="q" value="<?= e($schoolSearch) ?>" placeholder="Cari NPSN, nama, alamat, tipe, atau akreditasi"></div>
<div class="col-md-3 col-lg-2"><select class="form-select" name="type" aria-label="Filter school type"><option value="">Semua tipe</option><option value="SMA" <?= $schoolTypeFilter === 'SMA' ? 'selected' : '' ?>>SMA</option><option value="SMK" <?= $schoolTypeFilter === 'SMK' ? 'selected' : '' ?>>SMK</option></select></div>
<div class="col-md-5 col-lg-3"><select class="form-select" name="domicile" aria-label="Filter domicile"><option value="">Semua domisili</option><?php foreach (['Jakarta Pusat','Jakarta Barat','Jakarta Timur','Jakarta Selatan','Jakarta Utara','Kepulauan Seribu'] as $place): ?><option value="<?= e($place) ?>" <?= strcasecmp($schoolDomicileFilter, $place) === 0 ? 'selected' : '' ?>><?= e($place) ?></option><?php endforeach; ?></select></div>
<div class="col-md-4 col-lg-1"><button class="btn btn-dark w-100" title="Cari sekolah"><i class="bi bi-search"></i><span class="visually-hidden">Cari</span></button></div>
</form>
<div class="table-responsive">
<table class="table align-middle">
<thead>
<tr>
<th>NPSN</th>
<th>School Name</th>
<th>Type</th>
<th>Domicile</th>
<th>Accreditation</th>
<th>Competencies</th>
<th>Actions</th>
</tr>
</thead>
<tbody>
<?php foreach ($existingSchools as $school): ?>
<tr>
<td><?= e($school['npsn']) ?></td>
<td><?= e($school['name']) ?></td>
<td><span class="badge bg-<?= $school['school_type'] === 'SMA' ? 'primary' : 'success' ?>"><?= e($school['school_type']) ?></span></td>
<td><?= e($school['domicile']) ?></td>
<td><?= e($school['accreditation'] ?? 'N/A') ?></td>
<td><?= $school['comp_count'] ?></td>
<td>
<form method="post" class="d-inline">
<input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
<input type="hidden" name="action" value="edit_form">
<input type="hidden" name="school_id" value="<?= $school['id'] ?>">
<button class="btn btn-sm btn-outline-primary btn-action"><i class="bi bi-pencil"></i></button>
</form>
<form method="post" class="d-inline" onsubmit="return confirm('Delete this school?')">
<input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
<input type="hidden" name="action" value="delete">
<input type="hidden" name="school_id" value="<?= $school['id'] ?>">
<button class="btn btn-sm btn-outline-danger btn-action"><i class="bi bi-trash"></i></button>
</form>
</td>
</tr>
<?php endforeach; ?>
<?php if (!$existingSchools): ?>
<tr><td colspan="7" class="text-center text-muted">No schools found. Import from CSV or add manually.</td></tr>
<?php endif; ?>
</tbody>
</table>
</div>
</div>

<!-- CSV Preview -->
<div class="crud-card">
<h4 class="mb-3"><i class="bi bi-eye"></i> CSV Preview (First 20 rows)</h4>
<div class="table-responsive">
<table class="table table-sm">
<thead>
<tr>
<th>NPSN</th>
<th>School</th>
<th>Type</th>
<th>Domicile</th>
<th>Avg</th>
<th>Accreditation</th>
<th>Comps</th>
</tr>
</thead>
<tbody>
<?php foreach (array_slice($preview, 0, 20) as $row): ?>
<tr>
<td><?= e($row[0]) ?></td>
<td><?= e($row[1]) ?></td>
<td><?= stripos(trim($row[1]), 'SMA') === 0 ? 'SMA' : 'SMK' ?></td>
<td><?= e($row[13]) ?></td>
<td><?= e($row[14]) ?></td>
<td><?= e($row[19] ?? 'N/A') ?></td>
<td><?= count(array_filter(array_slice($row, 3, 10), fn($v) => trim((string)$v) !== '')) ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</div>
</div>

<!-- Add/Edit Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
<div class="modal-dialog modal-lg">
<div class="modal-content">
<div class="modal-header">
<h5 class="modal-title"><?= $editing ? 'Edit School' : 'Add New School' ?></h5>
<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>
<form method="post">
<div class="modal-body">
<input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
<input type="hidden" name="action" value="<?= $editing ? 'edit' : 'add' ?>">
<?php if ($editing): ?><input type="hidden" name="school_id" value="<?= $editing['id'] ?>"><?php endif; ?>
<div class="row g-3">
<div class="col-md-6">
<label class="form-label">NPSN</label>
<input class="form-control" name="npsn" value="<?= e($editing['npsn'] ?? '') ?>" required>
</div>
<div class="col-md-6">
<label class="form-label">School Type</label>
<select class="form-select" name="school_type" required>
<option value="SMA" <?= ($editing['school_type'] ?? '') === 'SMA' ? 'selected' : '' ?>>SMA</option>
<option value="SMK" <?= ($editing['school_type'] ?? '') === 'SMK' ? 'selected' : '' ?>>SMK</option>
</select>
</div>
<div class="col-12">
<label class="form-label">School Name</label>
<input class="form-control" name="name" value="<?= e($editing['name'] ?? '') ?>" required>
</div>
<div class="col-12">
<label class="form-label">Address</label>
<input class="form-control" name="address" value="<?= e($editing['address'] ?? '') ?>" required>
</div>
<div class="col-md-6">
<label class="form-label">Domicile</label>
<select class="form-select" name="domicile" required>
<?php foreach (['Jakarta Pusat','Jakarta Barat','Jakarta Timur','Jakarta Selatan','Jakarta Utara','Kepulauan Seribu'] as $place): ?>
<option value="<?= e($place) ?>" <?= ($editing['domicile'] ?? '') === $place ? 'selected' : '' ?>><?= e($place) ?></option>
<?php endforeach; ?>
</select>
</div>
<div class="col-md-3">
<label class="form-label">Average Score</label>
<input class="form-control" name="average_score" type="number" step="0.01" value="<?= e($editing['average_score'] ?? '') ?>">
</div>
<div class="col-md-3">
<label class="form-label">Accreditation</label>
<input class="form-control" name="accreditation" value="<?= e($editing['accreditation'] ?? 'A') ?>">
</div>
<div class="col-md-6">
<label class="form-label">Capacity</label>
<input class="form-control" name="capacity" type="number" value="<?= e($editing['capacity'] ?? '') ?>">
</div>
<div class="col-12">
<label class="form-label">Competencies (one per line)</label>
<textarea class="form-control" name="competencies[]" rows="4"><?= implode("\n", $editing['competencies'] ?? []) ?></textarea>
</div>
</div>
</div>
<div class="modal-footer">
<button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Cancel</button>
<button type="submit" class="btn btn-primary rounded-pill"><?= $editing ? 'Update' : 'Add' ?> School</button>
</div>
</form>
</div>
</div>
</div>

<?php if ($editing): ?><script>document.addEventListener('DOMContentLoaded', () => new bootstrap.Modal(document.getElementById('addModal')).show());</script><?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
