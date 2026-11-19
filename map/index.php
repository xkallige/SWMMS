<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_login();
$pageTitle = 'Portal map';
$isPortal = current_user() !== null;
$bodyClass = $isPortal ? 'resident-portal-page' : '';
$pageClass = $isPortal ? 'resident-portal' : 'container py-4';
$extraHead = '<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">';
require __DIR__ . '/../includes/header.php';
?>
<?php if ($isPortal): ?><div class="portal-shell"><?php require __DIR__ . '/../includes/portal_sidebar.php'; ?><button class="portal-menu-toggle" type="button" aria-controls="portalSidebar" aria-expanded="false"><svg viewBox="0 0 24 24" fill="none"><path d="M4 7h16M4 12h16M4 17h16"/></svg><span>Menu</span></button><div class="portal-sidebar-backdrop" data-portal-close></div><main class="portal-main"><header class="portal-topbar"><div><strong>Resident portal</strong><small>Community reporting and progress tracking</small></div><div class="portal-user"><span><?= e(strtoupper(substr(current_user()['full_name'], 0, 1))) ?></span><div><strong><?= e(current_user()['full_name']) ?></strong><small><?= e(ucfirst((string) current_user()['role'])) ?> account</small></div></div></header><div class="portal-content"><?php endif; ?>
<div class="portal-heading"><div><div class="portal-date"><?= e(strtoupper(date('l, j F'))) ?></div><h1>SmartWaste map</h1><p>Live waste reports and facilities across Port Harcourt.</p></div><span class="portal-map-count" id="map-count">Loading map data...</span></div>
<section class="portal-panel map-filter-panel"><div class="row g-3 align-items-end"><div class="col-md-4"><label class="form-label" for="category-filter">Waste category</label><select class="form-select" id="category-filter"><option value="">All categories</option><?php foreach (db()->query('SELECT id, name FROM waste_categories WHERE is_active = 1 ORDER BY name') as $category): ?><option value="<?= e($category['id']) ?>"><?= e($category['name']) ?></option><?php endforeach; ?></select></div><div class="col-md-4"><label class="form-label" for="status-filter">Report status</label><select class="form-select" id="status-filter"><option value="">All statuses</option><?php foreach (['pending','under_review','assigned','in_progress','resolved','rejected','duplicate'] as $status): ?><option value="<?= e($status) ?>"><?= e(report_status_label($status)) ?></option><?php endforeach; ?></select></div><div class="col-md-2"><button class="portal-primary-action w-100 justify-content-center" id="refresh-map" type="button">Apply filters</button></div><div class="col-md-2"><button class="portal-secondary-action w-100" id="use-map-location" type="button">My location</button></div></div><div class="small text-muted mt-3" id="location-help">Map starts at Port Harcourt. Use “My location” to center on your current GPS position.</div></section>
<section class="portal-panel p-2 mt-3"><div id="public-map" class="public-map"></div></section>
<div class="d-flex gap-3 small text-muted mt-2"><span><span class="legend-dot bg-danger"></span> Reports</span><span><span class="legend-dot bg-success"></span> Facilities</span></div>
<?php if ($isPortal): ?></div></main></div><?php endif; ?>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
const map = L.map('public-map').setView([<?= DEFAULT_MAP_LATITUDE ?>, <?= DEFAULT_MAP_LONGITUDE ?>], Math.max(<?= DEFAULT_MAP_ZOOM ?>, 13));
const mapboxToken = <?= json_encode(MAPBOX_ACCESS_TOKEN, JSON_THROW_ON_ERROR) ?>;
if (mapboxToken) {
    const satelliteStreets = L.tileLayer(`https://api.mapbox.com/styles/v1/mapbox/satellite-streets-v12/tiles/512/{z}/{x}/{y}@2x?access_token=${mapboxToken}`, {maxZoom: 22, tileSize: 512, zoomOffset: -1, attribution: '&copy; Mapbox &copy; OpenStreetMap'});
    const streets = L.tileLayer(`https://api.mapbox.com/styles/v1/mapbox/streets-v12/tiles/512/{z}/{x}/{y}@2x?access_token=${mapboxToken}`, {maxZoom: 22, tileSize: 512, zoomOffset: -1, attribution: '&copy; Mapbox &copy; OpenStreetMap'});
    satelliteStreets.addTo(map);
    L.control.layers({'Satellite + streets': satelliteStreets, 'Street map': streets}, null, {position: 'topright'}).addTo(map);
} else {
    document.getElementById('map-count').textContent = 'Mapbox token required';
    document.getElementById('location-help').textContent = 'Add a restricted Mapbox public token in config/app.php to load satellite imagery.';
}
const reportLayer = L.layerGroup().addTo(map);
const facilityLayer = L.layerGroup().addTo(map);
L.control.layers(null, {'Reported problems': reportLayer, 'Waste facilities': facilityLayer}).addTo(map);
const reportIcon = L.divIcon({className: 'report-marker', html: '<span></span>', iconSize: [18, 18], iconAnchor: [9, 9]});
const facilityIcon = L.divIcon({className: 'facility-marker', html: '<span></span>', iconSize: [18, 18], iconAnchor: [9, 9]});
function escapeHtml(value) { const div = document.createElement('div'); div.textContent = value ?? ''; return div.innerHTML; }
async function loadMap() {
    const params = new URLSearchParams();
    const category = document.getElementById('category-filter').value;
    const status = document.getElementById('status-filter').value;
    if (category) params.set('category_id', category);
    if (status) params.set('status', status);
    const response = await fetch('<?= APP_URL ?>/api/gis/map-data.php?' + params.toString());
    if (!response.ok) throw new Error('Map data request failed');
    const data = await response.json();
    reportLayer.clearLayers(); facilityLayer.clearLayers();
    data.reports.forEach(report => L.marker([report.latitude, report.longitude], {icon: reportIcon}).bindPopup(`<strong>Report #${escapeHtml(report.id)}</strong><br>${escapeHtml(report.category)}<br><span class="badge text-bg-secondary">${escapeHtml(report.status.replaceAll('_', ' '))}</span><p class="mb-0 mt-2">${escapeHtml(report.description)}</p>`).addTo(reportLayer));
    data.facilities.forEach(facility => L.marker([facility.latitude, facility.longitude], {icon: facilityIcon}).bindPopup(`<strong>${escapeHtml(facility.name)}</strong><br>${escapeHtml(facility.facility_type.replaceAll('_', ' '))}<br>${escapeHtml(facility.address || facility.community || 'No address provided')}`).addTo(facilityLayer));
    document.getElementById('map-count').textContent = `${data.reports.length} reports · ${data.facilities.length} facilities`;
}
document.getElementById('refresh-map').addEventListener('click', () => loadMap().catch(() => { document.getElementById('map-count').textContent = 'Map data unavailable'; }));
document.getElementById('use-map-location').addEventListener('click', () => {
    const help = document.getElementById('location-help');
    if (!navigator.geolocation) {
        help.textContent = 'Live location is not supported by this browser.';
        return;
    }
    help.textContent = 'Requesting your live location...';
    navigator.geolocation.getCurrentPosition((position) => {
        map.setView([position.coords.latitude, position.coords.longitude], 19);
        help.textContent = `Showing your location: ${position.coords.latitude.toFixed(6)}, ${position.coords.longitude.toFixed(6)}`;
    }, () => {
        help.textContent = 'Location permission was denied or unavailable.';
    }, {enableHighAccuracy: true, timeout: 10000, maximumAge: 60000});
});
loadMap().catch(() => { document.getElementById('map-count').textContent = 'Map data unavailable'; });
</script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
