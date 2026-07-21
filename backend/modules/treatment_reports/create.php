<?php
// ===========================================
// Treatment Reports API - Create
// POST /treatment_reports/create.php
// ===========================================

require_once __DIR__ . '/../../config/db.php';
require_once dirname(__DIR__, 2) . '/includes/session.php';
require_once dirname(__DIR__, 2) . '/includes/notifications.php';
require_once dirname(__DIR__, 2) . '/includes/audit.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError('Method not allowed', 405);
}

if (!isLoggedIn()) {
    sendError('Authentication required', 401);
}

$user = getCurrentUser();
if ($user['role'] !== 'co') {
    sendError('Only assigned receiving doctors/COs can submit treatment reports', 403);
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    sendError('Invalid JSON input');
}

validateRequired($input, ['referral_id', 'diagnosis', 'treatment_given', 'patient_outcome', 'report_date', 'doctor_name']);

$referralId = (int)$input['referral_id'];
$diagnosis = trim($input['diagnosis']);
$investigations = trim($input['investigations'] ?? '');
$treatmentGiven = trim($input['treatment_given']);
$proceduresDone = trim($input['procedures_done'] ?? '');
$patientOutcome = trim($input['patient_outcome']);
$currentCondition = trim($input['current_condition'] ?? '');
$recommendation = trim($input['recommendation'] ?? '');
$suggestedFollowUp = trim($input['suggested_follow_up'] ?? '');
$reportDate = trim($input['report_date']);
$doctorName = trim($input['doctor_name']);
$digitalSignature = trim($input['digital_signature'] ?? '');

$accessStmt = $conn->prepare(
    'SELECT
        r.id,
        r.status,
        r.receiving_facility_id,
        p.first_name AS patient_first_name,
        p.last_name AS patient_last_name,
        receptionist.id AS receptionist_user_id,
        receptionist.email AS receptionist_email,
        receptionist.phone AS receptionist_phone,
        doc.user_id AS assigned_doctor_user_id
     FROM referrals r
     JOIN patients p ON r.patient_id = p.id
     JOIN doctors doc ON r.assigned_doctor_id = doc.id
     LEFT JOIN users receptionist ON receptionist.facility_id = r.receiving_facility_id
        AND receptionist.role = "receptionist"
        AND receptionist.is_active = 1
     WHERE r.id = ?
     ORDER BY receptionist.id ASC
     LIMIT 1'
);
if (!$accessStmt) {
    sendError('Database error preparing access check', 500);
}
$accessStmt->bind_param('i', $referralId);
$accessStmt->execute();
$accessResult = $accessStmt->get_result();
if ($accessResult->num_rows === 0) {
    sendError('Referral not found', 404);
}
$referral = $accessResult->fetch_assoc();
$accessStmt->close();

if ((int)$referral['assigned_doctor_user_id'] !== (int)$user['id']) {
    sendError('Only the assigned receiving doctor can submit this treatment report', 403);
}

if ($referral['status'] !== 'accepted') {
    sendError('Treatment report can only be submitted after referral acceptance', 400);
}

$stmt = $conn->prepare(
    'INSERT INTO treatment_reports (
        referral_id, submitted_by_user_id, diagnosis, investigations, treatment_given, procedures_done,
        patient_outcome, current_condition, recommendation, suggested_follow_up, report_date, doctor_name, digital_signature
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
);
if (!$stmt) {
    sendError('Database error preparing treatment report', 500);
}
$stmt->bind_param(
    'iisssssssssss',
    $referralId,
    $user['id'],
    $diagnosis,
    $investigations,
    $treatmentGiven,
    $proceduresDone,
    $patientOutcome,
    $currentCondition,
    $recommendation,
    $suggestedFollowUp,
    $reportDate,
    $doctorName,
    $digitalSignature
);
if (!$stmt->execute()) {
    sendError('Unable to save treatment report', 500);
}
$reportId = $stmt->insert_id;
$stmt->close();

$patientName = trim($referral['patient_first_name'] . ' ' . $referral['patient_last_name']);
if (!empty($referral['receptionist_email'])) {
    createNotification(
        $conn,
        $referralId,
        $referral['receptionist_email'],
        $referral['receptionist_phone'],
        "Treatment report submitted for referral #{$referralId}",
        "A treatment report has been submitted for referral #{$referralId} ({$patientName}).\n\nOutcome: {$patientOutcome}",
        'email',
        'pending',
        $user['id'],
        (int)$referral['receptionist_user_id']
    );
}

logAudit($conn, $user, 'treatment_report_created', $referralId, 'accepted', 'accepted', "Treatment report #{$reportId} submitted");

sendResponse([
    'success' => true,
    'message' => 'Treatment report submitted successfully',
    'report_id' => $reportId,
]);
?>
