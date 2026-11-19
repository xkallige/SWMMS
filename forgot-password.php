<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(APP_URL . '/?auth=forgot');
}

if (!verify_csrf($_POST['csrf_token'] ?? null)) {
    flash('auth_error', 'Your session token is invalid. Refresh and try again.');
    redirect(APP_URL . '/?auth=forgot');
}

$email = strtolower(trim((string) ($_POST['email'] ?? '')));
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    flash('auth_error', 'Enter a valid email address.');
    redirect(APP_URL . '/?auth=forgot');
}

$statement = db()->prepare('SELECT id, full_name, email FROM users WHERE email = ? AND is_active = 1 LIMIT 1');
$statement->execute([$email]);
$user = $statement->fetch();

if (!$user) {
    flash('auth_success', 'If an active account matches that email, a reset link has been sent. Check your inbox and spam folder.');
    redirect(APP_URL . '/?auth=forgot');
}

db()->prepare('DELETE FROM password_reset_tokens WHERE user_id = ? OR expires_at < NOW()')->execute([(int) $user['id']]);
$rawToken = bin2hex(random_bytes(32));
$tokenHash = hash('sha256', $rawToken);
$expiresAt = (new DateTimeImmutable('+1 hour'))->format('Y-m-d H:i:s');
$insert = db()->prepare('INSERT INTO password_reset_tokens (user_id, token_hash, expires_at) VALUES (?, ?, ?)');
$insert->execute([(int) $user['id'], $tokenHash, $expiresAt]);

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$resetUrl = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . APP_URL . '/reset-password.php?token=' . urlencode($rawToken);
$subject = APP_NAME . ' password reset';
$message = "Hello {$user['full_name']},\n\nUse this link to reset your password. It expires in one hour:\n{$resetUrl}\n\nIf you did not request this, you can ignore this message.";
$headers = 'From: SmartWaste GIS <no-reply@smartwaste.local>' . "\r\n" . 'Content-Type: text/plain; charset=UTF-8';

if (!mail($user['email'], $subject, $message, $headers)) {
    error_log('SmartWaste password reset email could not be sent for user ID ' . (int) $user['id']);
    flash('auth_error', 'The reset link could not be sent. Configure SMTP for PHP mail in XAMPP, then try again.');
} else {
    flash('auth_success', 'If an active account matches that email, a reset link has been sent. Check your inbox and spam folder.');
}

redirect(APP_URL . '/?auth=forgot');
