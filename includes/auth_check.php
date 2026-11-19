<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';

function require_login(?string $requiredRole = null): void
{
    $user = current_user();
    if (!$user) {
        flash('error', 'Please log in to continue.');
        redirect(APP_URL . '/login.php');
    }

    $statement = db()->prepare('SELECT id, full_name, email, role, is_active FROM users WHERE id = ? LIMIT 1');
    $statement->execute([(int) $user['id']]);
    $liveUser = $statement->fetch();
    if (!$liveUser || !(bool) $liveUser['is_active']) {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        flash('error', 'Please log in to continue.');
        redirect(APP_URL . '/login.php');
    }
    $_SESSION['user'] = $liveUser;
    $user = $liveUser;
    if ($requiredRole !== null && ($user['role'] ?? '') !== $requiredRole) {
        http_response_code(403);
        exit('Access denied.');
    }
}
