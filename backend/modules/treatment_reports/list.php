<?php
// ===========================================
// Treatment Reports API - List
// GET /treatment_reports/list.php
// ===========================================

require_once __DIR__ . '/../../config/db.php';
require_once dirname(__DIR__, 2) . '/includes/session.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendError('Method not allowed', 405);
}

if (!isLoggedIn()) {
    sendError('Authentication required', 401);
}

$user = getCurrentUser();

$query = '
    SELECT
        tr.*,
        r.status AS referral_status,
        p.first_name AS patient_first_name,
        p.last_name AS patient_last_name,
        sender.first_name AS submitted_by_first_name,
        sender.last_name AS submitted_by_last_name
    FROM treatment_reports tr
    JOIN referrals r ON tr.referral_id = r.id
    JOIN patients p ON r.patient_id = p.id
    JOIN users sender ON tr.submitted_by_user_id = sender.id
';

if ($user['role'] === 'moh') {
    $query .= ' ORDER BY tr.created_at DESC';
    $stmt = $conn->prepare($query);
} elseif ($user['role'] === 'receptionist') {
    $query .= ' WHERE r.receiving_facility_id = ? ORDER BY tr.created_at DESC';
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $user['facility_id']);
} else {
    $query .= ' WHERE r.referring_co_id = ? OR tr.submitted_by_user_id = ? ORDER BY tr.created_at DESC';
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ii', $user['id'], $user['id']);
}

if (!$stmt) {
    sendError('Database error preparing treatment report list', 500);
}
$stmt->execute();
$result = $stmt->get_result();
$reports = [];
while ($row = $result->fetch_assoc()) {
    $row['patient_name'] = trim($row['patient_first_name'] . ' ' . $row['patient_last_name']);
    $row['submitted_by'] = trim($row['submitted_by_first_name'] . ' ' . $row['submitted_by_last_name']);
    unset($row['patient_first_name'], $row['patient_last_name'], $row['submitted_by_first_name'], $row['submitted_by_last_name']);
    $reports[] = $row;
}
$stmt->close();

sendResponse([
    'success' => true,
    'reports' => $reports,
    'count' => count($reports),
]);
?>
