<?php $user = current_user(); ?>
<nav class="navbar navbar-expand-lg navbar-light site-navbar sticky-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2 fw-bold" href="<?= APP_URL ?>/"><span class="brand-mark">S</span><span>SmartWaste <span class="brand-muted">GIS</span></span></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
                <?php if (($pageClass ?? '') === 'home-page'): ?><li class="nav-item"><a class="nav-link" href="#services">Services</a></li><li class="nav-item"><a class="nav-link" href="#infrastructure">Infrastructure</a></li><li class="nav-item"><a class="nav-link" href="#updates">Updates</a></li><li class="nav-item"><a class="nav-link" href="#faq">FAQs</a></li><?php endif; ?>
                <?php if ($user): ?>
                    <li class="nav-item"><a class="nav-link" href="<?= e(role_home($user['role'])) ?>">Dashboard</a></li>
                    <?php if ($user['role'] === 'admin'): ?><li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/admin/reports.php">Reports</a></li><li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/admin/users.php">Users</a></li><?php elseif ($user['role'] === 'officer'): ?><li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/officer/assigned-tasks.php">Tasks</a></li><?php endif; ?>
                    <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/notifications.php">Notifications<?php $unreadCount = unread_notification_count((int) $user['id']); ?><?php if ($unreadCount > 0): ?> <span class="badge rounded-pill bg-warning text-dark"><?= $unreadCount ?></span><?php endif; ?></a></li>
                    <li class="nav-item"><a class="nav-portal nav-portal-dark" href="<?= APP_URL ?>/logout.php">Log out <span aria-hidden="true">→</span></a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/?auth=login">Log in</a></li>
                    <li class="nav-item"><a class="nav-portal" href="<?= APP_URL ?>/?auth=register">Join the network <span aria-hidden="true">→</span></a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
