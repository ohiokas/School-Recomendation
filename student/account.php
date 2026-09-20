<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_login();

$user = current_user();

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['csrf'] ?? null)) {
    $fullName = trim((string)($_POST['full_name'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));
    $newPassword = (string)($_POST['new_password'] ?? '');
    $confirmPassword = (string)($_POST['confirm_password'] ?? '');

    if ($fullName === '' || $email === '') {
        $error = 'Nama dan email wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } elseif ($newPassword !== '' && strlen($newPassword) < 6) {
        $error = 'Password baru minimal 6 karakter.';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'Konfirmasi password tidak sama.';
    } else {
        try {
            $check = db()->prepare(
                'SELECT id FROM users WHERE email = ? AND id <> ? LIMIT 1'
            );
            $check->execute([$email, $user['id']]);

            if ($check->fetch()) {
                $error = 'Email tersebut sudah digunakan akun lain.';
            } else {
                if ($newPassword !== '') {
                    $stmt = db()->prepare(
                        'UPDATE users
                         SET full_name = ?, email = ?, password_hash = ?
                         WHERE id = ?'
                    );
                    $stmt->execute([
                        $fullName,
                        $email,
                        password_hash($newPassword, PASSWORD_DEFAULT),
                        $user['id']
                    ]);
                } else {
                    $stmt = db()->prepare(
                        'UPDATE users
                         SET full_name = ?, email = ?
                         WHERE id = ?'
                    );
                    $stmt->execute([
                        $fullName,
                        $email,
                        $user['id']
                    ]);
                }

                $message = 'Profile berhasil diperbarui.';
                $user['full_name'] = $fullName;
                $user['email'] = $email;
            }
        } catch (Throwable $exception) {
            $error = 'Gagal memperbarui profile: ' . $exception->getMessage();
        }
    }
}

$title = 'Edit Profile';
require __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="panel">
                <div class="eyebrow mb-2">Account Settings</div>
                <h2 class="mb-2">Edit Profile</h2>
                <p class="text-muted mb-4">
                    Ubah nama, email, atau password akun kamu.
                </p>

                <?php if ($message): ?>
                    <div class="alert alert-success">
                        <?= e($message) ?>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <?= e($error) ?>
                    </div>
                <?php endif; ?>

                <form method="post">
                    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">

                    <div class="mb-3">
                        <label class="form-label">Nama</label>
                        <input
                            type="text"
                            name="full_name"
                            class="form-control"
                            value="<?= e($user['full_name'] ?? '') ?>"
                            required
                        >
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input
                            type="email"
                            name="email"
                            class="form-control"
                            value="<?= e($user['email'] ?? '') ?>"
                            required
                        >
                    </div>

                    <hr class="my-4">

                    <div class="eyebrow mb-2">Change Password</div>
                    <p class="small text-muted">
                        Kosongkan kedua kolom password jika tidak ingin mengganti password.
                    </p>

                    <div class="mb-3">
                        <label class="form-label">Password Baru</label>
                        <input
                            type="password"
                            name="new_password"
                            class="form-control"
                            minlength="6"
                            autocomplete="new-password"
                        >
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Konfirmasi Password Baru</label>
                        <input
                            type="password"
                            name="confirm_password"
                            class="form-control"
                            minlength="6"
                            autocomplete="new-password"
                        >
                    </div>

                    <div class="d-flex gap-2">
                        <a
                            href="<?= e(APP_URL) ?>/dashboard.php"
                            class="btn btn-light rounded-pill"
                        >
                            Cancel
                        </a>

                        <button
                            type="submit"
                            class="btn btn-primary rounded-pill"
                        >
                            <i class="bi bi-check-lg"></i>
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
