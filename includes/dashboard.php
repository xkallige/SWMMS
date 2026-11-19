<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';

function report_counts_for_user(int $userId): array
{
    $counts = array_fill_keys(['total', 'pending', 'under_review', 'resolved'], 0);
    $statement = db()->prepare('SELECT status, COUNT(*) AS total FROM waste_reports WHERE reported_by = ? GROUP BY status');
    $statement->execute([$userId]);
    foreach ($statement as $row) {
        $counts['total'] += (int) $row['total'];
        if (array_key_exists($row['status'], $counts)) {
            $counts[$row['status']] = (int) $row['total'];
        }
    }
    return $counts;
}

function recent_reports_for_user(int $userId, int $limit = 5): array
{
    $statement = db()->prepare(
        'SELECT wr.id, wc.name AS category, wr.status, wr.created_at
         FROM waste_reports wr
         INNER JOIN waste_categories wc ON wc.id = wr.category_id
         WHERE wr.reported_by = ?
         ORDER BY wr.created_at DESC LIMIT ' . $limit
    );
    $statement->execute([$userId]);
    return $statement->fetchAll();
}

function admin_dashboard_data(): array
{
    $data = [];
    $data['users'] = (int) db()->query('SELECT COUNT(*) FROM users')->fetchColumn();
    $data['residents'] = (int) db()->query("SELECT COUNT(*) FROM users WHERE role = 'resident'")->fetchColumn();
    $data['officers'] = (int) db()->query("SELECT COUNT(*) FROM users WHERE role = 'officer'")->fetchColumn();
    $data['reports'] = (int) db()->query('SELECT COUNT(*) FROM waste_reports')->fetchColumn();
    $data['pending'] = (int) db()->query("SELECT COUNT(*) FROM waste_reports WHERE status = 'pending'")->fetchColumn();
    $data['resolved'] = (int) db()->query("SELECT COUNT(*) FROM waste_reports WHERE status = 'resolved'")->fetchColumn();
    $data['categories'] = db()->query(
        'SELECT wc.name, COUNT(wr.id) AS total
         FROM waste_categories wc
         LEFT JOIN waste_reports wr ON wr.category_id = wc.id
         GROUP BY wc.id, wc.name ORDER BY total DESC, wc.name'
    )->fetchAll();
    $data['recent_reports'] = db()->query(
        'SELECT wr.id, wr.status, wr.created_at, u.full_name, wc.name AS category
         FROM waste_reports wr
         INNER JOIN users u ON u.id = wr.reported_by
         INNER JOIN waste_categories wc ON wc.id = wr.category_id
         ORDER BY wr.created_at DESC LIMIT 8'
    )->fetchAll();
    $data['activities'] = db()->query(
        'SELECT action, entity_type, created_at FROM system_activity_logs ORDER BY created_at DESC LIMIT 5'
    )->fetchAll();
    return $data;
}

function officer_dashboard_data(int $officerId): array
{
    $statement = db()->prepare(
        'SELECT status, COUNT(*) AS total FROM collection_tasks WHERE officer_id = ? GROUP BY status'
    );
    $statement->execute([$officerId]);
    $counts = array_fill_keys(['assigned', 'pending', 'in_progress', 'completed'], 0);
    foreach ($statement as $row) {
        if ($row['status'] === 'pending') {
            $counts['assigned'] = (int) $row['total'];
        }
        if (array_key_exists($row['status'], $counts)) {
            $counts[$row['status']] = (int) $row['total'];
        }
    }
    $counts['total'] = array_sum($counts) - $counts['assigned'];
    $recent = db()->prepare(
        'SELECT ct.id, ct.status, ct.assigned_at, wr.id AS report_id, wc.name AS category
         FROM collection_tasks ct
         INNER JOIN waste_reports wr ON wr.id = ct.report_id
         INNER JOIN waste_categories wc ON wc.id = wr.category_id
         WHERE ct.officer_id = ?
         ORDER BY ct.assigned_at DESC LIMIT 8'
    );
    $recent->execute([$officerId]);
    return ['counts' => $counts, 'recent_tasks' => $recent->fetchAll()];
}
