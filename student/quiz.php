<?php
require_once __DIR__ . '/../config/database.php';
require_login();
$questions = db()->query('SELECT * FROM quiz_questions WHERE is_active = 1 ORDER BY order_number')->fetchAll();
$indicatorLabels = ['Linguistic'=>'Linguistik','Musical'=>'Musikal','Bodily'=>'Kinestetik','Logical-Mathematical'=>'Logis-Matematis','Spatial-Visualization'=>'Visual-Spasial','Interpersonal'=>'Interpersonal','Intrapersonal'=>'Intrapersonal','Naturalist'=>'Naturalis'];
$user = current_user();
$studentCheck = db()->prepare('SELECT s.id, a.id AS academic_id, o.id AS organization_id, aa.id AS academic_achievement_id, na.id AS non_academic_achievement_id FROM students s LEFT JOIN academic_scores a ON a.student_id = s.id LEFT JOIN organization_scores o ON o.student_id = s.id LEFT JOIN academic_achievements aa ON aa.student_id = s.id LEFT JOIN non_academic_achievements na ON na.student_id = s.id WHERE s.user_id = ?');
$studentCheck->execute([$user['id']]);
$prerequisites = $studentCheck->fetch();
if (!$prerequisites) redirect('student/profile.php');
if (!$prerequisites['academic_id']) redirect('student/academic.php');
if (!$prerequisites['academic_achievement_id'] || !$prerequisites['non_academic_achievement_id']) redirect('student/achievements.php');
if (!$prerequisites['organization_id']) redirect('student/organization.php');
$quizError = ($_GET['error'] ?? '') === 'incomplete' ? 'Jawab semua pertanyaan sebelum mengirimkan.' : '';
$title = 'Kuis minat';
require __DIR__ . '/../includes/header.php';
?>
<div class="container py-5"><div class="row justify-content-center"><div class="col-lg-8"><div class="eyebrow mb-2">Minat dan bakat · <?= count($questions) ?> pertanyaan</div><h1 class="section-title mb-3">Kenali hal yang terasa alami bagimu.</h1><p class="text-muted mb-4">Jawab dengan jujur. Tidak ada profil yang benar atau salah. Jawabanmu dibandingkan dengan pola minat dan karier dalam dataset penelitian utama.</p><?php if ($quizError): ?><div class="alert alert-warning"><?= e($quizError) ?></div><?php endif; ?><form method="post" action="quiz_process.php" class="panel quiz-form"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><?php foreach ($questions as $index => $question): ?><fieldset class="feature quiz-question"><legend class="d-flex justify-content-between gap-3"><span><?= $index + 1 ?>. <?= e($question['question']) ?></span><small class="eyebrow"><?= e($indicatorLabels[$question['indicator']] ?? $question['indicator']) ?></small></legend><div class="quiz-choices" role="radiogroup" aria-label="Jawaban untuk pertanyaan <?= $index + 1 ?>"><?php foreach ([1=>'Sangat tidak setuju',2=>'Tidak setuju',3=>'Netral',4=>'Setuju',5=>'Sangat setuju'] as $value=>$label): ?><label class="quiz-choice"><input type="radio" name="answers[<?= (int)$question['id'] ?>]" value="<?= $value ?>" required><span class="quiz-choice-text"><strong><?= $value ?></strong><span><?= e($label) ?></span></span></label><?php endforeach; ?></div></fieldset><?php endforeach; ?><button class="btn btn-accent rounded-pill px-4 mt-4" type="submit">Hitung profil minat <i class="bi bi-arrow-right"></i></button></form></div></div></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>