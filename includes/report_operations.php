<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';

function valid_report_status(string $status): bool
{
    return in_array($status, ['pending', 'under_review', 'assigned', 'in_progress', 'resolved', 'rejected', 'duplicate'], true);
}

function update_report_status(int $reportId, string $newStatus, int $userId, string $remarks = ''): void
{
    if (!valid_report_status($newStatus)) {
        throw new InvalidArgumentException('Invalid report status.');
    }

    $connection = db();
    $connection->beginTransaction();
    try {
        $reportStatement = $connection->prepare('SELECT reported_by, status FROM waste_reports WHERE id = ? FOR UPDATE');
        $reportStatement->execute([$reportId]);
        $report = $reportStatement->fetch();
        if (!$report) {
            throw new RuntimeException('Report not found.');
        }
        if ($report['status'] === $newStatus && trim($remarks) === '') {
            $connection->commit();
            return;
        }
        record_report_status($connection, $reportId, $report['reported_by'], $report['status'], $newStatus, $userId, $remarks);
        $connection->commit();
    } catch (Throwable $exception) {
        if ($connection->inTransaction()) {
            $connection->rollBack();
        }
        throw $exception;
    }
}

function record_report_status(PDO $connection, int $reportId, int $residentId, string $oldStatus, string $newStatus, int $userId, string $remarks): void
{
    $update = $connection->prepare('UPDATE waste_reports SET status = ? WHERE id = ?');
    $update->execute([$newStatus, $reportId]);
    $history = $connection->prepare(
        'INSERT INTO report_updates (report_id, changed_by, old_status, new_status, remarks) VALUES (?, ?, ?, ?, ?)'
    );
    $history->execute([$reportId, $userId, $oldStatus, $newStatus, $remarks ?: null]);
    $notification = $connection->prepare(
        'INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)'
    );
    $notification->execute([
        $residentId,
        'Report status updated',
        'Report #' . $reportId . ' is now ' . report_status_label($newStatus) . '.',
    ]);
    $activity = $connection->prepare(
        'INSERT INTO system_activity_logs (user_id, action, entity_type, entity_id, ip_address) VALUES (?, ?, ?, ?, ?)'
    );
    $activity->execute([$userId, 'updated_report_status', 'waste_report', $reportId, $_SERVER['REMOTE_ADDR'] ?? null]);
}

function assign_report(int $reportId, int $officerId, int $adminId): void
{
    $connection = db();
    $connection->beginTransaction();
    try {
        $reportStatement = $connection->prepare('SELECT reported_by, status FROM waste_reports WHERE id = ? FOR UPDATE');
        $reportStatement->execute([$reportId]);
        $report = $reportStatement->fetch();
        $officerStatement = $connection->prepare("SELECT id FROM users WHERE id = ? AND role = 'officer' AND is_active = 1");
        $officerStatement->execute([$officerId]);
        if (!$report || !$officerStatement->fetch()) {
            throw new RuntimeException('Report or officer not found.');
        }
        $existing = $connection->prepare('SELECT id FROM collection_tasks WHERE report_id = ? AND status IN ("pending", "in_progress") LIMIT 1');
        $existing->execute([$reportId]);
        if ($existing->fetch()) {
            throw new RuntimeException('This report already has an active task.');
        }
        $update = $connection->prepare('UPDATE waste_reports SET assigned_officer_id = ?, status = "assigned" WHERE id = ?');
        $update->execute([$officerId, $reportId]);
        $task = $connection->prepare('INSERT INTO collection_tasks (report_id, officer_id) VALUES (?, ?)');
        $task->execute([$reportId, $officerId]);
        $history = $connection->prepare(
            'INSERT INTO report_updates (report_id, changed_by, old_status, new_status, remarks) VALUES (?, ?, ?, "assigned", ?)'
        );
        $history->execute([$reportId, $adminId, $report['status'], 'Assigned to waste management officer.']);
        $notification = $connection->prepare('INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)');
        $notification->execute([$officerId, 'New report assignment', 'You have been assigned report #' . $reportId . '.']);
        $notification->execute([$report['reported_by'], 'Report assigned', 'Report #' . $reportId . ' has been assigned for action.']);
        $activity = $connection->prepare(
            'INSERT INTO system_activity_logs (user_id, action, entity_type, entity_id, ip_address) VALUES (?, ?, ?, ?, ?)'
        );
        $activity->execute([$adminId, 'assigned_report', 'waste_report', $reportId, $_SERVER['REMOTE_ADDR'] ?? null]);
        $connection->commit();
    } catch (Throwable $exception) {
        if ($connection->inTransaction()) {
            $connection->rollBack();
        }
        throw $exception;
    }
}

function update_task_status(int $taskId, int $officerId, string $status): void
{
    if (!in_array($status, ['in_progress', 'completed', 'cancelled'], true)) {
        throw new InvalidArgumentException('Invalid task status.');
    }
    $connection = db();
    $connection->beginTransaction();
    try {
        $taskStatement = $connection->prepare('SELECT report_id FROM collection_tasks WHERE id = ? AND officer_id = ? FOR UPDATE');
        $taskStatement->execute([$taskId, $officerId]);
        $task = $taskStatement->fetch();
        if (!$task) {
            throw new RuntimeException('Task not found.');
        }
        $completedAt = $status === 'completed' ? date('Y-m-d H:i:s') : null;
        $update = $connection->prepare('UPDATE collection_tasks SET status = ?, completed_at = ? WHERE id = ? AND officer_id = ?');
        $update->execute([$status, $completedAt, $taskId, $officerId]);
        $reportStatus = match ($status) {
            'in_progress' => 'in_progress',
            'completed' => 'resolved',
            default => 'rejected',
        };
        $reportStatement = $connection->prepare('SELECT reported_by, status FROM waste_reports WHERE id = ? FOR UPDATE');
        $reportStatement->execute([$task['report_id']]);
        $report = $reportStatement->fetch();
        if (!$report) {
            throw new RuntimeException('Related report not found.');
        }
        record_report_status($connection, (int) $task['report_id'], (int) $report['reported_by'], $report['status'], $reportStatus, $officerId, 'Collection task marked ' . report_status_label($status) . '.');
        $connection->commit();
    } catch (Throwable $exception) {
        if ($connection->inTransaction()) {
            $connection->rollBack();
        }
        throw $exception;
    }
}
