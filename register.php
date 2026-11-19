<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim((string) ($_POST['full_name'] ?? '')); $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? ''); $confirm = (string) ($_POST['password_confirmation'] ?? '');
    if (!verify_csrf($_POST['csrf_token'] ?? null)) $errors[] = 'Your session token is invalid. Refresh and try again.';
    if (mb_strlen($name) < 2) $errors[] = 'Enter your full name.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email address.';
    if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters.';
    if ($password !== $confirm) $errors[] = 'Passwords do not match.';
    if (!$errors) { try { $statement = db()->prepare('INSERT INTO users (full_name, email, password_hash, role) VALUES (?, ?, ?, "resident")'); $statement->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT)]); $_SESSION['registration_complete'] = ['name' => $name, 'email' => $email]; redirect(APP_URL . '/role-selection.php'); } catch (PDOException $exception) { if (($exception->errorInfo[1] ?? null) === 1062) $errors[] = 'That email address is already registered.'; else throw $exception; } }
}
$pageTitle = 'Create your account'; $bodyClass = 'auth-page'; $pageClass = 'auth-shell';
require __DIR__ . '/includes/header.php';
?>
<div class="auth-layout"><div class="auth-form-wrap"><div class="auth-form auth-card auth-card-register"><a class="auth-close" href="<?= APP_URL ?>/" aria-label="Return to homepage">×</a><div class="auth-card-brand"><span class="brand-mark">S</span></div><div class="eyebrow">Resident portal</div><h2>Create your account</h2><p class="auth-subtitle">Register to report local waste problems, add precise locations, and follow progress.</p><?php foreach ($errors as $error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endforeach; ?><form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><label class="form-label" for="full_name">Full name</label><input class="form-control mb-3" id="full_name" name="full_name" autocomplete="name" required value="<?= e($_POST['full_name'] ?? '') ?>"><label class="form-label" for="email">Email address</label><input class="form-control mb-3" id="email" name="email" type="email" autocomplete="email" required value="<?= e($_POST['email'] ?? '') ?>"><label class="form-label" for="password">Password</label><input class="form-control mb-3" id="password" name="password" type="password" autocomplete="new-password" minlength="8" required><label class="form-label" for="password_confirmation">Confirm password</label><input class="form-control mb-3" id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="8" required><button class="btn auth-submit w-100">Create account <span>→</span></button></form><div class="auth-divider"><span>Already registered?</span></div><a class="auth-secondary-action" href="<?= APP_URL ?>/login.php">Sign in securely</a><p class="auth-security">Protected by secure session authentication</p></div></div></div>
<?php require __DIR__ . '/includes/footer.php'; ?>
