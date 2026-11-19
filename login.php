<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';
if (current_user()) redirect(role_home(current_user()['role']));
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Your session token is invalid. Refresh and try again.';
    } else {
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') $errors[] = 'Enter a valid email address and password.';
        else {
            $statement = db()->prepare('SELECT id, full_name, email, password_hash, role, is_active FROM users WHERE email = ? LIMIT 1');
            $statement->execute([$email]);
            $user = $statement->fetch();
            if (!$user || !$user['is_active'] || !password_verify($password, $user['password_hash'])) $errors[] = 'Invalid credentials or inactive account.';
            else { session_regenerate_id(true); unset($user['password_hash']); $_SESSION['user'] = $user; redirect(role_home($user['role'])); }
        }
    }
}
$pageTitle = 'Sign in';
$bodyClass = 'auth-page';
$pageClass = 'auth-shell';
require __DIR__ . '/includes/header.php';
?>
<div class="auth-layout"><div class="auth-form-wrap"><div class="auth-form auth-card"><a class="auth-close" href="<?= APP_URL ?>/" aria-label="Return to homepage">×</a><div class="auth-card-brand"><span class="brand-mark">S</span></div><div class="eyebrow">Resident portal</div><h2>Welcome back</h2><p class="auth-subtitle">Sign in to view reports, track progress, and manage your waste concerns.</p>
<?php foreach ($errors as $error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endforeach; ?><?php if ($message = flash('error')): ?><div class="alert alert-danger"><?= e($message) ?></div><?php endif; ?><?php if ($message = flash('success')): ?><div class="alert alert-success"><?= e($message) ?></div><?php endif; ?>
<form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><label class="form-label" for="email">Email address</label><input class="form-control mb-3" id="email" name="email" type="email" autocomplete="email" required><label class="form-label" for="password">Password</label><input class="form-control mb-3" id="password" name="password" type="password" autocomplete="current-password" required><div class="auth-form-options"><label><input type="checkbox" name="remember"> Remember me</label><a href="<?= APP_URL ?>/?auth=forgot">Forgot password?</a></div><button class="btn auth-submit w-100">Sign in securely <span>→</span></button></form><div class="auth-divider"><span>New to online services?</span></div><a class="auth-secondary-action" href="<?= APP_URL ?>/register.php">Register your resident account</a><p class="auth-security">Protected by secure session authentication</p></div></div></div>
<?php require __DIR__ . '/includes/footer.php'; ?>
