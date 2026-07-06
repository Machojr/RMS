<?php
// ===========================================
// Referrals API - List Referrals
// GET /referrals/list.php
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
        r.id,
        r.patient_number,
        r.age_years,
        r.status,
        r.urgency,
        r.region,
        r.district,
        r.transfer_date,
        r.referral_number,
        r.diagnosis,
        r.temperature,
        r.heart_rate,
        r.respiratory_rate,
        r.blood_pressure,
        r.mental_status,
        r.alert_status,
        r.patient_history,
        r.chronic_medications,
        r.medication_allergies,
        r.examination_findings,
        r.laboratory_results,
        r.radiology_results,
        r.treatment_before_transfer,
        r.reason_for_transfer,
        r.doctor_name,
        r.doctor_phone,
        r.facilitator_phone,
        r.clinical_reason,
        r.clinical_findings,
        r.requested_services,
        r.created_at,
        p.first_name AS patient_first_name,
        p.last_name AS patient_last_name,
        u.first_name AS co_first_name,
        u.last_name AS co_last_name,
        u.phone AS co_phone,
        f1.name AS referring_facility,
        f2.name AS receiving_facility
    FROM referrals r
    JOIN patients p ON r.patient_id = p.id
    JOIN users u ON r.referring_co_id = u.id
    JOIN facilities f1 ON r.referring_facility_id = f1.id
    JOIN facilities f2 ON r.receiving_facility_id = f2.id
";

if ($user['role'] === 'moh') {
    $query .= " ORDER BY r.created_at DESC";
    $stmt = $conn->prepare($query);
} elseif ($user['role'] === 'admin') {
    $query .= " WHERE f1.id = ? OR f2.id = ? ORDER BY r.created_at DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ii', $user['facility_id'], $user['facility_id']);
} else {
    $query .= " WHERE r.referring_co_id = ? ORDER BY r.created_at DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $user['id']);
}

$stmt->execute();
$result = $stmt->get_result();
$referrals = [];
while ($row = $result->fetch_assoc()) {
    $row['patient_name'] = trim($row['patient_first_name'] . ' ' . $row['patient_last_name']);
    $row['referring_co'] = trim($row['co_first_name'] . ' ' . $row['co_last_name']);
    if (empty($row['doctor_name'])) {
        $row['doctor_name'] = $row['referring_co'];
    }
    if (empty($row['doctor_phone'])) {
        $row['doctor_phone'] = $row['co_phone'];
    }
    unset($row['patient_first_name'], $row['patient_last_name'], $row['co_first_name'], $row['co_last_name'], $row['co_phone']);
    $referrals[] = $row;
}

sendResponse([
    'success' => true,
    'referrals' => $referrals,
    'count' => count($referrals),
]);
?>
