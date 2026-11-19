<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth_check.php';
require_login('admin');
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!verify_csrf($_POST['csrf_token'] ?? null)) throw new RuntimeException('Your session token is invalid.');
        $action = $_POST['action'] ?? 'create';
        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
        if ($action === 'toggle' && $id) {
            db()->prepare('UPDATE waste_categories SET is_active = NOT is_active WHERE id = ?')->execute([$id]);
            flash('success', 'Category status updated.');
        } else {
            $name = trim((string) ($_POST['name'] ?? ''));
            if (mb_strlen($name) < 2) throw new RuntimeException('Enter a category name.');
            db()->prepare('INSERT INTO waste_categories (name) VALUES (?)')->execute([$name]);
            flash('success', 'Category added.');
        }
        redirect(APP_URL . '/admin/categories.php');
    } catch (PDOException $exception) {
        $errors[] = ($exception->errorInfo[1] ?? null) === 1062 ? 'That category already exists.' : 'The category could not be saved.';
    } catch (Throwable $exception) {
        $errors[] = $exception->getMessage();
    }
}
$categories = db()->query('SELECT wc.id, wc.name, wc.is_active, COUNT(wr.id) AS report_count FROM waste_categories wc LEFT JOIN waste_reports wr ON wr.category_id = wc.id GROUP BY wc.id ORDER BY wc.name')->fetchAll();
$pageTitle = 'Manage categories';
$bodyClass = 'resident-portal-page';
$pageClass = 'resident-portal';
require __DIR__ . '/../includes/header.php';
?>
<?php require __DIR__ . '/../includes/portal_start.php'; ?>
<div class="d-flex justify-content-between align-items-center mb-4"><div><h1 class="h3 mb-1">Manage waste categories</h1><p class="text-muted mb-0">Control the categories residents can select.</p></div><a class="btn btn-outline-success" href="<?= APP_URL ?>/admin/dashboard.php">Dashboard</a></div>
<?php foreach ($errors as $error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endforeach; ?><?php if ($message = flash('success')): ?><div class="alert alert-success"><?= e($message) ?></div><?php endif; ?>
<div class="row g-4"><div class="col-lg-4"><div class="card p-4"><h2 class="h5">Add category</h2><form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input class="form-control mb-3" name="name" placeholder="Category name" required><button class="btn btn-success w-100">Add category</button></form></div></div><div class="col-lg-8"><div class="card p-4"><h2 class="h5">Categories</h2><div class="table-responsive"><table class="table"><thead><tr><th>Name</th><th>Reports</th><th>Status</th><th></th></tr></thead><tbody><?php foreach ($categories as $category): ?><tr><td><?= e($category['name']) ?></td><td><?= e($category['report_count']) ?></td><td><?= $category['is_active'] ? 'Active' : 'Inactive' ?></td><td><form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= e($category['id']) ?>"><button class="btn btn-sm btn-outline-secondary"><?= $category['is_active'] ? 'Deactivate' : 'Activate' ?></button></form></td></tr><?php endforeach; ?></tbody></table></div></div></div></div>
<?php require __DIR__ . '/../includes/portal_end.php'; ?><?php require __DIR__ . '/../includes/footer.php'; ?>
