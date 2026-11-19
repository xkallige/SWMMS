<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';

$token = trim((string) ($_GET['token'] ?? $_POST['token'] ?? ''));
$tokenHash = $token !== '' ? hash('sha256', $token) : '';
$errors = [];
$validToken = false;

if ($tokenHash !== '') {
    $statement = db()->prepare('SELECT id FROM password_reset_tokens WHERE token_hash = ? AND expires_at > NOW() LIMIT 1');
    $statement->execute([$tokenHash]);
    $validToken = (bool) $statement->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = (string) ($_POST['password'] ?? '');
    $confirmation = (string) ($_POST['password_confirmation'] ?? '');

    if (!verify_csrf($_POST['csrf_token'] ?? null)) $errors[] = 'Your session token is invalid. Refresh and try again.';
    if (!$validToken) $errors[] = 'This reset link is invalid or has expired. Request a new one.';
    if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters.';
    if ($password !== $confirmation) $errors[] = 'Passwords do not match.';

    if (!$errors) {
        $connection = db();
        $connection->beginTransaction();
        try {
            $lookup = $connection->prepare('SELECT user_id FROM password_reset_tokens WHERE token_hash = ? AND expires_at > NOW() LIMIT 1 FOR UPDATE');
            $lookup->execute([$tokenHash]);
            $reset = $lookup->fetch();
            if (!$reset) {
                throw new RuntimeException('The password reset token is no longer valid.');
            }
            $update = $connection->prepare('UPDATE users SET password_hash = ? WHERE id = ? AND is_active = 1');
            $update->execute([password_hash($password, PASSWORD_DEFAULT), (int) $reset['user_id']]);
            $delete = $connection->prepare('DELETE FROM password_reset_tokens WHERE user_id = ?');
            $delete->execute([(int) $reset['user_id']]);
            $connection->commit();
            flash('success', 'Your password has been updated. You can now sign in.');
            redirect(APP_URL . '/?auth=login');
        } catch (Throwable $exception) {
            if ($connection->inTransaction()) $connection->rollBack();
            throw $exception;
        }
    }
}

$pageTitle = 'Choose a new password';
$bodyClass = 'auth-page';
$pageClass = 'auth-shell';
require __DIR__ . '/includes/header.php';
?>
<div class="auth-layout"><div class="auth-form-wrap"><div class="auth-form auth-card">
    <a class="auth-close" href="<?= APP_URL ?>/" aria-label="Return to homepage">×</a>
    <div class="auth-card-brand"><span class="brand-mark">S</span></div>
    <div class="eyebrow">Resident portal</div>
    <h2>Choose a new password</h2>
    <p class="auth-subtitle">Use at least eight characters for your new SmartWaste GIS password.</p>
    <?php foreach ($errors as $error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endforeach; ?>
    <?php if (!$validToken): ?>
        <div class="alert alert-warning">This reset link is invalid or has expired.</div>
        <a class="auth-secondary-action" href="<?= APP_URL ?>/forgot-password.php">Request a new link <span aria-hidden="true">→</span></a>
    <?php else: ?>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="token" value="<?= e($token) ?>">
            <label class="form-label" for="password">New password</label>
            <input class="form-control mb-3" id="password" name="password" type="password" autocomplete="new-password" minlength="8" required>
            <label class="form-label" for="password_confirmation">Confirm new password</label>
            <input class="form-control mb-3" id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="8" required>
            <button class="btn auth-submit w-100" type="submit">Update password <span>→</span></button>
        </form>
    <?php endif; ?>
    <p class="auth-security">Reset links expire after one hour</p>
</div></div></div>
<?php require __DIR__ . '/includes/footer.php'; ?>
