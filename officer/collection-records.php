<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth_check.php';
require_login('officer');
$officerId = (int) current_user()['id'];
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!verify_csrf($_POST['csrf_token'] ?? null)) throw new RuntimeException('Your session token is invalid.');
        $taskId = filter_var($_POST['task_id'] ?? null, FILTER_VALIDATE_INT);
        $date = (string) ($_POST['collection_date'] ?? '');
        $quantity = filter_var($_POST['quantity'] ?? null, FILTER_VALIDATE_FLOAT, FILTER_NULL_ON_FAILURE);
        $unit = trim((string) ($_POST['quantity_unit'] ?? ''));
        $remarks = trim((string) ($_POST['remarks'] ?? ''));
        if (!$taskId || !DateTime::createFromFormat('Y-m-d', $date) || $date > date('Y-m-d')) throw new RuntimeException('Enter a valid collection date.');
        if ($quantity !== null && ($quantity < 0 || $quantity > 99999999)) throw new RuntimeException('Enter a valid quantity.');
        $task = db()->prepare("SELECT id FROM collection_tasks WHERE id = ? AND officer_id = ? AND status IN ('in_progress', 'completed')");
        $task->execute([$taskId, $officerId]);
        if (!$task->fetch()) throw new RuntimeException('You can only record activity for your assigned tasks.');
        $photoPath = null;
        $upload = $_FILES['cleared_site_photo'] ?? null;
        if ($upload && $upload['error'] !== UPLOAD_ERR_NO_FILE) {
            if ($upload['error'] !== UPLOAD_ERR_OK || $upload['size'] > 5 * 1024 * 1024) throw new RuntimeException('Cleared-site photographs must be valid images no larger than 5 MB.');
            $imageInfo = @getimagesize($upload['tmp_name']);
            $allowedTypes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
            $mime = $imageInfo['mime'] ?? '';
            if (!$imageInfo || !isset($allowedTypes[$mime])) throw new RuntimeException('Only JPEG, PNG, and WebP cleared-site photographs are allowed.');
            if (!is_dir(UPLOAD_DIR) && !mkdir(UPLOAD_DIR, 0750, true)) throw new RuntimeException('The upload directory is unavailable.');
            $filename = bin2hex(random_bytes(16)) . '.' . $allowedTypes[$mime];
            if (!move_uploaded_file($upload['tmp_name'], UPLOAD_DIR . $filename)) throw new RuntimeException('The cleared-site photograph could not be saved.');
            $photoPath = 'uploads/reports/' . $filename;
        }
        $statement = db()->prepare('INSERT INTO collection_records (task_id, officer_id, collection_date, quantity, quantity_unit, remarks, cleared_site_photo_path) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $statement->execute([$taskId, $officerId, $date, $quantity, $unit ?: null, $remarks ?: null, $photoPath]);
        flash('success', 'Collection activity recorded.');
        redirect(APP_URL . '/officer/collection-records.php');
    } catch (Throwable $exception) {
        $errors[] = $exception->getMessage();
    }
}
$tasksStatement = db()->prepare("SELECT id, report_id FROM collection_tasks WHERE officer_id = ? AND status IN ('in_progress', 'completed') ORDER BY assigned_at DESC");
$tasksStatement->execute([$officerId]);
$tasks = $tasksStatement->fetchAll();
$recordsStatement = db()->prepare('SELECT cr.*, ct.report_id FROM collection_records cr INNER JOIN collection_tasks ct ON ct.id = cr.task_id WHERE cr.officer_id = ? ORDER BY cr.created_at DESC');
$recordsStatement->execute([$officerId]);
$records = $recordsStatement->fetchAll();
$pageTitle = 'Collection records';
$bodyClass = 'resident-portal-page';
$pageClass = 'resident-portal';
require __DIR__ . '/../includes/header.php';
?>
<div class="portal-shell"><?php require __DIR__ . '/../includes/portal_sidebar.php'; ?><button class="portal-menu-toggle" type="button" aria-controls="portalSidebar" aria-expanded="false"><svg viewBox="0 0 24 24" fill="none"><path d="M4 7h16M4 12h16M4 17h16"/></svg><span>Menu</span></button><div class="portal-sidebar-backdrop" data-portal-close></div><main class="portal-main"><header class="portal-topbar"><div><strong>Officer portal</strong><small>Field operations and collection response</small></div><div class="portal-user"><span><?= e(strtoupper(substr(current_user()['full_name'], 0, 1))) ?></span><div><strong><?= e(current_user()['full_name']) ?></strong><small>Officer account</small></div></div></header><div class="portal-content">
<div class="portal-heading"><div><div class="portal-date"><?= e(strtoupper(date('l, j F'))) ?></div><h1>Collection records</h1><p>Record quantities, remarks, and cleared-site evidence.</p></div><a class="portal-secondary-action" href="<?= APP_URL ?>/officer/assigned-tasks.php">Assigned tasks</a></div>
<?php foreach ($errors as $error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endforeach; ?><?php if ($message = flash('success')): ?><div class="alert alert-success"><?= e($message) ?></div><?php endif; ?>
<div class="row g-4"><div class="col-lg-5"><div class="card p-4"><h2 class="h5">Add activity</h2><form method="post" enctype="multipart/form-data"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><label class="form-label" for="task_id">Task</label><select class="form-select mb-3" id="task_id" name="task_id" required><option value="">Select task</option><?php foreach ($tasks as $task): ?><option value="<?= e($task['id']) ?>">Task #<?= e($task['id']) ?> · Report #<?= e($task['report_id']) ?></option><?php endforeach; ?></select><label class="form-label" for="collection_date">Collection date</label><input class="form-control mb-3" id="collection_date" name="collection_date" type="date" max="<?= date('Y-m-d') ?>" required><div class="row"><div class="col-7"><label class="form-label" for="quantity">Quantity</label><input class="form-control mb-3" id="quantity" name="quantity" type="number" min="0" step="0.01"></div><div class="col-5"><label class="form-label" for="quantity_unit">Unit</label><input class="form-control mb-3" id="quantity_unit" name="quantity_unit" placeholder="kg"></div></div><label class="form-label" for="remarks">Remarks</label><textarea class="form-control mb-3" id="remarks" name="remarks" rows="3"></textarea><label class="form-label" for="cleared_site_photo">Cleared site photograph <span class="text-muted">(optional, max 5 MB)</span></label><input class="form-control mb-3" id="cleared_site_photo" name="cleared_site_photo" type="file" accept="image/jpeg,image/png,image/webp" capture="environment"><button class="btn btn-success w-100">Save record</button></form></div></div><div class="col-lg-7"><div class="card p-4"><h2 class="h5">Activity history</h2><div class="table-responsive"><table class="table"><thead><tr><th>Report</th><th>Date</th><th>Quantity</th><th>Evidence</th><th>Remarks</th></tr></thead><tbody><?php foreach ($records as $record): ?><tr><td>#<?= e($record['report_id']) ?></td><td><?= e($record['collection_date']) ?></td><td><?= e($record['quantity'] ?? '-') ?> <?= e($record['quantity_unit'] ?? '') ?></td><td><?= !empty($record['cleared_site_photo_path']) ? '<span class="badge text-bg-success">Attached</span>' : '<span class="text-muted">-</span>' ?></td><td><?= e($record['remarks'] ?? '-') ?></td></tr><?php endforeach; ?><?php if (!$records): ?><tr><td colspan="5" class="text-muted">No collection records yet.</td></tr><?php endif; ?></tbody></table></div></div></div></div>
</div></main></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
