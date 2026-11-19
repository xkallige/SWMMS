<?php

declare(strict_types=1);
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/dashboard.php';
require_login('admin');
$data = admin_dashboard_data();
$pageTitle = 'Administrator dashboard';
$bodyClass = 'resident-portal-page';
$pageClass = 'resident-portal';
require __DIR__ . '/../includes/header.php';
?>
<div class="portal-shell"><?php require __DIR__ . '/../includes/portal_sidebar.php'; ?><button class="portal-menu-toggle" type="button" aria-controls="portalSidebar" aria-expanded="false"><svg viewBox="0 0 24 24" fill="none">
            <path d="M4 7h16M4 12h16M4 17h16" />
        </svg><span>Menu</span></button>
    <div class="portal-sidebar-backdrop" data-portal-close></div>
    <main class="portal-main">
        <header class="portal-topbar">
            <div><strong>Administrator portal</strong><small>Platform oversight and operations control</small></div>
            <div class="portal-user"><span><?= e(strtoupper(substr(current_user()['full_name'], 0, 1))) ?></span>
                <div><strong><?= e(current_user()['full_name']) ?></strong><small>Administrator account</small></div>
            </div>
        </header>
        <div class="portal-content">
            <div class="portal-heading">
                <div>
                    <div class="portal-date"><?= e(strtoupper(date('l, j F'))) ?></div>
                    <h1>Good day, <?= e((string) preg_split('/\s+/', current_user()['full_name'])[0]) ?>.</h1>
                    <p>Monitor reports, people, and waste operations from one place.</p>
                </div><a class="portal-primary-action" href="<?= APP_URL ?>/admin/reports.php"><span>→</span> Review reports <b>→</b></a>
            </div>
            <div class="portal-stat-grid portal-stat-grid-3"><?php foreach (['users' => 'Users', 'reports' => 'Reports', 'pending' => 'Pending reports', 'resolved' => 'Resolved reports'] as $key => $label): ?><div class="portal-stat-card"><span class="portal-stat-icon <?= $key === 'resolved' ? 'mint' : ($key === 'pending' ? 'gold' : 'green') ?>"><svg viewBox="0 0 24 24" fill="none">
                                <path d="M5 4h14v16H5zM8 8h8M8 12h6M8 16h4" />
                            </svg></span><small><?= $label ?></small><strong><?= e($data[$key]) ?></strong><em>Platform overview</em></div><?php endforeach; ?></div>
            <div class="portal-dashboard-grid">
                <section class="portal-panel">
                    <div class="portal-panel-heading">
                        <div>
                            <h2>Recent reports</h2>
                            <p>Latest community submissions</p>
                        </div><a href="<?= APP_URL ?>/admin/reports.php">View all <span>→</span></a>
                    </div>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Report</th>
                                    <th>Resident</th>
                                    <th>Category</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($data['recent_reports'] as $report): ?><tr>
                                        <td>#<?= e($report['id']) ?></td>
                                        <td><?= e($report['full_name']) ?></td>
                                        <td><?= e($report['category']) ?></td>
                                        <td><span class="badge text-bg-<?= e(report_status_class($report['status'])) ?>"><?= e(report_status_label($report['status'])) ?></span></td>
                                    </tr><?php endforeach; ?>
                                <?php if (!$data['recent_reports']): ?><tr>
                                        <td colspan="4" class="text-muted">No reports submitted yet.</td>
                                    </tr><?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
                <section class="portal-panel">
                    <div class="portal-panel-heading">
                        <div>
                            <h2>Reports by category</h2>
                            <p>Where concerns are concentrated</p>
                        </div>
                    </div><?php foreach ($data['categories'] as $category): ?><div class="d-flex justify-content-between border-bottom py-2"><span><?= e($category['name']) ?></span><strong><?= e($category['total']) ?></strong></div><?php endforeach; ?>
                </section>
            </div>
        </div>
    </main>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>