<?php
// ===========================================
// Patients API - List Patients
// GET /patients/list.php
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
    SELECT DISTINCT
        p.id,
        p.first_name,
        p.last_name,
        p.date_of_birth,
        p.gender,
        p.phone,
        p.address,
        p.national_id,
        p.created_at,
        COUNT(r.id) AS referral_count,
        MAX(r.created_at) AS last_referral_at
    FROM patients p
    LEFT JOIN referrals r ON p.id = r.patient_id
";

if (in_array($user['role'], ['admin', 'super_admin'], true)) {
    $query .= "
        GROUP BY p.id
        ORDER BY p.created_at DESC
    ";
    $stmt = $conn->prepare($query);
} elseif ($user['role'] === 'receptionist') {
    $query .= "
        WHERE r.referring_facility_id = ? OR r.receiving_facility_id = ?
        GROUP BY p.id
        ORDER BY p.created_at DESC
    ";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ii', $user['facility_id'], $user['facility_id']);
} else {
    $query .= "
        WHERE r.referring_co_id = ?
        GROUP BY p.id
        ORDER BY p.created_at DESC
    ";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $user['id']);
}

if (!$stmt) {
    sendError('Database error preparing statement', 500);
}

$stmt->execute();
$result = $stmt->get_result();

$patients = [];
while ($row = $result->fetch_assoc()) {
    $row['patient_name'] = trim($row['first_name'] . ' ' . $row['last_name']);
    $row['referral_count'] = (int)$row['referral_count'];
    $patients[] = $row;
}

$stmt->close();

sendResponse([
    'success' => true,
    'patients' => $patients,
    'count' => count($patients),
]);
?>
