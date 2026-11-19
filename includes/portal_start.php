<?php
$portalUser = current_user();
$portalRoleLabel = ($portalUser['role'] ?? '') === 'admin' ? 'Administrator' : ucfirst((string) ($portalUser['role'] ?? 'Resident'));
$portalDescription = ($portalUser['role'] ?? '') === 'admin' ? 'Platform oversight and operations control' : 'Field operations and collection response';
?>
<div class="portal-shell"><?php require __DIR__ . '/portal_sidebar.php'; ?><button class="portal-menu-toggle" type="button" aria-controls="portalSidebar" aria-expanded="false"><svg viewBox="0 0 24 24" fill="none"><path d="M4 7h16M4 12h16M4 17h16"/></svg><span>Menu</span></button><div class="portal-sidebar-backdrop" data-portal-close></div><main class="portal-main"><header class="portal-topbar"><div><strong><?= e($portalRoleLabel) ?> portal</strong><small><?= e($portalDescription) ?></small></div><div class="portal-user"><span><?= e(strtoupper(substr($portalUser['full_name'], 0, 1))) ?></span><div><strong><?= e($portalUser['full_name']) ?></strong><small><?= e($portalRoleLabel) ?> account</small></div></div></header><div class="portal-content">
