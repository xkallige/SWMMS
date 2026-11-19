<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/report_operations.php';
require_login('admin');

$reportId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
if (!$reportId) {
    http_response_code(404);
    exit('Report not found.');
}
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!verify_csrf($_POST['csrf_token'] ?? null)) throw new RuntimeException('Your session token is invalid.');
        if (($_POST['action'] ?? '') === 'assign') {
            $officerId = filter_var($_POST['officer_id'] ?? null, FILTER_VALIDATE_INT);
            if (!$officerId) throw new RuntimeException('Select an officer.');
            assign_report($reportId, $officerId, (int) current_user()['id']);
            flash('success', 'Report assigned successfully.');
        } elseif (($_POST['action'] ?? '') === 'status') {
            update_report_status($reportId, (string) ($_POST['status'] ?? ''), (int) current_user()['id'], trim((string) ($_POST['remarks'] ?? '')));
            flash('success', 'Report status updated.');
        }
        redirect(APP_URL . '/admin/report-details.php?id=' . $reportId);
    } catch (Throwable $exception) {
        $errors[] = $exception->getMessage();
    }
}
$statement = db()->prepare(
    'SELECT wr.*, wc.name AS category, u.full_name AS resident, u.email AS resident_email, c.name AS community, ao.full_name AS officer
     FROM waste_reports wr INNER JOIN waste_categories wc ON wc.id = wr.category_id
     INNER JOIN users u ON u.id = wr.reported_by LEFT JOIN communities c ON c.id = wr.community_id
     LEFT JOIN users ao ON ao.id = wr.assigned_officer_id WHERE wr.id = ?'
);
$statement->execute([$reportId]);
$report = $statement->fetch();
if (!$report) {
    http_response_code(404);
    exit('Report not found.');
}
$officers = db()->query("SELECT id, full_name FROM users WHERE role = 'officer' AND is_active = 1 ORDER BY full_name")->fetchAll();
$historyStatement = db()->prepare('SELECT ru.*, u.full_name FROM report_updates ru INNER JOIN users u ON u.id = ru.changed_by WHERE ru.report_id = ? ORDER BY ru.created_at DESC');
$historyStatement->execute([$reportId]);
$history = $historyStatement->fetchAll();
$pageTitle = 'Review report #' . $reportId;
$bodyClass = 'resident-portal-page';
$pageClass = 'resident-portal';
require __DIR__ . '/../includes/header.php';
?>
<?php require __DIR__ . '/../includes/portal_start.php'; ?>
<div class="d-flex justify-content-between align-items-center mb-4"><div><h1 class="h3 mb-1">Review report #<?= e($report['id']) ?></h1><p class="text-muted mb-0">Submitted by <?= e($report['resident']) ?> on <?= e(date('M j, Y', strtotime($report['created_at']))) ?>.</p></div><a class="btn btn-outline-success" href="<?= APP_URL ?>/admin/reports.php">All reports</a></div>
<?php foreach ($errors as $error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endforeach; ?><?php if ($message = flash('success')): ?><div class="alert alert-success"><?= e($message) ?></div><?php endif; ?>
<div class="row g-4"><div class="col-lg-7"><div class="card p-4"><dl class="row mb-0"><dt class="col-sm-4">Category</dt><dd class="col-sm-8"><?= e($report['category']) ?></dd><dt class="col-sm-4">Community</dt><dd class="col-sm-8"><?= e($report['community'] ?? 'Not specified') ?></dd><dt class="col-sm-4">Location</dt><dd class="col-sm-8"><?= e($report['latitude']) ?>, <?= e($report['longitude']) ?></dd><dt class="col-sm-4">Description</dt><dd class="col-sm-8"><?= nl2br(e($report['description'])) ?></dd><dt class="col-sm-4">Assigned officer</dt><dd class="col-sm-8"><?= e($report['officer'] ?? 'Unassigned') ?></dd><dt class="col-sm-4">Status</dt><dd class="col-sm-8"><span class="badge text-bg-<?= e(report_status_class($report['status'])) ?>"><?= e(report_status_label($report['status'])) ?></span></dd></dl></div><div class="card p-4 mt-4"><h2 class="h5">Status history</h2><?php foreach ($history as $item): ?><div class="border-bottom py-2"><strong><?= e(report_status_label($item['new_status'])) ?></strong><span class="text-muted small"> by <?= e($item['full_name']) ?> on <?= e(date('M j, Y H:i', strtotime($item['created_at']))) ?></span><?php if ($item['remarks']): ?><div class="small"><?= e($item['remarks']) ?></div><?php endif; ?></div><?php endforeach; ?></div></div>
<div class="col-lg-5"><div class="card p-4"><h2 class="h5">Assign officer</h2><form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="assign"><label class="form-label" for="officer_id">Officer</label><select class="form-select mb-3" id="officer_id" name="officer_id" required><option value="">Select officer</option><?php foreach ($officers as $officer): ?><option value="<?= e($officer['id']) ?>"><?= e($officer['full_name']) ?></option><?php endforeach; ?></select><button class="btn btn-success w-100" type="submit">Assign report</button></form></div><div class="card p-4 mt-4"><h2 class="h5">Update status</h2><form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="status"><label class="form-label" for="status">New status</label><select class="form-select mb-3" id="status" name="status"><?php foreach (['pending','under_review','assigned','in_progress','resolved','rejected','duplicate'] as $option): ?><option value="<?= e($option) ?>" <?= $report['status'] === $option ? 'selected' : '' ?>><?= e(report_status_label($option)) ?></option><?php endforeach; ?></select><label class="form-label" for="remarks">Remarks</label><textarea class="form-control mb-3" id="remarks" name="remarks" rows="3"></textarea><button class="btn btn-outline-success w-100" type="submit">Save status</button></form></div></div></div>
<?php require __DIR__ . '/../includes/portal_end.php'; ?><?php require __DIR__ . '/../includes/footer.php'; ?>
