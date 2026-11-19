<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth_check.php';
require_login('admin');
$statement = db()->query(
    'SELECT cr.collection_date, cr.quantity, cr.quantity_unit, cr.remarks, cr.completion_status,
            ct.id AS task_id, ct.report_id, u.full_name AS officer
     FROM collection_records cr INNER JOIN collection_tasks ct ON ct.id = cr.task_id
     INNER JOIN users u ON u.id = cr.officer_id ORDER BY cr.created_at DESC'
);
$records = $statement->fetchAll();
$pageTitle = 'Collection monitoring';
$bodyClass = 'resident-portal-page';
$pageClass = 'resident-portal';
require __DIR__ . '/../includes/header.php';
?>
<?php require __DIR__ . '/../includes/portal_start.php'; ?><div class="portal-heading"><div><div class="portal-date"><?= e(strtoupper(date('l, j F'))) ?></div><h1>Collection monitoring</h1><p>Review field collection activities recorded by officers.</p></div><a class="portal-secondary-action" href="<?= APP_URL ?>/admin/dashboard.php">Dashboard</a></div>
<div class="portal-panel"><div class="table-responsive"><table class="table align-middle"><thead><tr><th>Task</th><th>Report</th><th>Officer</th><th>Date</th><th>Quantity</th><th>Status</th><th>Remarks</th></tr></thead><tbody><?php foreach ($records as $record): ?><tr><td>#<?= e($record['task_id']) ?></td><td>#<?= e($record['report_id']) ?></td><td><?= e($record['officer']) ?></td><td><?= e($record['collection_date']) ?></td><td><?= e($record['quantity'] ?? '-') ?> <?= e($record['quantity_unit'] ?? '') ?></td><td><?= e(report_status_label($record['completion_status'])) ?></td><td><?= e($record['remarks'] ?? '-') ?></td></tr><?php endforeach; ?><?php if (!$records): ?><tr><td colspan="7" class="text-muted">No collection activities recorded yet.</td></tr><?php endif; ?></tbody></table></div></div>
<?php require __DIR__ . '/../includes/portal_end.php'; ?><?php require __DIR__ . '/../includes/footer.php'; ?>
