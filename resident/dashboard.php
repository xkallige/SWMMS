<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/dashboard.php';
require_login('resident');
$user = current_user();
$counts = report_counts_for_user((int) $user['id']);
$recentReports = recent_reports_for_user((int) $user['id']);
$unreadCount = unread_notification_count((int) $user['id']);
$pageTitle = 'Resident dashboard';
$bodyClass = 'resident-portal-page';
$pageClass = 'resident-portal';
require __DIR__ . '/../includes/header.php';
?>
<div class="portal-shell">
    <?php require __DIR__ . '/../includes/portal_sidebar.php'; ?>
    <button class="portal-menu-toggle" type="button" aria-controls="portalSidebar" aria-expanded="false"><svg viewBox="0 0 24 24" fill="none"><path d="M4 7h16M4 12h16M4 17h16"/></svg><span>Menu</span></button>
    <div class="portal-sidebar-backdrop" data-portal-close></div>
    <main class="portal-main">
        <header class="portal-topbar"><div><strong>Resident portal</strong><small>Community reporting and progress tracking</small></div><div class="portal-user"><span><?= e(strtoupper(substr($user['full_name'], 0, 1))) ?></span><div><strong><?= e($user['full_name']) ?></strong><small>Resident account</small></div></div></header>
        <div class="portal-content">
            <div class="portal-heading"><div><div class="portal-date"><?= e(strtoupper(date('l, j F'))) ?></div><h1>Good day, <?= e((string) preg_split('/\s+/', $user['full_name'])[0]) ?>.</h1><p>Here is what is happening with your community reports.</p></div><a class="portal-primary-action" href="<?= APP_URL ?>/resident/report-waste.php"><span>+</span> New report <b>→</b></a></div>
            <div class="portal-notice"><span class="portal-notice-icon">i</span><div><strong>Make your street count</strong><small>Report waste concerns with a location so response teams can act faster.</small></div><a href="<?= APP_URL ?>/resident/report-waste.php">Report now <span>→</span></a></div>
            <div class="portal-stat-grid">
                <div class="portal-stat-card"><span class="portal-stat-icon green"><svg viewBox="0 0 24 24" fill="none"><path d="M5 4h14v16H5zM8 8h8M8 12h6M8 16h4"/></svg></span><small>Total reports</small><strong><?= e($counts['total']) ?></strong><em>All submissions</em></div>
                <div class="portal-stat-card"><span class="portal-stat-icon gold"><svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="8"/><path d="M12 8v5l3 2"/></svg></span><small>Pending reports</small><strong><?= e($counts['pending']) ?></strong><em>Awaiting review</em></div>
                <div class="portal-stat-card"><span class="portal-stat-icon blue"><svg viewBox="0 0 24 24" fill="none"><path d="M4 5h16v11H4z"/><path d="M8 20h8M12 16v4M8 9h8"/></svg></span><small>Under review</small><strong><?= e($counts['under_review']) ?></strong><em>Being coordinated</em></div>
                <div class="portal-stat-card"><span class="portal-stat-icon mint"><svg viewBox="0 0 24 24" fill="none"><path d="m5 12 4 4L19 6"/></svg></span><small>Resolved</small><strong><?= e($counts['resolved']) ?></strong><em>Completed reports</em></div>
            </div>
            <div class="portal-dashboard-grid">
                <section class="portal-panel"><div class="portal-panel-heading"><div><h2>Recent report activity</h2><p>Your latest community submissions</p></div><a href="<?= APP_URL ?>/resident/my-reports.php">View all <span>→</span></a></div><div class="portal-report-list"><?php foreach ($recentReports as $report): ?><a class="portal-report-row" href="<?= APP_URL ?>/resident/my-reports.php"><span class="portal-report-marker"></span><span><strong>Report #<?= e($report['id']) ?></strong><small><?= e($report['category']) ?> · <?= e(date('M j, Y', strtotime($report['created_at']))) ?></small></span><span class="badge text-bg-<?= e(report_status_class($report['status'])) ?>"><?= e(report_status_label($report['status'])) ?></span><b>→</b></a><?php endforeach; ?><?php if (!$recentReports): ?><div class="portal-empty"><strong>No reports yet</strong><span>Submit your first waste concern to start tracking progress.</span><a href="<?= APP_URL ?>/resident/report-waste.php">Submit a report →</a></div><?php endif; ?></div></section>
                <section class="portal-panel"><div class="portal-panel-heading"><div><h2>Quick actions</h2><p>Common account tasks</p></div></div><div class="portal-action-list"><a href="<?= APP_URL ?>/resident/report-waste.php"><span class="portal-action-icon green"><svg viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12h14"/></svg></span><span><strong>Report a waste concern</strong><small>Add a location and description</small></span><b>→</b></a><a href="<?= APP_URL ?>/resident/my-reports.php"><span class="portal-action-icon blue"><svg viewBox="0 0 24 24" fill="none"><path d="M6 4h12v16H6zM9 8h6M9 12h6M9 16h4"/></svg></span><span><strong>View my reports</strong><small>Track submissions and status</small></span><b>→</b></a><a href="<?= APP_URL ?>/notifications.php"><span class="portal-action-icon gold"><svg viewBox="0 0 24 24" fill="none"><path d="M18 9a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9ZM10 21h4"/></svg></span><span><strong>Check notifications</strong><small><?= $unreadCount ? $unreadCount . ' unread updates' : 'No unread updates' ?></small></span><b>→</b></a></div></section>
            </div>
        </div>
    </main>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
