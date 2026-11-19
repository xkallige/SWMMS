<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth_check.php';
require_login('resident');

$errors = [];
$categories = db()->query('SELECT id, name FROM waste_categories WHERE is_active = 1 ORDER BY name')->fetchAll();
$communities = db()->query('SELECT id, name FROM communities ORDER BY name')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $categoryId = filter_var($_POST['category_id'] ?? null, FILTER_VALIDATE_INT);
    $communityId = filter_var($_POST['community_id'] ?? null, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE);
    $description = trim((string) ($_POST['description'] ?? ''));
    $latitude = filter_var($_POST['latitude'] ?? null, FILTER_VALIDATE_FLOAT);
    $longitude = filter_var($_POST['longitude'] ?? null, FILTER_VALIDATE_FLOAT);

    if (!verify_csrf($_POST['csrf_token'] ?? null)) $errors[] = 'Your session token is invalid. Refresh and try again.';
    if (!$categoryId) $errors[] = 'Select a waste category.';
    if (strlen($description) < 10 || strlen($description) > 5000) $errors[] = 'Description must be between 10 and 5,000 characters.';
    if ($latitude === false || $latitude < -90 || $latitude > 90 || $longitude === false || $longitude < -180 || $longitude > 180) $errors[] = 'Select a valid location on the map.';

    $photographPath = null;
    $upload = $_FILES['photograph'] ?? null;
    if (!$upload || $upload['error'] === UPLOAD_ERR_NO_FILE) $upload = $_FILES['camera_photo'] ?? null;
    if ($upload && $upload['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($upload['error'] !== UPLOAD_ERR_OK || $upload['size'] > 5 * 1024 * 1024) {
            $errors[] = 'Photographs must be valid images no larger than 5 MB.';
        } else {
            $imageInfo = @getimagesize($upload['tmp_name']);
            $allowedTypes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
            $mime = $imageInfo['mime'] ?? '';
            if (!$imageInfo || !isset($allowedTypes[$mime])) {
                $errors[] = 'Only JPEG, PNG, and WebP photographs are allowed.';
            } else {
                $filename = bin2hex(random_bytes(16)) . '.' . $allowedTypes[$mime];
                $target = UPLOAD_DIR . $filename;
                if (!is_dir(UPLOAD_DIR) && !mkdir(UPLOAD_DIR, 0750, true)) {
                    $errors[] = 'The upload directory is unavailable.';
                } elseif (!move_uploaded_file($upload['tmp_name'], $target)) {
                    $errors[] = 'The photograph could not be saved.';
                } else {
                    $photographPath = 'uploads/reports/' . $filename;
                }
            }
        }
    }

    if (!$errors) {
        $connection = db();
        try {
            $connection->beginTransaction();
            $statement = $connection->prepare(
                "INSERT INTO waste_reports
                 (reported_by, category_id, community_id, description, latitude, longitude, location)
                 VALUES (?, ?, ?, ?, ?, ?, ST_GeomFromText(CONCAT('POINT(', ?, ' ', ?, ')')))"
            );
            $statement->execute([
                current_user()['id'], $categoryId, $communityId ?: null, $description,
                $latitude, $longitude, $longitude, $latitude,
            ]);
            $reportId = (int) $connection->lastInsertId();
            if ($photographPath) {
                $update = $connection->prepare('UPDATE waste_reports SET photograph_path = ? WHERE id = ?');
                $update->execute([$photographPath, $reportId]);
            }
            $history = $connection->prepare(
                'INSERT INTO report_updates (report_id, changed_by, old_status, new_status, remarks) VALUES (?, ?, NULL, ?, ?)'
            );
            $history->execute([$reportId, current_user()['id'], 'pending', 'Report submitted']);
            $notification = $connection->prepare(
                'INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)'
            );
            $notification->execute([
                current_user()['id'], 'Report submitted',
                'Your waste report #' . $reportId . ' was submitted and is pending review.',
            ]);
            $activity = $connection->prepare(
                'INSERT INTO system_activity_logs (user_id, action, entity_type, entity_id, ip_address) VALUES (?, ?, ?, ?, ?)'
            );
            $activity->execute([
                current_user()['id'], 'submitted_report', 'waste_report', $reportId,
                $_SERVER['REMOTE_ADDR'] ?? null,
            ]);
            $connection->commit();
            flash('success', 'Your waste report was submitted successfully.');
            redirect(APP_URL . '/resident/my-reports.php');
        } catch (Throwable $exception) {
            if ($connection->inTransaction()) $connection->rollBack();
            if ($photographPath) @unlink(UPLOAD_DIR . basename($photographPath));
            throw $exception;
        }
    }
}

$pageTitle = 'Report waste';
$bodyClass = 'resident-portal-page';
$pageClass = 'resident-portal';
$extraHead = '<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">';
require __DIR__ . '/../includes/header.php';
?>
<div class="portal-shell"><?php require __DIR__ . '/../includes/portal_sidebar.php'; ?><button class="portal-menu-toggle" type="button" aria-controls="portalSidebar" aria-expanded="false"><svg viewBox="0 0 24 24" fill="none"><path d="M4 7h16M4 12h16M4 17h16"/></svg><span>Menu</span></button><div class="portal-sidebar-backdrop" data-portal-close></div><main class="portal-main"><header class="portal-topbar"><div><strong>Resident portal</strong><small>Community reporting and progress tracking</small></div><div class="portal-user"><span><?= e(strtoupper(substr(current_user()['full_name'], 0, 1))) ?></span><div><strong><?= e(current_user()['full_name']) ?></strong><small>Resident account</small></div></div></header><div class="portal-content">
<div class="portal-heading"><div><div class="portal-date"><?= e(strtoupper(date('l, j F'))) ?></div><h1>Report a waste problem</h1><p>Provide details and click the map to select the location.</p></div></div>
<div class="row g-4"><div class="col-lg-5"><div class="portal-panel p-4">
    <?php foreach ($errors as $error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endforeach; ?>
    <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="latitude" id="latitude" value="<?= e($_POST['latitude'] ?? '') ?>">
        <input type="hidden" name="longitude" id="longitude" value="<?= e($_POST['longitude'] ?? '') ?>">
        <label class="form-label" for="category_id">Waste category</label>
        <select class="form-select mb-3" id="category_id" name="category_id" required><option value="">Select category</option><?php foreach ($categories as $category): ?><option value="<?= e($category['id']) ?>" <?= (string) ($category['id']) === (string) ($_POST['category_id'] ?? '') ? 'selected' : '' ?>><?= e($category['name']) ?></option><?php endforeach; ?></select>
        <label class="form-label" for="community_id">Community <span class="text-muted">(optional)</span></label>
        <select class="form-select mb-3" id="community_id" name="community_id"><option value="">Not specified</option><?php foreach ($communities as $community): ?><option value="<?= e($community['id']) ?>"><?= e($community['name']) ?></option><?php endforeach; ?></select>
        <label class="form-label" for="description">Description</label>
        <textarea class="form-control mb-3" id="description" name="description" rows="5" required><?= e($_POST['description'] ?? '') ?></textarea>
        <label class="form-label">Photograph <span class="text-muted">(optional, max 5 MB)</span></label>
        <div class="report-photo-picker mb-3"><button class="report-photo-choice" type="button" data-photo-trigger="camera-photo"><svg viewBox="0 0 24 24" fill="none"><path d="M4 8h4l1.5-2h5L16 8h4v11H4V8Z"/><circle cx="12" cy="13" r="3"/></svg><span><strong>Capture on spot</strong><small>Use your camera</small></span></button><button class="report-photo-choice" type="button" data-photo-trigger="device-photo"><svg viewBox="0 0 24 24" fill="none"><rect x="4" y="4" width="16" height="16" rx="2"/><circle cx="9" cy="9" r="1.5"/><path d="m5 17 4-4 3 3 2-2 5 5"/></svg><span><strong>Upload a photo</strong><small>Choose from your device</small></span></button></div>
        <input class="visually-hidden" id="camera-photo" name="camera_photo" type="file" accept="image/jpeg,image/png,image/webp" capture="environment">
        <input class="visually-hidden" id="device-photo" name="photograph" type="file" accept="image/jpeg,image/png,image/webp">
        <div class="report-photo-status small text-muted mb-3" id="photo-status">No photograph selected.</div>
        <div class="d-flex gap-2 align-items-center mb-3"><button class="btn btn-outline-success btn-sm" id="use-live-location" type="button">Use my live location</button><div class="small text-muted" id="location-help">No location selected.</div></div>
        <button class="btn btn-success w-100" type="submit">Submit report</button>
    </form>
</div></div><div class="col-lg-7"><div class="portal-panel p-2"><div id="report-map" class="report-map"></div></div></div></div></div></main></div>
<div class="camera-capture" id="camera-capture" aria-hidden="true">
    <div class="camera-capture-card" role="dialog" aria-modal="true" aria-labelledby="camera-capture-title">
        <button class="camera-capture-close" id="close-camera" type="button" aria-label="Close camera">&times;</button>
        <h2 id="camera-capture-title">Capture a photograph</h2>
        <p id="camera-help">Allow camera access, then position the waste concern and take a snapshot.</p>
        <video id="camera-preview" autoplay playsinline></video>
        <canvas id="camera-canvas" class="visually-hidden"></canvas>
        <div class="camera-capture-actions"><button class="portal-secondary-action" id="cancel-camera" type="button">Cancel</button><button class="portal-primary-action" id="take-photo" type="button">Take photo</button></div>
    </div>
</div>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
const map = L.map('report-map').setView([<?= DEFAULT_MAP_LATITUDE ?>, <?= DEFAULT_MAP_LONGITUDE ?>], <?= DEFAULT_MAP_ZOOM ?>);
const mapboxToken = <?= json_encode(MAPBOX_ACCESS_TOKEN, JSON_THROW_ON_ERROR) ?>;
if (mapboxToken) {
    const satelliteStreets = L.tileLayer(`https://api.mapbox.com/styles/v1/mapbox/satellite-streets-v12/tiles/512/{z}/{x}/{y}@2x?access_token=${mapboxToken}`, {maxZoom: 22, tileSize: 512, zoomOffset: -1, attribution: '&copy; Mapbox &copy; OpenStreetMap'});
    const streets = L.tileLayer(`https://api.mapbox.com/styles/v1/mapbox/streets-v12/tiles/512/{z}/{x}/{y}@2x?access_token=${mapboxToken}`, {maxZoom: 22, tileSize: 512, zoomOffset: -1, attribution: '&copy; Mapbox &copy; OpenStreetMap'});
    satelliteStreets.addTo(map);
    L.control.layers({'Satellite + streets': satelliteStreets, 'Street map': streets}, null, {position: 'topright'}).addTo(map);
} else {
    document.getElementById('location-help').textContent = 'Add a restricted Mapbox public token in config/app.php to load satellite imagery.';
}
let marker;
function selectLocation(latitude, longitude) {
    document.getElementById('latitude').value = latitude.toFixed(7);
    document.getElementById('longitude').value = longitude.toFixed(7);
    document.getElementById('location-help').textContent = `Selected location: ${latitude.toFixed(6)}, ${longitude.toFixed(6)}`;
    if (marker) marker.setLatLng([latitude, longitude]); else marker = L.marker([latitude, longitude]).addTo(map);
}
map.on('click', (event) => selectLocation(event.latlng.lat, event.latlng.lng));
document.getElementById('use-live-location').addEventListener('click', () => {
    const button = document.getElementById('use-live-location');
    const help = document.getElementById('location-help');
    if (!navigator.geolocation) {
        help.textContent = 'Live location is not supported by this browser.';
        return;
    }
    button.disabled = true;
    help.textContent = 'Requesting your live location...';
    navigator.geolocation.getCurrentPosition((position) => {
        const latitude = position.coords.latitude;
        const longitude = position.coords.longitude;
        selectLocation(latitude, longitude);
        map.setView([latitude, longitude], 19);
        button.disabled = false;
    }, () => {
        help.textContent = 'Location permission was denied or unavailable. Click the map instead.';
        button.disabled = false;
    }, {enableHighAccuracy: true, timeout: 10000, maximumAge: 60000});
});
<?php if (is_numeric($_POST['latitude'] ?? null) && is_numeric($_POST['longitude'] ?? null)): ?>selectLocation(<?= (float) $_POST['latitude'] ?>, <?= (float) $_POST['longitude'] ?>); map.setView([<?= (float) $_POST['latitude'] ?>, <?= (float) $_POST['longitude'] ?>], 15);<?php endif; ?>
</script>
<script>
document.querySelectorAll('[data-photo-trigger]').forEach((button) => {
    button.addEventListener('click', async () => {
        if (button.dataset.photoTrigger === 'camera-photo') {
            await openCamera();
            return;
        }
        document.getElementById(button.dataset.photoTrigger).click();
    });
});
const cameraDialog = document.getElementById('camera-capture');
const cameraPreview = document.getElementById('camera-preview');
const cameraCanvas = document.getElementById('camera-canvas');
const cameraHelp = document.getElementById('camera-help');
const photoStatus = document.getElementById('photo-status');
let cameraStream;
async function openCamera() {
    if (!navigator.mediaDevices?.getUserMedia) {
        cameraHelp.textContent = 'Camera capture is not supported by this browser. Use Upload a photo instead.';
        cameraDialog.classList.add('is-open');
        cameraDialog.setAttribute('aria-hidden', 'false');
        return;
    }
    cameraDialog.classList.add('is-open');
    cameraDialog.setAttribute('aria-hidden', 'false');
    cameraHelp.textContent = 'Requesting camera access...';
    try {
        cameraStream = await navigator.mediaDevices.getUserMedia({video: {facingMode: {ideal: 'environment'}}, audio: false});
        cameraPreview.srcObject = cameraStream;
        cameraHelp.textContent = 'Position the waste concern, then select Take photo.';
    } catch (error) {
        cameraHelp.textContent = error.name === 'NotAllowedError'
            ? 'Camera access was denied. Allow camera access in your browser or use Upload a photo.'
            : 'The camera could not be opened. Check that it is available, then try again.';
    }
}
function closeCamera() {
    cameraStream?.getTracks().forEach((track) => track.stop());
    cameraStream = null;
    cameraPreview.srcObject = null;
    cameraDialog.classList.remove('is-open');
    cameraDialog.setAttribute('aria-hidden', 'true');
}
document.getElementById('take-photo').addEventListener('click', () => {
    if (!cameraStream || !cameraPreview.videoWidth) {
        cameraHelp.textContent = 'Camera access is not ready yet.';
        return;
    }
    cameraCanvas.width = cameraPreview.videoWidth;
    cameraCanvas.height = cameraPreview.videoHeight;
    cameraCanvas.getContext('2d').drawImage(cameraPreview, 0, 0);
    cameraCanvas.toBlob((blob) => {
        if (!blob) {
            cameraHelp.textContent = 'The photograph could not be captured. Try again.';
            return;
        }
        const file = new File([blob], `smartwaste-${Date.now()}.jpg`, {type: 'image/jpeg'});
        const transfer = new DataTransfer();
        transfer.items.add(file);
        document.getElementById('camera-photo').files = transfer.files;
        document.getElementById('device-photo').value = '';
        photoStatus.textContent = `Captured: ${file.name}`;
        closeCamera();
    }, 'image/jpeg', 0.9);
});
document.getElementById('close-camera').addEventListener('click', closeCamera);
document.getElementById('cancel-camera').addEventListener('click', closeCamera);
document.querySelectorAll('#camera-photo, #device-photo').forEach((input) => input.addEventListener('change', () => {
    if (!input.files.length) return;
    document.querySelectorAll('#camera-photo, #device-photo').forEach((other) => { if (other !== input) other.value = ''; });
    photoStatus.textContent = `Selected: ${input.files[0].name}`;
}));
</script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
