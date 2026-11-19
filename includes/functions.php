<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

function e(mixed $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function redirect(string $location): never
{
    header('Location: ' . $location);
    exit;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf(?string $token): bool
{
    return is_string($token) && hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

function flash(string $key, ?string $message = null): ?string
{
    if ($message !== null) {
        $_SESSION['flash'][$key] = $message;
        return null;
    }
    $value = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $value;
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function unread_notification_count(int $userId): int
{
    $statement = db()->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND read_at IS NULL');
    $statement->execute([$userId]);
    return (int) $statement->fetchColumn();
}

function report_status_label(string $status): string
{
    return ucwords(str_replace('_', ' ', $status));
}

function report_status_class(string $status): string
{
    return match ($status) {
        'resolved' => 'success',
        'rejected', 'duplicate' => 'danger',
        'in_progress', 'assigned' => 'primary',
        'under_review' => 'warning',
        default => 'secondary',
    };
}

function role_home(string $role): string
{
    return match ($role) {
        'admin' => APP_URL . '/admin/dashboard.php',
        'officer' => APP_URL . '/officer/dashboard.php',
        default => APP_URL . '/resident/dashboard.php',
    };
}
