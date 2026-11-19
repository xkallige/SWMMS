<?php
$portalUser = current_user();
$portalRole = $portalUser['role'] ?? 'resident';
$portalPath = basename($_SERVER['PHP_SELF']);
$portalLinks = match ($portalRole) {
    'admin' => [
        ['admin/dashboard.php', 'Overview', 'grid'],
        ['admin/reports.php', 'Reports', 'clipboard'],
        ['admin/users.php', 'Users', 'users'],
        ['admin/facilities.php', 'Facilities', 'map'],
        ['admin/communities.php', 'Communities', 'layers'],
        ['admin/categories.php', 'Categories', 'tag'],
        ['admin/collections.php', 'Collections', 'check'],
    ],
    'officer' => [
        ['officer/dashboard.php', 'Overview', 'grid'],
        ['officer/assigned-tasks.php', 'Assigned tasks', 'clipboard'],
        ['officer/collection-records.php', 'Collection records', 'check'],
        ['notifications.php', 'Notifications', 'bell'],
    ],
    default => [
        ['resident/dashboard.php', 'Overview', 'grid'],
        ['resident/report-waste.php', 'Report waste', 'plus'],
        ['resident/my-reports.php', 'My reports', 'clipboard'],
        ['notifications.php', 'Notifications', 'bell'],
    ],
};
?>
<aside class="portal-sidebar" id="portalSidebar">
    <a class="portal-brand" href="<?= APP_URL ?>/"><span class="brand-mark">S</span><span>SmartWaste<br><small><?= e(ucfirst($portalRole)) ?> portal</small></span></a>
    <nav class="portal-nav" aria-label="Portal navigation">
        <?php foreach ($portalLinks as [$path, $label, $icon]): ?>
            <?php $active = basename(rtrim($path, '/')) === $portalPath; ?>
            <a class="<?= $active ? 'is-active' : '' ?>" href="<?= APP_URL ?>/<?= $path ?>">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><?php if ($icon === 'grid'): ?><rect x="4" y="4" width="6" height="6" rx="1"/><rect x="14" y="4" width="6" height="6" rx="1"/><rect x="4" y="14" width="6" height="6" rx="1"/><rect x="14" y="14" width="6" height="6" rx="1"/><?php elseif ($icon === 'plus'): ?><path d="M12 5v14M5 12h14"/><?php elseif ($icon === 'clipboard'): ?><path d="M6 4h12v16H6zM9 8h6M9 12h6M9 16h4"/><?php elseif ($icon === 'map'): ?><path d="m3 6 6-2 6 2 6-2v14l-6 2-6-2-6 2V6Z"/><path d="M9 4v14M15 6v14"/><?php elseif ($icon === 'bell'): ?><path d="M18 9a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9ZM10 21h4"/><?php elseif ($icon === 'users'): ?><circle cx="9" cy="8" r="3"/><path d="M3.5 19a5.5 5.5 0 0 1 11 0M16 11v6M13 14h6"/><?php elseif ($icon === 'layers'): ?><path d="m12 4 8 4-8 4-8-4 8-4Z"/><path d="m4 12 8 4 8-4M4 16l8 4 8-4"/><?php elseif ($icon === 'tag'): ?><path d="M4 5v6l9 9 7-7-9-9H4Z"/><circle cx="8" cy="8" r="1"/><?php else: ?><path d="m5 12 4 4L19 6"/><?php endif; ?></svg>
                <?= e($label) ?>
            </a>
        <?php endforeach; ?>
    </nav>
    <div class="portal-sidebar-bottom">
        <a href="<?= APP_URL ?>/"><svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9"/><path d="M12 8v4l3 2"/></svg>Return to website</a>
        <a href="<?= APP_URL ?>/logout.php"><svg viewBox="0 0 24 24" fill="none"><path d="M10 5H5v14h5M14 8l4 4-4 4M8 12h10"/></svg>Log out</a>
    </div>
</aside>
