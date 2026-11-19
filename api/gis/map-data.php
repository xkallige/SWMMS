<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

try {
    $status = trim((string) ($_GET['status'] ?? ''));
    $categoryId = filter_var($_GET['category_id'] ?? null, FILTER_VALIDATE_INT);
    $allowedStatuses = ['pending', 'under_review', 'assigned', 'in_progress', 'resolved', 'rejected', 'duplicate'];
    $conditions = [];
    $parameters = [];
    if (in_array($status, $allowedStatuses, true)) {
        $conditions[] = 'wr.status = ?';
        $parameters[] = $status;
    }
    if ($categoryId) {
        $conditions[] = 'wr.category_id = ?';
        $parameters[] = $categoryId;
    }
    $where = $conditions ? ' WHERE ' . implode(' AND ', $conditions) : '';
    $reportStatement = db()->prepare(
        'SELECT wr.id, wr.latitude, wr.longitude, wr.description, wr.status, wr.created_at,
                wc.id AS category_id, wc.name AS category, c.name AS community
         FROM waste_reports wr INNER JOIN waste_categories wc ON wc.id = wr.category_id
         LEFT JOIN communities c ON c.id = wr.community_id' . $where . ' ORDER BY wr.created_at DESC LIMIT 1000'
    );
    $reportStatement->execute($parameters);
    $facilityStatement = db()->query(
        'SELECT f.id, f.name, f.facility_type, f.description, f.address, f.latitude, f.longitude,
                f.operational_status, c.name AS community
         FROM facilities f LEFT JOIN communities c ON c.id = f.community_id
         ORDER BY f.name LIMIT 1000'
    );
    echo json_encode([
        'reports' => $reportStatement->fetchAll(),
        'facilities' => $facilityStatement->fetchAll(),
    ], JSON_THROW_ON_ERROR);
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode(['error' => 'Map data could not be loaded.'], JSON_THROW_ON_ERROR);
}
