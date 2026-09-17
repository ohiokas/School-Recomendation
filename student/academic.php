<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php'; require_once __DIR__ . '/../recommendation/Scoring.php'; require_login();
$user = current_user();
$studentQuery = db()->prepare('SELECT s.id AS student_id, s.domicile, s.previous_school, a.science, a.social_studies, a.indonesian, a.english, a.mathematics, a.civics, a.academic_average FROM students s LEFT JOIN academic_scores a ON a.student_id = s.id WHERE s.user_id = ?');
$studentQuery->execute([$user['id']]);
$data = $studentQuery->fetch();
if (!$data) {
    try {
        $userExists = db()->prepare('SELECT id FROM users WHERE id = ?');
        $userExists->execute([$user['id']]);
        if (!$userExists->fetchColumn()) {
            $_SESSION = [];
            redirect('login.php');
        }
        $createStudent = db()->prepare('INSERT INTO students(user_id) VALUES(?)');
        $createStudent->execute([$user['id']]);
        $studentQuery->execute([$user['id']]);
        $data = $studentQuery->fetch();
    } catch (Throwable $exception) { error_log('SMAKITA academic student initialization failed: ' . $exception->getMessage()); $data = null; }
}
if (!$data || empty($data['student_id'])) { $error = 'Profil akunmu belum terhubung ke basis data. Buka halaman profil dan simpan kembali.'; $data = ['student_id'=>null]; } else { $error = ''; }
$profileNotice = empty($data['domicile']) || empty($data['previous_school']);
$fields = ['science','social_studies','indonesian','english','mathematics','civics'];
$labels = ['science'=>'Ilmu Pengetahuan Alam','social_studies'=>'Ilmu Pengetahuan Sosial','indonesian'=>'Bahasa Indonesia','english'=>'Bahasa Inggris','mathematics'=>'Matematika','civics'=>'Pendidikan Pancasila'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf'] ?? null)) $error = 'Sesi formulir telah berakhir. Silakan coba lagi.';
    else {
        try {
            $values = []; foreach ($fields as $field) $values[$field] = max(0, min(100, (float)($_POST[$field] ?? 0)));
            $average = academicAverage($values);
            $save = db()->prepare('INSERT INTO academic_scores(student_id,science,social_studies,indonesian,english,mathematics,civics,academic_average) VALUES(?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE science=VALUES(science),social_studies=VALUES(social_studies),indonesian=VALUES(indonesian),english=VALUES(english),mathematics=VALUES(mathematics),civics=VALUES(civics),academic_average=VALUES(academic_average)');
            $save->execute([$data['student_id'], ...array_values($values), $average]);
            redirect('dashboard.php');
        } catch (Throwable $exception) { error_log('SMAKITA academic save failed: ' . $exception->getMessage()); $error = 'Nilai akademik tidak dapat disimpan. Periksa koneksi basis data dan coba lagi.'; }
    }
}
 $title = 'Nilai akademik'; require __DIR__ . '/../includes/header.php';
?><div class="container py-5"><div class="row justify-content-center"><div class="col-lg-7"><div class="eyebrow mb-2">Nilai akademik</div><h1 class="section-title mb-4">Masukkan nilai akademikmu.</h1><?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php elseif ($profileNotice): ?><div class="alert alert-info">Nilai akademik dapat diisi sekarang. Lengkapi profil pribadi sebelum melanjutkan ke prestasi dan kuis minat.</div><?php endif; ?><form method="post" class="panel"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><?php foreach ($labels as $field=>$label): ?><label class="form-label mt-2"><?= e($label) ?></label><input class="form-control mb-2" type="number" min="0" max="100" step="0.01" name="<?= e($field) ?>" value="<?= e((string)($data[$field] ?? '')) ?>" required><?php endforeach; ?><button class="btn btn-accent rounded-pill px-4 mt-3" <?= empty($data['student_id']) ? 'disabled' : '' ?>>Simpan nilai <i class="bi bi-arrow-right"></i></button></form></div></div></div><?php require __DIR__ . '/../includes/footer.php'; ?>