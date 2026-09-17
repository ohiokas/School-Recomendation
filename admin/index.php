<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php'; require_login(); if ((current_user()['role'] ?? '') !== 'admin') redirect('dashboard.php');
$counts = [];
foreach (['students'=>"SELECT COUNT(*) FROM users WHERE role='student'",'schools'=>'SELECT COUNT(*) FROM school_data WHERE is_active=1','majors'=>'SELECT COUNT(*) FROM majors WHERE is_active=1','datasets'=>'SELECT COUNT(*) FROM datasets','predictions'=>'SELECT COUNT(*) FROM recommendations'] as $key=>$sql) { try { $counts[$key] = (int)db()->query($sql)->fetchColumn(); } catch (Throwable $exception) { $counts[$key] = 0; } }
$activeModel = db()->query("SELECT version_name,metrics FROM ml_models WHERE status='active' ORDER BY created_at DESC LIMIT 1")->fetch(); $title = 'Admin overview'; require __DIR__ . '/../includes/header.php';
?>
<style>
.admin-dashboard { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 3rem 0; border-radius: 20px; margin-bottom: 2rem; }
.stat-card { background: white; border-radius: 16px; padding: 1.5rem; box-shadow: 0 4px 20px rgba(0,0,0,0.08); transition: transform 0.3s, box-shadow 0.3s; }
.stat-card:hover { transform: translateY(-5px); box-shadow: 0 8px 30px rgba(0,0,0,0.12); }
.stat-card .icon { width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin-bottom: 1rem; }
.stat-card .count { font-size: 2.5rem; font-weight: 700; color: #1a1a2e; }
.stat-card .label { color: #6c757d; font-size: 0.9rem; font-weight: 500; }
.admin-nav-card { background: white; border-radius: 16px; padding: 1.5rem; box-shadow: 0 4px 15px rgba(0,0,0,0.06); }
.admin-nav-card .nav-link { color: #1a1a2e; text-decoration: none; padding: 0.75rem 1rem; border-radius: 10px; transition: all 0.2s; display: flex; align-items: center; gap: 0.75rem; }
.admin-nav-card .nav-link:hover { background: #f8f9fa; color: #667eea; }
.admin-nav-card .nav-link i { font-size: 1.25rem; }
.model-status { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); border-radius: 16px; padding: 2rem; color: white; }
</style>
<div class="container py-5">
<div class="admin-dashboard">
<div class="row align-items-center">
<div class="col-lg-8">
<div class="eyebrow mb-2" style="color: rgba(255,255,255,0.8);">Welcome back, Admin</div>
<h1 class="mb-0" style="font-size: 2.5rem; font-weight: 700;">System Overview</h1>
<p class="mb-0 mt-2" style="opacity: 0.9;">Manage your school recommendation platform efficiently</p>
</div>
<div class="col-lg-4 text-lg-end">
<div class="d-flex align-items-center gap-3 justify-content-lg-end">
<div class="text-end">
<div class="small" style="opacity: 0.8;">Active Model</div>
<strong><?= e($activeModel['version_name'] ?? 'Not trained') ?></strong>
</div>
<div style="width: 60px; height: 60px; background: rgba(255,255,255,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
<i class="bi bi-cpu" style="font-size: 1.5rem;"></i>
</div>
</div>
</div>
</div>
</div>

<div class="row g-4 mb-4">
<div class="col-md-6 col-lg-3">
<div class="stat-card">
<div class="icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
<i class="bi bi-people"></i>
</div>
<div class="count"><?= $counts['students'] ?></div>
<div class="label">Total Students</div>
</div>
</div>
<div class="col-md-6 col-lg-3">
<div class="stat-card">
<div class="icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white;">
<i class="bi bi-building"></i>
</div>
<div class="count"><?= $counts['schools'] ?></div>
<div class="label">Registered Schools</div>
</div>
</div>
<div class="col-md-6 col-lg-3">
<div class="stat-card">
<div class="icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white;">
<i class="bi bi-book"></i>
</div>
<div class="count"><?= $counts['majors'] ?></div>
<div class="label">Available Majors</div>
</div>
</div>
<div class="col-md-6 col-lg-3">
<div class="stat-card">
<div class="icon" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); color: white;">
<i class="bi bi-graph-up"></i>
</div>
<div class="count"><?= $counts['predictions'] ?></div>
<div class="label">Recommendations</div>
</div>
</div>
</div>

<div class="row g-4">
<div class="col-lg-8">
<div class="admin-nav-card">
<h3 class="mb-4">Quick Actions</h3>
<div class="row g-3">
<div class="col-md-6">
<a class="nav-link" href="<?= APP_URL ?>/admin/schools.php">
<i class="bi bi-building"></i>
<div>
<strong>School Management</strong>
<div class="small text-muted">Manage school data and competencies</div>
</div>
</a>
</div>
<div class="col-md-6">
<a class="nav-link" href="<?= APP_URL ?>/admin/students.php">
<i class="bi bi-people"></i>
<div>
<strong>Student Management</strong>
<div class="small text-muted">View and manage student accounts</div>
</div>
</a>
</div>
<div class="col-md-6">
<a class="nav-link" href="<?= APP_URL ?>/admin/upload_dataset.php">
<i class="bi bi-upload"></i>
<div>
<strong>Upload Dataset</strong>
<div class="small text-muted">Import new school data</div>
</div>
</a>
</div>
<div class="col-md-6">
<a class="nav-link" href="<?= APP_URL ?>/admin/training.php">
<i class="bi bi-gear"></i>
<div>
<strong>Train Model</strong>
<div class="small text-muted">Train ML recommendation model</div>
</div>
</a>
</div>
<div class="col-md-6">
<a class="nav-link" href="<?= APP_URL ?>/admin/majors.php">
<i class="bi bi-book"></i>
<div>
<strong>Major Management</strong>
<div class="small text-muted">Configure available majors</div>
</div>
</a>
</div>
<div class="col-md-6">
<a class="nav-link" href="<?= APP_URL ?>/admin/passion.php">
<i class="bi bi-heart"></i>
<div>
<strong>Passion Library</strong>
<div class="small text-muted">Manage passion professions</div>
</div>
</a>
</div>
<div class="col-md-6">
<a class="nav-link" href="<?= APP_URL ?>/admin/quiz.php">
<i class="bi bi-question-circle"></i>
<div>
<strong>Quiz Management</strong>
<div class="small text-muted">Configure assessment questions</div>
</div>
</a>
</div>
<div class="col-md-6">
<a class="nav-link" href="<?= APP_URL ?>/admin/settings.php">
<i class="bi bi-gear"></i>
<div>
<strong>System Settings</strong>
<div class="small text-muted">Configure system parameters</div>
</div>
</a>
</div>
</div>
</div>
</div>
<div class="col-lg-4">
<div class="model-status">
<div class="d-flex align-items-center gap-3 mb-3">
<i class="bi bi-cpu" style="font-size: 2rem;"></i>
<div>
<div class="small" style="opacity: 0.9;">Active ML Model</div>
<strong><?= e($activeModel['version_name'] ?? 'Not trained') ?></strong>
</div>
</div>
<div class="mb-3">
<div class="small" style="opacity: 0.9;">Model Accuracy</div>
<strong style="font-size: 1.5rem;"><?= isset($activeModel['metrics']) ? round((float)(json_decode($activeModel['metrics'], true)['accuracy'] ?? 0) * 100, 2) . '%' : 'n/a' ?></strong>
</div>
<a href="<?= APP_URL ?>/admin/models.php" class="btn btn-light rounded-pill w-100">View All Models</a>
</div>
</div>
</div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
