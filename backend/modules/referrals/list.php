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
        r.rejection_reason,
        r.doctor_decision,
        r.doctor_decision_reason,
        r.doctor_decision_at,
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
        r.referring_co_id,
        r.receiving_facility_id,
        r.receiving_department_id,
        dep.name AS receiving_department,
        r.assigned_doctor_id,
        doc.user_id AS assigned_doctor_user_id,
        doc.license_number AS assigned_doctor_license,
        doc.contact_phone AS assigned_doctor_phone,
        docu.first_name AS assigned_doctor_first_name,
        docu.last_name AS assigned_doctor_last_name,
        r.facilitator_phone,
        r.clinical_reason,
        r.clinical_findings,
        r.requested_services,
        r.created_at,
        p.first_name AS patient_first_name,
        p.last_name AS patient_last_name,
        u.first_name AS co_first_name,
        u.last_name AS co_last_name,
        u.email AS co_email,
        u.phone AS co_phone,
        f1.name AS referring_facility,
        f2.name AS receiving_facility
    FROM referrals r
    JOIN patients p ON r.patient_id = p.id
    JOIN users u ON r.referring_co_id = u.id
    JOIN facilities f1 ON r.referring_facility_id = f1.id
    JOIN facilities f2 ON r.receiving_facility_id = f2.id
    LEFT JOIN departments dep ON r.receiving_department_id = dep.id
    LEFT JOIN doctors doc ON r.assigned_doctor_id = doc.id
    LEFT JOIN users docu ON doc.user_id = docu.id
";

if (in_array($user['role'], ['admin', 'super_admin'], true)) {
    $query .= " ORDER BY r.created_at DESC";
    $stmt = $conn->prepare($query);
} elseif ($user['role'] === 'receptionist') {
    $query .= " WHERE f1.id = ? OR f2.id = ? ORDER BY r.created_at DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ii', $user['facility_id'], $user['facility_id']);
} else {
    $query .= " WHERE (r.referring_co_id = ? OR EXISTS (
        SELECT 1
        FROM doctors doc
        JOIN departments dep ON doc.department_id = dep.id
        WHERE doc.user_id = ?
          AND dep.id = r.receiving_department_id
          AND dep.facility_id = r.receiving_facility_id
    )) ORDER BY r.created_at DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ii', $user['id'], $user['id']);
}

$stmt->execute();
$result = $stmt->get_result();
$referrals = [];
while ($row = $result->fetch_assoc()) {
    $row['patient_name'] = trim($row['patient_first_name'] . ' ' . $row['patient_last_name']);
    $row['referring_co'] = trim($row['co_first_name'] . ' ' . $row['co_last_name']);
    $row['receiving_department'] = $row['receiving_department'] ?? null;
    $row['assigned_doctor_name'] = trim(($row['assigned_doctor_first_name'] ?? '') . ' ' . ($row['assigned_doctor_last_name'] ?? ''));
    if (empty($row['assigned_doctor_name'])) {
        $row['assigned_doctor_name'] = null;
    }
    if (empty($row['doctor_name'])) {
        $row['doctor_name'] = $row['referring_co'];
    }
    if (empty($row['doctor_phone'])) {
        $row['doctor_phone'] = $row['co_phone'];
    }
    unset($row['patient_first_name'], $row['patient_last_name'], $row['co_first_name'], $row['co_last_name'], $row['co_phone'], $row['assigned_doctor_first_name'], $row['assigned_doctor_last_name']);
    $referrals[] = $row;
}

sendResponse([
    'success' => true,
    'referrals' => $referrals,
    'count' => count($referrals),
]);
?>
