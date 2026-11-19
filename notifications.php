<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth_check.php';
require_login();

$userId = (int) current_user()['id'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        flash('error', 'Your session token is invalid. Refresh and try again.');
    } elseif (($_POST['action'] ?? '') === 'mark_all_read') {
        $statement = db()->prepare('UPDATE notifications SET read_at = CURRENT_TIMESTAMP WHERE user_id = ? AND read_at IS NULL');
        $statement->execute([$userId]);
        flash('success', 'All notifications marked as read.');
    }
    redirect(APP_URL . '/notifications.php');
}
$statement = db()->prepare('SELECT id, title, message, read_at, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50');
$statement->execute([$userId]);
$notifications = $statement->fetchAll();
$pageTitle = 'Notifications';
$bodyClass = 'resident-portal-page';
$pageClass = 'resident-portal';
require __DIR__ . '/includes/header.php';
?>
<div class="portal-shell"><?php require __DIR__ . '/includes/portal_sidebar.php'; ?><button class="portal-menu-toggle" type="button" aria-controls="portalSidebar" aria-expanded="false"><svg viewBox="0 0 24 24" fill="none"><path d="M4 7h16M4 12h16M4 17h16"/></svg><span>Menu</span></button><div class="portal-sidebar-backdrop" data-portal-close></div><main class="portal-main"><header class="portal-topbar"><div><strong>SmartWaste portal</strong><small>Updates about your account</small></div><div class="portal-user"><span><?= e(strtoupper(substr(current_user()['full_name'], 0, 1))) ?></span><div><strong><?= e(current_user()['full_name']) ?></strong><small><?= e(ucfirst((string) current_user()['role'])) ?> account</small></div></div></header><div class="portal-content">
<div class="portal-heading"><div><div class="portal-date"><?= e(strtoupper(date('l, j F'))) ?></div><h1>Notifications</h1><p>Updates about your SmartWaste GIS activity.</p></div><form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="mark_all_read"><button class="portal-secondary-action" type="submit">Mark all as read</button></form></div>
<?php if ($message = flash('success')): ?><div class="alert alert-success"><?= e($message) ?></div><?php endif; ?><?php if ($message = flash('error')): ?><div class="alert alert-danger"><?= e($message) ?></div><?php endif; ?>
<section class="portal-panel p-3"><?php foreach ($notifications as $notification): ?><div class="border-bottom py-3 <?= $notification['read_at'] ? '' : 'fw-semibold' ?>"><div class="d-flex justify-content-between"><span><?= e($notification['title']) ?></span><small class="text-muted"><?= e(date('M j, Y H:i', strtotime($notification['created_at']))) ?></small></div><div class="text-muted small mt-1"><?= e($notification['message']) ?></div></div><?php endforeach; ?><?php if (!$notifications): ?><p class="text-muted mb-0">You have no notifications.</p><?php endif; ?></section>
</div></main></div>
<?php require __DIR__ . '/includes/footer.php'; ?>
