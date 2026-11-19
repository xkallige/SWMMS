<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth_check.php';
require_login('admin');

$status = $_GET['status'] ?? '';
$allowedStatuses = ['pending', 'under_review', 'assigned', 'in_progress', 'resolved', 'rejected', 'duplicate'];
$sql = 'SELECT wr.id, wr.status, wr.created_at, u.full_name, wc.name AS category, ao.full_name AS officer
        FROM waste_reports wr INNER JOIN users u ON u.id = wr.reported_by
        INNER JOIN waste_categories wc ON wc.id = wr.category_id
        LEFT JOIN users ao ON ao.id = wr.assigned_officer_id';
$parameters = [];
if (in_array($status, $allowedStatuses, true)) {
    $sql .= ' WHERE wr.status = ?';
    $parameters[] = $status;
}
$sql .= ' ORDER BY wr.created_at DESC';
$statement = db()->prepare($sql);
$statement->execute($parameters);
$reports = $statement->fetchAll();
$pageTitle = 'Manage reports';
$bodyClass = 'resident-portal-page';
$pageClass = 'resident-portal';
require __DIR__ . '/../includes/header.php';
?>
<?php require __DIR__ . '/../includes/portal_start.php'; ?><div class="portal-heading"><div><div class="portal-date"><?= e(strtoupper(date('l, j F'))) ?></div><h1>Manage reports</h1><p>Review submissions and assign collection work.</p></div><a class="portal-secondary-action" href="<?= APP_URL ?>/admin/dashboard.php">Dashboard</a></div>
<div class="portal-panel"><form class="row g-2 mb-3"><div class="col-md-4"><label class="visually-hidden" for="status">Status</label><select class="form-select" id="status" name="status"><option value="">All statuses</option><?php foreach ($allowedStatuses as $option): ?><option value="<?= e($option) ?>" <?= $status === $option ? 'selected' : '' ?>><?= e(report_status_label($option)) ?></option><?php endforeach; ?></select></div><div class="col-auto"><button class="btn btn-success">Filter</button></div></form><div class="table-responsive"><table class="table align-middle"><thead><tr><th>Report</th><th>Resident</th><th>Category</th><th>Officer</th><th>Status</th><th></th></tr></thead><tbody><?php foreach ($reports as $report): ?><tr><td>#<?= e($report['id']) ?></td><td><?= e($report['full_name']) ?></td><td><?= e($report['category']) ?></td><td><?= e($report['officer'] ?? 'Unassigned') ?></td><td><span class="badge text-bg-<?= e(report_status_class($report['status'])) ?>"><?= e(report_status_label($report['status'])) ?></span></td><td><a class="btn btn-sm btn-outline-success" href="<?= APP_URL ?>/admin/report-details.php?id=<?= e($report['id']) ?>">Review</a></td></tr><?php endforeach; ?><?php if (!$reports): ?><tr><td colspan="6" class="text-muted">No reports found.</td></tr><?php endif; ?></tbody></table></div></div>
<?php require __DIR__ . '/../includes/portal_end.php'; ?><?php require __DIR__ . '/../includes/footer.php'; ?>
