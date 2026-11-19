<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth_check.php';
require_login('admin');
$lgaCommunities = [
    'Abua/Odual' => ['Abua', 'Akinima', 'Emohua', 'Okpeden', 'Ogbema'],
    'Ahoada East' => ['Ahoada', 'Igbuduya', 'Upata', 'Ula-Upata', 'Ubie'],
    'Ahoada West' => ['Akinima', 'Joinkrama', 'Egenema', 'Elele-Alimini', 'Okarki'],
    'Akuku-Toru' => ['Abonnema', 'Buguma', 'Degema', 'Bille', 'Tombia'],
    'Andoni' => ['Ngo', 'Andoni', 'Ataba', 'Bonny Island', 'Unyeada'],
    'Asari-Toru' => ['Buguma', 'Abalama', 'Iwoama', 'Kula', 'Tema'],
    'Bonny' => ['Bonny', 'Finima', 'Ibani', 'Oguede', 'Oloma'],
    'Degema' => ['Degema', 'Bakana', 'Bukuma', 'Tuma', 'Ke'],
    'Eleme' => ['Onne', 'Eleme', 'Akpajo', 'Aleto', 'Ogale'],
    'Emohua' => ['Emohua', 'Rumuji', 'Oduoha', 'Isiodu', 'Ndele'],
    'Etche' => ['Okomoko', 'Elemele', 'Eberi', 'Ndoki', 'Ozuzu'],
    'Gokana' => ['Kpor', 'Bodo', 'K-Dere', 'Bomu', 'Ban-Ogoi'],
    'Ikwerre' => ['Isiokpo', 'Aluu', 'Omademe', 'Omerelu', 'Ibaa'],
    'Khana' => ['Bori', 'Bane', 'Bodo', 'Kono', 'Yeghe'],
    'Obio/Akpor' => ['Rumuokoro', 'Rumuola', 'Rumuodomaya', 'Choba', 'Eliozu', 'Waja'],
    'Ogba/Egbema/Ndoni' => ['Omoku', 'Egbema', 'Egi', 'Ndoni', 'Obrikom'],
    'Ogu/Bolo' => ['Ogu', 'Bolo', 'Wakama', 'Oruama', 'Kula'],
    'Okrika' => ['Okrika', 'Ogoloma', 'Ogan-Ama', 'Oproama', 'Abuloma'],
    'Omuma' => ['Eberi', 'Ofeh', 'Owuahia', 'Obiohia', 'Umuru'],
    'Opobo/Nkoro' => ['Opobo', 'Nkoro', 'Queens Town', 'Kigbene', 'Iwoama'],
    'Oyigbo' => ['Oyigbo', 'Afam', 'Obrikom', 'Komkom', 'Ogu'],
    'Port Harcourt' => ['Old GRA', 'New GRA', 'D-Line', 'Mile 1', 'Mile 2', 'Mile 3', 'Borokiri', 'Diobu', 'Trans Amadi', 'Rumuomasi', 'Rumuola', 'Town'],
    'Tai' => ['Kporghor', 'Bunu', 'Botem', 'Gio', 'Ban-Ogoi'],
];
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!verify_csrf($_POST['csrf_token'] ?? null)) throw new RuntimeException('Your session token is invalid.');
        $action = $_POST['action'] ?? 'create';
        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
        if ($action === 'delete' && $id) {
            db()->prepare('DELETE FROM communities WHERE id = ?')->execute([$id]);
            flash('success', 'Community deleted.');
        } else {
            $lga = trim((string) ($_POST['lga'] ?? ''));
            $name = trim((string) ($_POST['name'] ?? ''));
            $description = trim((string) ($_POST['description'] ?? ''));
            $latitude = filter_var($_POST['latitude'] ?? null, FILTER_VALIDATE_FLOAT);
            $longitude = filter_var($_POST['longitude'] ?? null, FILTER_VALIDATE_FLOAT);
            if (!array_key_exists($lga, $lgaCommunities) || !in_array($name, $lgaCommunities[$lga], true)) throw new RuntimeException('Select a valid LGA and community.');
            if ($latitude === false || $latitude < -90 || $latitude > 90 || $longitude === false || $longitude < -180 || $longitude > 180) throw new RuntimeException('Capture your location or select a valid map location.');
            db()->prepare('INSERT INTO communities (name, lga, description, latitude, longitude) VALUES (?, ?, ?, ?, ?)')->execute([$name, $lga, $description ?: null, $latitude, $longitude]);
            flash('success', 'Community added.');
        }
        redirect(APP_URL . '/admin/communities.php');
    } catch (PDOException $exception) {
        $errors[] = ($exception->errorInfo[1] ?? null) === 1062 ? 'That community already exists.' : 'The community could not be saved.';
    } catch (Throwable $exception) {
        $errors[] = $exception->getMessage();
    }
}
$communities = db()->query('SELECT c.id, c.name, c.description, COUNT(wr.id) AS report_count FROM communities c LEFT JOIN waste_reports wr ON wr.community_id = c.id GROUP BY c.id ORDER BY c.name')->fetchAll();
$pageTitle = 'Manage communities';
$bodyClass = 'resident-portal-page';
$pageClass = 'resident-portal';
$extraHead = '<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">';
require __DIR__ . '/../includes/header.php';
?>
<?php require __DIR__ . '/../includes/portal_start.php'; ?>
<div class="d-flex justify-content-between align-items-center mb-4"><div><h1 class="h3 mb-1">Manage communities</h1><p class="text-muted mb-0">Maintain areas used for reporting and analysis.</p></div><a class="btn btn-outline-success" href="<?= APP_URL ?>/admin/dashboard.php">Dashboard</a></div>
<?php foreach ($errors as $error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endforeach; ?><?php if ($message = flash('success')): ?><div class="alert alert-success"><?= e($message) ?></div><?php endif; ?>
<div class="row g-4"><div class="col-lg-5"><div class="card p-4"><h2 class="h5">Add community</h2><form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><label class="form-label" for="lga">Local government area</label><select class="form-select mb-3" id="lga" name="lga" required><option value="">Select LGA</option><?php foreach ($lgaCommunities as $lga => $names): ?><option value="<?= e($lga) ?>"><?= e($lga) ?></option><?php endforeach; ?></select><label class="form-label" for="name">Community</label><select class="form-select mb-3" id="name" name="name" required disabled><option value="">Select an LGA first</option></select><label class="form-label" for="description">Description</label><textarea class="form-control mb-3" name="description" rows="2"></textarea><input type="hidden" name="latitude" id="latitude"><input type="hidden" name="longitude" id="longitude"><div class="d-flex gap-2 mb-2"><button class="btn btn-outline-success btn-sm" id="use-live-location" type="button">Use my live location</button><button class="btn btn-outline-secondary btn-sm" id="clear-location" type="button">Clear</button></div><div class="small text-muted mb-2" id="location-help">Click the map or use your live location.</div><div id="community-map" class="community-map mb-3"></div><button class="btn btn-success w-100">Add community</button></form></div></div><div class="col-lg-7"><div class="card p-4"><h2 class="h5">Communities</h2><div class="table-responsive"><table class="table"><thead><tr><th>LGA</th><th>Community</th><th>Reports</th><th></th></tr></thead><tbody><?php foreach ($communities as $community): ?><tr><td><?= e($community['lga'] ?? '-') ?></td><td><?= e($community['name']) ?></td><td><?= e($community['report_count']) ?></td><td><form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= e($community['id']) ?>"><button class="btn btn-sm btn-outline-danger">Delete</button></form></td></tr><?php endforeach; ?><?php if (!$communities): ?><tr><td colspan="4" class="text-muted">No communities added yet.</td></tr><?php endif; ?></tbody></table></div></div></div></div>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script><script>
const communityOptions = <?= json_encode($lgaCommunities, JSON_THROW_ON_ERROR) ?>;
const lgaSelect = document.getElementById('lga'), communitySelect = document.getElementById('name');
lgaSelect.addEventListener('change', () => { populateCommunities(lgaSelect.value); });
function populateCommunities(lga, selectedName = '') {
    communitySelect.innerHTML = '<option value="">Select community</option>';
    (communityOptions[lga] || []).forEach((name) => { const option = new Option(name, name); option.selected = name === selectedName; communitySelect.add(option); });
    communitySelect.disabled = !lga;
}
const map = L.map('community-map').setView([<?= DEFAULT_MAP_LATITUDE ?>, <?= DEFAULT_MAP_LONGITUDE ?>], 12); const token = <?= json_encode(MAPBOX_ACCESS_TOKEN, JSON_THROW_ON_ERROR) ?>;
if (token) L.tileLayer(`https://api.mapbox.com/styles/v1/mapbox/streets-v12/tiles/512/{z}/{x}/{y}@2x?access_token=${token}`, {maxZoom: 22, tileSize: 512, zoomOffset: -1, attribution: '&copy; Mapbox &copy; OpenStreetMap'}).addTo(map);
let marker;
function setLocation(lat, lng) { document.getElementById('latitude').value = lat.toFixed(7); document.getElementById('longitude').value = lng.toFixed(7); if (marker) marker.setLatLng([lat, lng]); else marker = L.marker([lat, lng]).addTo(map); }
function normalize(value) { return String(value || '').toLowerCase().replace(/[^a-z0-9]/g, ''); }
async function selectLocationDetails(lat, lng) {
    setLocation(lat, lng);
    map.setView([lat, lng], 18);
    const help = document.getElementById('location-help');
    if (!token) { help.textContent = `Location selected: ${lat.toFixed(6)}, ${lng.toFixed(6)}. Select the LGA and community manually.`; return; }
    help.textContent = 'Location found. Identifying the current LGA and community...';
    try {
        const response = await fetch(`https://api.mapbox.com/geocoding/v5/mapbox.places/${lng},${lat}.json?types=address,neighborhood,locality,place,district,region&limit=10&access_token=${encodeURIComponent(token)}`);
        if (!response.ok) throw new Error('Reverse geocoding failed');
        const data = await response.json();
        const features = Array.isArray(data.features) ? data.features : [];
        const contextValues = features.flatMap((feature) => [feature.text, feature.place_name, ...(feature.context || []).map((item) => item.text)]);
        const matchedLga = Object.keys(communityOptions).find((lga) => contextValues.some((value) => normalize(value).includes(normalize(lga)) || normalize(lga).includes(normalize(value))));
        if (!matchedLga) { help.textContent = 'Exact location selected, but the LGA could not be identified. Please choose it manually.'; return; }
        const candidates = contextValues.map(normalize);
        const matchedCommunity = communityOptions[matchedLga].find((community) => candidates.some((value) => value === normalize(community) || value.includes(normalize(community)) || normalize(community).includes(value)));
        lgaSelect.value = matchedLga;
        populateCommunities(matchedLga, matchedCommunity || '');
        help.textContent = matchedCommunity ? `Selected ${matchedLga} · ${matchedCommunity}.` : `Selected ${matchedLga}. Choose the community that matches the exact location.`;
    } catch (error) {
        help.textContent = 'Exact location selected, but the LGA could not be identified automatically. Please choose it manually.';
    }
}
map.on('click', (event) => { setLocation(event.latlng.lat, event.latlng.lng); document.getElementById('location-help').textContent = `Selected: ${event.latlng.lat.toFixed(6)}, ${event.latlng.lng.toFixed(6)}. Use live location for automatic LGA and community detection.`; });
document.getElementById('clear-location').addEventListener('click', () => { document.getElementById('latitude').value = ''; document.getElementById('longitude').value = ''; lgaSelect.value = ''; populateCommunities(''); document.getElementById('location-help').textContent = 'Click the map or use your live location.'; if (marker) { map.removeLayer(marker); marker = null; } });
document.getElementById('use-live-location').addEventListener('click', () => { const button = document.getElementById('use-live-location'); const help = document.getElementById('location-help'); if (!navigator.geolocation) { help.textContent = 'Live location is not supported by this browser.'; return; } button.disabled = true; help.textContent = 'Requesting your live location...'; navigator.geolocation.getCurrentPosition(async (position) => { await selectLocationDetails(position.coords.latitude, position.coords.longitude); button.disabled = false; }, () => { help.textContent = 'Location permission was denied or unavailable.'; button.disabled = false; }, {enableHighAccuracy: true, timeout: 10000, maximumAge: 60000}); });
</script>
<?php require __DIR__ . '/../includes/portal_end.php'; ?><?php require __DIR__ . '/../includes/footer.php'; ?>
