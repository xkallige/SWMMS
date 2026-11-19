<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth_check.php';
require_login('admin');

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!verify_csrf($_POST['csrf_token'] ?? null)) throw new RuntimeException('Your session token is invalid.');
        $action = $_POST['action'] ?? '';
        $userId = filter_var($_POST['user_id'] ?? null, FILTER_VALIDATE_INT);
        if ($action === 'toggle' && $userId && $userId !== (int) current_user()['id']) {
            $statement = db()->prepare('UPDATE users SET is_active = NOT is_active WHERE id = ?');
            $statement->execute([$userId]);
            flash('success', 'User account status updated.');
        } elseif ($action === 'create') {
            $name = trim((string) ($_POST['full_name'] ?? ''));
            $email = strtolower(trim((string) ($_POST['email'] ?? '')));
            $role = (string) ($_POST['role'] ?? 'resident');
            $password = (string) ($_POST['password'] ?? '');
            if (mb_strlen($name) < 2 || !filter_var($email, FILTER_VALIDATE_EMAIL) || !in_array($role, ['resident', 'officer', 'admin'], true) || strlen($password) < 8) {
                throw new RuntimeException('Enter valid user details and a password of at least 8 characters.');
            }
            $statement = db()->prepare('INSERT INTO users (full_name, email, password_hash, role) VALUES (?, ?, ?, ?)');
            $statement->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), $role]);
            flash('success', 'User account created.');
        }
        redirect(APP_URL . '/admin/users.php');
    } catch (PDOException $exception) {
        $errors[] = ($exception->errorInfo[1] ?? null) === 1062 ? 'That email address is already registered.' : 'The user could not be saved.';
    } catch (Throwable $exception) {
        $errors[] = $exception->getMessage();
    }
}
$users = db()->query('SELECT id, full_name, email, role, is_active, created_at FROM users ORDER BY created_at DESC')->fetchAll();
$pageTitle = 'Manage users';
$bodyClass = 'resident-portal-page';
$pageClass = 'resident-portal';
require __DIR__ . '/../includes/header.php';
?>
<?php require __DIR__ . '/../includes/portal_start.php'; ?>
<div class="d-flex justify-content-between align-items-center mb-4"><div><h1 class="h3 mb-1">Manage users</h1><p class="text-muted mb-0">Create officers and control account access.</p></div><a class="btn btn-outline-success" href="<?= APP_URL ?>/admin/dashboard.php">Dashboard</a></div>
<?php foreach ($errors as $error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endforeach; ?><?php if ($message = flash('success')): ?><div class="alert alert-success"><?= e($message) ?></div><?php endif; ?>
<div class="row g-4"><div class="col-lg-4"><div class="card p-4"><h2 class="h5">Create account</h2><form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="create"><label class="form-label">Full name</label><input class="form-control mb-3" name="full_name" required><label class="form-label">Email</label><input class="form-control mb-3" name="email" type="email" required><label class="form-label">Role</label><select class="form-select mb-3" name="role"><option value="resident">Resident</option><option value="officer">Officer</option><option value="admin">Administrator</option></select><label class="form-label">Temporary password</label><input class="form-control mb-3" name="password" type="password" minlength="8" required><button class="btn btn-success w-100">Create account</button></form></div></div><div class="col-lg-8"><div class="card p-4"><h2 class="h5">Accounts</h2><div class="table-responsive"><table class="table align-middle"><thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th></th></tr></thead><tbody><?php foreach ($users as $user): ?><tr><td><?= e($user['full_name']) ?></td><td><?= e($user['email']) ?></td><td><?= e(ucfirst($user['role'])) ?></td><td><span class="badge text-bg-<?= $user['is_active'] ? 'success' : 'secondary' ?>"><?= $user['is_active'] ? 'Active' : 'Inactive' ?></span></td><td><?php if ((int) $user['id'] !== (int) current_user()['id']): ?><form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="toggle"><input type="hidden" name="user_id" value="<?= e($user['id']) ?>"><button class="btn btn-sm btn-outline-secondary"><?= $user['is_active'] ? 'Deactivate' : 'Activate' ?></button></form><?php endif; ?></td></tr><?php endforeach; ?></tbody></table></div></div></div></div>
<?php require __DIR__ . '/../includes/portal_end.php'; ?><?php require __DIR__ . '/../includes/footer.php'; ?>
