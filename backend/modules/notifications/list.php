<?php
// ===========================================
// Notifications API - List Notifications
// GET /notifications/list.php
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

$query = "
    SELECT
        n.id,
        n.type,
        n.recipient_email,
        n.recipient_phone,
        n.subject,
        n.message,
        n.sent_at,
        n.status,
        r.id AS referral_id,
        p.first_name AS patient_first_name,
        p.last_name AS patient_last_name
    FROM notifications n
    LEFT JOIN referrals r ON n.referral_id = r.id
    LEFT JOIN patients p ON r.patient_id = p.id
";

if ($user['role'] === 'moh') {
    $query .= " ORDER BY n.sent_at DESC";
    $stmt = $conn->prepare($query);
} elseif ($user['role'] === 'receptionist') {
    $query .= " WHERE r.referring_facility_id = ? OR r.receiving_facility_id = ? ORDER BY n.sent_at DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ii', $user['facility_id'], $user['facility_id']);
} else {
    $query .= " WHERE r.referring_co_id = ? ORDER BY n.sent_at DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $user['id']);
}

$stmt->execute();
$result = $stmt->get_result();
$notifications = [];
while ($row = $result->fetch_assoc()) {
    $row['patient_name'] = trim($row['patient_first_name'] . ' ' . $row['patient_last_name']);
    unset($row['patient_first_name'], $row['patient_last_name']);
    $notifications[] = $row;
}

sendResponse([
    'success' => true,
    'notifications' => $notifications,
    'count' => count($notifications),
]);
?>