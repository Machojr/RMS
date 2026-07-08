<?php
// ===========================================
// Feedback API - List Feedback
// GET /feedback/list.php
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
        fb.id,
        fb.department,
        fb.referral_serial_no,
        fb.referral_diagnosis,
        fb.confirmed_diagnosis,
        fb.comments,
        fb.clinical_outcome,
        fb.treatment_given,
        fb.discharge_summary,
        fb.follow_up_instructions,
        fb.sent_at,
        r.id AS referral_id,
        r.status AS referral_status,
        p.first_name AS patient_first_name,
        p.last_name AS patient_last_name,
        u.first_name AS sent_by_first_name,
        u.last_name AS sent_by_last_name
    FROM feedback fb
    JOIN referrals r ON fb.referral_id = r.id
    JOIN patients p ON r.patient_id = p.id
    JOIN users u ON fb.sent_by_receptionist_id = u.id
";

if ($user['role'] === 'moh') {
    $query .= " ORDER BY fb.sent_at DESC";
    $stmt = $conn->prepare($query);
} elseif ($user['role'] === 'receptionist') {
    $query .= " WHERE r.referring_facility_id = ? OR r.receiving_facility_id = ? ORDER BY fb.sent_at DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ii', $user['facility_id'], $user['facility_id']);
} else {
    $query .= " WHERE r.referring_co_id = ? ORDER BY fb.sent_at DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $user['id']);
}

$stmt->execute();
$result = $stmt->get_result();
$feedback = [];
while ($row = $result->fetch_assoc()) {
    $row['patient_name'] = trim($row['patient_first_name'] . ' ' . $row['patient_last_name']);
    $row['sent_by'] = trim($row['sent_by_first_name'] . ' ' . $row['sent_by_last_name']);
    unset($row['patient_first_name'], $row['patient_last_name'], $row['sent_by_first_name'], $row['sent_by_last_name']);
    $feedback[] = $row;
}

sendResponse([
    'success' => true,
    'feedback' => $feedback,
    'count' => count($feedback),
]);
?>
