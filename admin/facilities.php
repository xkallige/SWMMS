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
        if ($action === 'delete' && $id) {
            db()->prepare('DELETE FROM facilities WHERE id = ?')->execute([$id]);
            flash('success', 'Facility deleted.');
        } else {
            $name = trim((string) ($_POST['name'] ?? ''));
            $type = (string) ($_POST['facility_type'] ?? '');
            $address = trim((string) ($_POST['address'] ?? ''));
            $description = trim((string) ($_POST['description'] ?? ''));
            $latitude = filter_var($_POST['latitude'] ?? null, FILTER_VALIDATE_FLOAT);
            $longitude = filter_var($_POST['longitude'] ?? null, FILTER_VALIDATE_FLOAT);
            if ($name === '' || !in_array($type, ['collection_point','public_bin','disposal_site','recycling_facility','transfer_station'], true) || $latitude === false || $longitude === false || $latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) throw new RuntimeException('Enter a valid facility name, type, and coordinates.');
            $statement = db()->prepare("INSERT INTO facilities (name, facility_type, description, address, latitude, longitude, location) VALUES (?, ?, ?, ?, ?, ?, ST_GeomFromText(CONCAT('POINT(', ?, ' ', ?, ')')))");
            $statement->execute([$name, $type, $description ?: null, $address ?: null, $latitude, $longitude, $longitude, $latitude]);
            flash('success', 'Facility added.');
        }
        redirect(APP_URL . '/admin/facilities.php');
    } catch (Throwable $exception) {
        $errors[] = $exception->getMessage();
    }
}
$facilities = db()->query('SELECT id, name, facility_type, address, latitude, longitude, operational_status FROM facilities ORDER BY name')->fetchAll();
$pageTitle = 'Manage facilities';
$bodyClass = 'resident-portal-page';
$pageClass = 'resident-portal';
require __DIR__ . '/../includes/header.php';
?>
<?php require __DIR__ . '/../includes/portal_start.php'; ?>
<div class="d-flex justify-content-between align-items-center mb-4"><div><h1 class="h3 mb-1">Manage facilities</h1><p class="text-muted mb-0">Add mapped collection and disposal facilities.</p></div><a class="btn btn-outline-success" href="<?= APP_URL ?>/admin/dashboard.php">Dashboard</a></div>
<?php foreach ($errors as $error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endforeach; ?><?php if ($message = flash('success')): ?><div class="alert alert-success"><?= e($message) ?></div><?php endif; ?>
<div class="row g-4"><div class="col-lg-5"><div class="card p-4"><h2 class="h5">Add facility</h2><form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><label class="form-label">Name</label><input class="form-control mb-3" name="name" required><label class="form-label">Type</label><select class="form-select mb-3" name="facility_type"><?php foreach (['collection_point','public_bin','disposal_site','recycling_facility','transfer_station'] as $type): ?><option value="<?= e($type) ?>"><?= e(ucwords(str_replace('_', ' ', $type))) ?></option><?php endforeach; ?></select><label class="form-label">Address</label><input class="form-control mb-3" name="address"><label class="form-label">Description</label><textarea class="form-control mb-3" name="description" rows="2"></textarea><div class="row"><div class="col"><label class="form-label">Latitude</label><input class="form-control mb-3" name="latitude" type="number" step="any" min="-90" max="90" required></div><div class="col"><label class="form-label">Longitude</label><input class="form-control mb-3" name="longitude" type="number" step="any" min="-180" max="180" required></div></div><button class="btn btn-success w-100">Add facility</button></form></div></div><div class="col-lg-7"><div class="card p-4"><h2 class="h5">Facilities</h2><div class="table-responsive"><table class="table align-middle"><thead><tr><th>Name</th><th>Type</th><th>Coordinates</th><th></th></tr></thead><tbody><?php foreach ($facilities as $facility): ?><tr><td><?= e($facility['name']) ?></td><td><?= e(ucwords(str_replace('_', ' ', $facility['facility_type']))) ?></td><td><?= e($facility['latitude']) ?>, <?= e($facility['longitude']) ?></td><td><form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= e($facility['id']) ?>"><button class="btn btn-sm btn-outline-danger">Delete</button></form></td></tr><?php endforeach; ?><?php if (!$facilities): ?><tr><td colspan="4" class="text-muted">No facilities added yet.</td></tr><?php endif; ?></tbody></table></div></div></div></div>
<?php require __DIR__ . '/../includes/portal_end.php'; ?><?php require __DIR__ . '/../includes/footer.php'; ?>
