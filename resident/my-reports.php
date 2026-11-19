<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth_check.php';
require_login('resident');
$statement = db()->prepare(
    'SELECT wr.id, wc.name AS category, wr.status, wr.description, wr.created_at
     FROM waste_reports wr INNER JOIN waste_categories wc ON wc.id = wr.category_id
     WHERE wr.reported_by = ? ORDER BY wr.created_at DESC'
);
$statement->execute([current_user()['id']]);
$reports = $statement->fetchAll();
$pageTitle = 'My reports';
$bodyClass = 'resident-portal-page';
$pageClass = 'resident-portal';
require __DIR__ . '/../includes/header.php';
?>
<div class="portal-shell"><?php require __DIR__ . '/../includes/portal_sidebar.php'; ?><button class="portal-menu-toggle" type="button" aria-controls="portalSidebar" aria-expanded="false"><svg viewBox="0 0 24 24" fill="none"><path d="M4 7h16M4 12h16M4 17h16"/></svg><span>Menu</span></button><div class="portal-sidebar-backdrop" data-portal-close></div><main class="portal-main"><header class="portal-topbar"><div><strong>Resident portal</strong><small>Community reporting and progress tracking</small></div><div class="portal-user"><span><?= e(strtoupper(substr(current_user()['full_name'], 0, 1))) ?></span><div><strong><?= e(current_user()['full_name']) ?></strong><small>Resident account</small></div></div></header><div class="portal-content">
<div class="portal-heading"><div><div class="portal-date"><?= e(strtoupper(date('l, j F'))) ?></div><h1>My reports</h1><p>Track the waste problems you have submitted.</p></div><a class="portal-primary-action" href="<?= APP_URL ?>/resident/report-waste.php"><span>+</span> New report <b>→</b></a></div>
<?php if ($message = flash('success')): ?><div class="alert alert-success"><?= e($message) ?></div><?php endif; ?>
<section class="portal-panel"><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Report</th><th>Category</th><th>Description</th><th>Status</th><th>Submitted</th></tr></thead><tbody><?php foreach ($reports as $report): ?><tr><td>#<?= e($report['id']) ?></td><td><?= e($report['category']) ?></td><td><?= e(mb_strimwidth($report['description'], 0, 70, '...')) ?></td><td><span class="badge text-bg-<?= e(report_status_class($report['status'])) ?>"><?= e(report_status_label($report['status'])) ?></span></td><td><?= e(date('M j, Y', strtotime($report['created_at']))) ?></td></tr><?php endforeach; ?><?php if (!$reports): ?><tr><td colspan="5" class="text-muted">You have not submitted any reports yet.</td></tr><?php endif; ?></tbody></table></div></section>
</div></main></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
