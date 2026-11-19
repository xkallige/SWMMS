<?php

declare(strict_types=1);
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/dashboard.php';
require_login('officer');
$data = officer_dashboard_data((int) current_user()['id']);
$pageTitle = 'Officer dashboard';
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
            <div><strong>Officer portal</strong><small>Field operations and collection response</small></div>
            <div class="portal-user"><span><?= e(strtoupper(substr(current_user()['full_name'], 0, 1))) ?></span>
                <div><strong><?= e(current_user()['full_name']) ?></strong><small>Officer account</small></div>
            </div>
        </header>
        <div class="portal-content">
            <div class="portal-heading">
                <div>
                    <div class="portal-date"><?= e(strtoupper(date('l, j F'))) ?></div>
                    <h1>Good day, <?= e((string) preg_split('/\s+/', current_user()['full_name'])[0]) ?>.</h1>
                    <p>Review assignments and keep collection progress moving.</p>
                </div><a class="portal-primary-action" href="<?= APP_URL ?>/officer/collection-records.php"><span>+</span> Record activity <b>→</b></a>
            </div>
            <div class="portal-stat-grid portal-stat-grid-3"><?php foreach (['assigned' => 'Assigned tasks', 'in_progress' => 'In progress', 'completed' => 'Completed'] as $key => $label): ?><div class="portal-stat-card"><span class="portal-stat-icon <?= $key === 'completed' ? 'mint' : ($key === 'in_progress' ? 'blue' : 'gold') ?>"><svg viewBox="0 0 24 24" fill="none">
                                <path d="M5 4h14v16H5zM8 8h8M8 12h6M8 16h4" />
                            </svg></span><small><?= $label ?></small><strong><?= e($data['counts'][$key]) ?></strong><em>Current workload</em></div><?php endforeach; ?></div>
            <section class="portal-panel">
                <div class="portal-panel-heading">
                    <div>
                        <h2>Recent assignments</h2>
                        <p>Your latest field operations</p>
                    </div><a href="<?= APP_URL ?>/officer/assigned-tasks.php">View all <span>→</span></a>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Task</th>
                                <th>Report</th>
                                <th>Category</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody><?php foreach ($data['recent_tasks'] as $task): ?><tr>
                                    <td>#<?= e($task['id']) ?></td>
                                    <td>#<?= e($task['report_id']) ?></td>
                                    <td><?= e($task['category']) ?></td>
                                    <td><span class="badge text-bg-<?= e($task['status'] === 'completed' ? 'success' : 'primary') ?>"><?= e(report_status_label($task['status'])) ?></span></td>
                                </tr><?php endforeach; ?><?php if (!$data['recent_tasks']): ?><tr>
                                    <td colspan="4" class="text-muted">No tasks assigned yet.</td>
                                </tr><?php endif; ?></tbody>
                    </table>
                </div>
            </section>
        </div>
    </main>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>