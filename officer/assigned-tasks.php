<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/report_operations.php';
require_login('officer');
$officerId = (int) current_user()['id'];
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!verify_csrf($_POST['csrf_token'] ?? null)) throw new RuntimeException('Your session token is invalid.');
        update_task_status((int) ($_POST['task_id'] ?? 0), $officerId, (string) ($_POST['status'] ?? ''));
        flash('success', 'Task status updated.');
        redirect(APP_URL . '/officer/assigned-tasks.php');
    } catch (Throwable $exception) {
        $errors[] = $exception->getMessage();
    }
}
$statement = db()->prepare(
    'SELECT ct.id, ct.status, ct.assigned_at, wr.id AS report_id, wr.description, wr.latitude, wr.longitude, wc.name AS category
     FROM collection_tasks ct INNER JOIN waste_reports wr ON wr.id = ct.report_id
     INNER JOIN waste_categories wc ON wc.id = wr.category_id WHERE ct.officer_id = ? ORDER BY ct.assigned_at DESC'
);
$statement->execute([$officerId]);
$tasks = $statement->fetchAll();
$pageTitle = 'Assigned tasks';
$bodyClass = 'resident-portal-page';
$pageClass = 'resident-portal';
require __DIR__ . '/../includes/header.php';
?>
<?php require __DIR__ . '/../includes/portal_start.php'; ?><div class="portal-heading"><div><div class="portal-date"><?= e(strtoupper(date('l, j F'))) ?></div><h1>Assigned tasks</h1><p>Manage reports assigned to you.</p></div><a class="portal-secondary-action" href="<?= APP_URL ?>/officer/dashboard.php">Dashboard</a></div>
<?php foreach ($errors as $error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endforeach; ?><?php if ($message = flash('success')): ?><div class="alert alert-success"><?= e($message) ?></div><?php endif; ?>
<div class="row g-4"><?php foreach ($tasks as $task): ?><div class="col-lg-6"><div class="portal-panel"><div class="d-flex justify-content-between"><h2 class="h5">Task #<?= e($task['id']) ?> · Report #<?= e($task['report_id']) ?></h2><span class="badge text-bg-<?= e($task['status'] === 'completed' ? 'success' : 'primary') ?>"><?= e(report_status_label($task['status'])) ?></span></div><div class="small text-muted mb-2"><?= e($task['category']) ?> · <?= e($task['latitude']) ?>, <?= e($task['longitude']) ?></div><p><?= e($task['description']) ?></p><?php if ($task['status'] !== 'completed' && $task['status'] !== 'cancelled'): ?><form method="post" class="d-flex gap-2"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="task_id" value="<?= e($task['id']) ?>"><select class="form-select" name="status"><option value="in_progress">Start work</option><option value="completed">Mark completed</option><option value="cancelled">Cancel task</option></select><button class="btn btn-success">Save</button></form><?php endif; ?></div></div><?php endforeach; ?><?php if (!$tasks): ?><div class="col-12"><div class="portal-panel text-muted">No tasks assigned yet.</div></div><?php endif; ?></div>
<?php require __DIR__ . '/../includes/portal_end.php'; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
