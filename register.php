<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/session.php';
if (current_user()) redirect('dashboard.php');
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf'] ?? null)) $error = 'Your form session expired. Please try again.';
    elseif (!filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL)) $error = 'Enter a valid email address.';
    elseif (strlen($_POST['password'] ?? '') < 8) $error = 'Password must be at least 8 characters.';
    elseif ($_POST['password'] !== $_POST['password_confirmation']) $error = 'Passwords do not match.';
    else { try { $stmt = db()->prepare('INSERT INTO users (full_name,email,password_hash,role) VALUES (?,?,?,?)'); $stmt->execute([trim($_POST['full_name']), strtolower(trim($_POST['email'])), password_hash($_POST['password'], PASSWORD_DEFAULT), 'student']); $_SESSION['user'] = ['id'=>(int)db()->lastInsertId(),'full_name'=>trim($_POST['full_name']),'role'=>'student']; session_regenerate_id(true); redirect('dashboard.php'); } catch (PDOException $exception) { $error = $exception->getCode() === '23000' ? 'That email is already registered.' : 'Unable to create your account right now.'; } }
}
$title='Create your profile'; require __DIR__ . '/includes/header.php'; ?>
<div class="auth-wrap"><div class="auth-card"><div class="eyebrow mb-2">First, hello</div><h1 class="h2 mb-4">Create your SMAKITA profile.</h1><?php if($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?><form method="post"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><label class="form-label">Full name</label><input class="form-control mb-3" name="full_name" required><label class="form-label">Email</label><input class="form-control mb-3" name="email" type="email" required><label class="form-label">Password</label><input class="form-control mb-3" name="password" type="password" minlength="8" required><label class="form-label">Confirm password</label><input class="form-control mb-4" name="password_confirmation" type="password" required><button class="btn btn-accent w-100 py-3 rounded-pill">Create account <i class="bi bi-arrow-right"></i></button></form><p class="text-muted mt-4 mb-0">Already registered? <a href="<?= APP_URL ?>/login.php">Log in</a></p></div></div><?php require __DIR__ . '/includes/footer.php'; ?>