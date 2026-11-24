<?php
require_once __DIR__ . '/../config/database.php'; 
require_once __DIR__ . '/../recommendation/Scoring.php'; 
require_login(); 
if ((current_user()['role'] ?? '') !== 'admin') redirect('dashboard.php');

$message = '';
$error = '';
$editing = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['csrf'] ?? null)) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'delete') {
        $id = (int)($_POST['user_id'] ?? 0);
        if ($id > 0 && $id !== (int)current_user()['id']) {
            try {
                db()->prepare("DELETE FROM users WHERE id = ? AND role = 'student'")->execute([$id]);
                $message = 'Student deleted successfully.';
            } catch (Throwable $exception) {
                $error = 'Error deleting student: ' . $exception->getMessage();
            }
        }
    }
    
    elseif ($action === 'edit') {
        try {
            $userId = (int)$_POST['user_id'];
            $fullName = trim($_POST['full_name']);
            $email = trim($_POST['email']);
            $domicile = trim($_POST['domicile']);
            $previousSchool = trim($_POST['previous_school']);
            $educationPreference = $_POST['education_preference'];
            $admissionPath = $_POST['admission_path'] ?? null;
            
            db()->prepare('UPDATE users SET full_name=?, email=? WHERE id=?')->execute([$fullName, $email, $userId]);
            db()->prepare('UPDATE students SET domicile=?, previous_school=?, education_preference=?, admission_path=? WHERE user_id=?')->execute([$domicile, $previousSchool, $educationPreference, $admissionPath, $userId]);
            
            $message = 'Student updated successfully.';
        } catch (Throwable $exception) {
            $error = 'Error updating student: ' . $exception->getMessage();
        }
    }
    
    elseif ($action === 'edit_form') {
        $editing = db()->prepare('SELECT u.*, s.* FROM users u LEFT JOIN students s ON s.user_id=u.id WHERE u.id=?');
        $editing->execute([(int)$_POST['user_id']]);
        $editing = $editing->fetch();
    }
}

$students = db()->query("SELECT u.id,u.full_name,u.email,u.created_at,s.domicile,s.previous_school,s.education_preference,s.admission_path FROM users u LEFT JOIN students s ON s.user_id=u.id WHERE u.role='student' ORDER BY u.created_at DESC")->fetchAll(); 
$title='Student Management'; 
require __DIR__.'/../includes/header.php';
?>
<style>
.student-crud-header { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; padding: 2rem; border-radius: 16px; margin-bottom: 2rem; }
.crud-card { background: white; border-radius: 12px; padding: 1.5rem; box-shadow: 0 2px 15px rgba(0,0,0,0.08); margin-bottom: 1rem; }
.btn-action { padding: 0.4rem 0.8rem; font-size: 0.85rem; border-radius: 8px; }
</style>
<div class="container py-5">
<div class="student-crud-header">
<div class="row align-items-center">
<div class="col-lg-8">
<div class="eyebrow mb-2" style="color: rgba(255,255,255,0.8);">Admin · Student Management</div>
<h1 class="mb-0" style="font-size: 2rem; font-weight: 700;">Student Database Control</h1>
<p class="mb-0 mt-2" style="opacity: 0.9;">View and manage student accounts and their profiles</p>
</div>
<div class="col-lg-4 text-lg-end">
<div class="d-flex gap-2 justify-content-lg-end">
<span class="badge bg-light text-dark"><?= count($students) ?> Total Students</span>
</div>
</div>
</div>
</div>

<?php if ($message): ?><div class="alert alert-success"><?= e($message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

<div class="crud-card">
<div class="d-flex justify-content-between align-items-center mb-3">
<h4 class="mb-0"><i class="bi bi-people"></i> Registered Students (<?= count($students) ?>)</h4>
</div>
<div class="table-responsive">
<table class="table align-middle">
<thead>
<tr>
<th>Name</th>
<th>Email</th>
<th>Domicile</th>
<th>Education</th>
<th>Admission Path</th>
<th>Previous School</th>
<th>Registered</th>
<th>Actions</th>
</tr>
</thead>
<tbody>
<?php foreach ($students as $student): ?>
<tr>
<td>
<strong><?= e($student['full_name']) ?></strong>
</td>
<td><?= e($student['email']) ?></td>
<td><?= e($student['domicile'] ?? 'Not set') ?></td>
<td><span class="badge bg-<?= $student['education_preference'] === 'sma' ? 'primary' : ($student['education_preference'] === 'smk' ? 'success' : 'secondary') ?>"><?= e(ucfirst($student['education_preference'] ?? 'undecided')) ?></span></td>
<td><?php $sRoute = normalizeAdmissionPath((string)($student['admission_path'] ?? '')); echo e(ucfirst(str_replace('_', ' ', $sRoute !== '' ? $sRoute : 'Not set'))); ?></td>
<td><?= e($student['previous_school'] ?? '-') ?></td>
<td><?= e($student['created_at']) ?></td>
<td>
<form method="post" class="d-inline">
<input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
<input type="hidden" name="action" value="edit_form">
<input type="hidden" name="user_id" value="<?= $student['id'] ?>">
<button class="btn btn-sm btn-outline-primary btn-action"><i class="bi bi-pencil"></i></button>
</form>
<form method="post" class="d-inline" onsubmit="return confirm('Delete this student and all their data?')">
<input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
<input type="hidden" name="action" value="delete">
<input type="hidden" name="user_id" value="<?= $student['id'] ?>">
<button class="btn btn-sm btn-outline-danger btn-action"><i class="bi bi-trash"></i></button>
</form>
</td>
</tr>
<?php endforeach; ?>
<?php if (!$students): ?>
<tr><td colspan="8" class="text-center text-muted">No student accounts yet.</td></tr>
<?php endif; ?>
</tbody>
</table>
</div>
</div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
<div class="modal-dialog modal-lg">
<div class="modal-content">
<div class="modal-header">
<h5 class="modal-title">Edit Student Profile</h5>
<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>
<form method="post">
<div class="modal-body">
<input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
<input type="hidden" name="action" value="edit">
<input type="hidden" name="user_id" value="<?= $editing['id'] ?>">
<div class="row g-3">
<div class="col-md-6">
<label class="form-label">Full Name</label>
<input class="form-control" name="full_name" value="<?= e($editing['full_name'] ?? '') ?>" required>
</div>
<div class="col-md-6">
<label class="form-label">Email</label>
<input class="form-control" name="email" value="<?= e($editing['email'] ?? '') ?>" required>
</div>
<div class="col-md-6">
<label class="form-label">Domicile</label>
<select class="form-select" name="domicile">
<option value="">Not set</option>
<?php foreach (['Jakarta Pusat','Jakarta Barat','Jakarta Timur','Jakarta Selatan','Jakarta Utara','Kepulauan Seribu'] as $place): ?>
<option value="<?= e($place) ?>" <?= ($editing['domicile'] ?? '') === $place ? 'selected' : '' ?>><?= e($place) ?></option>
<?php endforeach; ?>
</select>
</div>
<div class="col-md-6">
<label class="form-label">Previous School</label>
<input class="form-control" name="previous_school" value="<?= e($editing['previous_school'] ?? '') ?>">
</div>
<div class="col-md-6">
<label class="form-label">Education Preference</label>
<select class="form-select" name="education_preference">
<option value="undecided" <?= ($editing['education_preference'] ?? '') === 'undecided' ? 'selected' : '' ?>>Undecided</option>
<option value="sma" <?= ($editing['education_preference'] ?? '') === 'sma' ? 'selected' : '' ?>>SMA</option>
<option value="smk" <?= ($editing['education_preference'] ?? '') === 'smk' ? 'selected' : '' ?>>SMK</option>
</select>
</div>
<div class="col-md-6">
<label class="form-label">Admission Path</label>
<select class="form-select" name="admission_path">
<option value="">Not set</option>
<?php 
$currentEditPath = normalizeAdmissionPath((string)($editing['admission_path'] ?? ''));
$adminPaths = [
    'prestasi_akademik_sma' => 'Prestasi Akademik SMA',
    'prestasi_akademik_smk' => 'Prestasi Akademik SMK',
    'afirmasi_sma' => 'Afirmasi SMA',
    'zonasi_sma' => 'Zonasi SMA',
    'tahap_kedua_sma' => 'Tahap Kedua SMA',
    'afirmasi_tahap_kedua_smk' => 'Afirmasi & Tahap Kedua SMK',
    'prestasi_non_akademik' => 'Prestasi Non-Akademik SMA/SMK',
];
foreach ($adminPaths as $pCode => $pName): ?>
<option value="<?= e($pCode) ?>" <?= $currentEditPath === $pCode ? 'selected' : '' ?>><?= e($pName) ?></option>
<?php endforeach; ?>
</select>
</div>
</div>
</div>
<div class="modal-footer">
<button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Cancel</button>
<button type="submit" class="btn btn-primary rounded-pill">Update Student</button>
</div>
</form>
</div>
</div>
</div>

<?php if ($editing): ?><script>document.addEventListener('DOMContentLoaded', () => new bootstrap.Modal(document.getElementById('editModal')).show());</script><?php endif; ?>
<?php require __DIR__.'/../includes/footer.php'; ?>
