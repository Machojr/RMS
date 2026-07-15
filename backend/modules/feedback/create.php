<?php
// ===========================================
// Feedback API - Create Feedback
// POST /feedback/create.php
// ===========================================

require_once __DIR__ . '/../../config/db.php';
require_once dirname(__DIR__, 2) . '/includes/session.php';
require_once dirname(__DIR__, 2) . '/includes/notifications.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError('Method not allowed', 405);
}

if (!isLoggedIn()) {
    sendError('Authentication required', 401);
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    sendError('Invalid JSON input');
}

validateRequired($input, ['referral_id', 'clinical_outcome']);

$referralId = (int)$input['referral_id'];
$department = trim($input['department'] ?? '');
$referralSerialNo = trim($input['referral_serial_no'] ?? '');
$referralDiagnosis = trim($input['referral_diagnosis'] ?? '');
$confirmedDiagnosis = trim($input['confirmed_diagnosis'] ?? '');
$comments = trim($input['comments'] ?? '');
$clinicalOutcome = trim($input['clinical_outcome']);
$treatmentGiven = trim($input['treatment_given'] ?? '');
$dischargeSummary = trim($input['discharge_summary'] ?? '');
$followUpInstructions = trim($input['follow_up_instructions'] ?? '');

$user = getCurrentUser();
if (!in_array($user['role'], ['co', 'receptionist', 'moh'], true)) {
    sendError('Only CO, Receptionist or MoH can submit feedback', 403);
}

if ($user['role'] === 'receptionist') {
    $stmt = $conn->prepare('SELECT id FROM referrals WHERE id = ? AND receiving_facility_id = ?');
    $stmt->bind_param('ii', $referralId, $user['facility_id']);
} elseif ($user['role'] === 'co') {
    $stmt = $conn->prepare('SELECT id FROM referrals WHERE id = ? AND referring_co_id = ?');
    $stmt->bind_param('ii', $referralId, $user['id']);
} else {
    $stmt = $conn->prepare('SELECT id FROM referrals WHERE id = ?');
    $stmt->bind_param('i', $referralId);
}

if (!$stmt) {
    sendError('Database error preparing referral access check', 500);
}
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    sendError('Referral not found or access denied', 403);
}
$stmt->close();

$stmt = $conn->prepare(
    'INSERT INTO feedback (
        referral_id, sent_by_receptionist_id, department, referral_serial_no, referral_diagnosis,
        confirmed_diagnosis, comments, clinical_outcome, treatment_given, discharge_summary, follow_up_instructions
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
);
if (!$stmt) {
    sendError('Database error preparing statement', 500);
}
$stmt->bind_param(
    'iisssssssss',
    $referralId,
    $user['id'],
    $department,
    $referralSerialNo,
    $referralDiagnosis,
    $confirmedDiagnosis,
    $comments,
    $clinicalOutcome,
    $treatmentGiven,
    $dischargeSummary,
    $followUpInstructions
);

if (!$stmt->execute()) {
    sendError('Unable to save feedback', 500);
}
$stmt->close();

$detailStmt = $conn->prepare(
    'SELECT
        p.first_name AS patient_first_name,
        p.last_name AS patient_last_name,
        referrer.id AS referrer_user_id,
        referrer.email AS referrer_email,
        referrer.phone AS referrer_phone,
        receptionist.id AS receptionist_user_id,
        receptionist.email AS receptionist_email,
        receptionist.phone AS receptionist_phone
     FROM referrals r
     JOIN patients p ON r.patient_id = p.id
     JOIN users referrer ON r.referring_co_id = referrer.id
     LEFT JOIN users receptionist ON receptionist.facility_id = r.receiving_facility_id
        AND receptionist.role = "receptionist"
        AND receptionist.is_active = 1
     WHERE r.id = ?
     ORDER BY receptionist.id ASC
     LIMIT 1'
);
$detailStmt->bind_param('i', $referralId);
$detailStmt->execute();
$detailResult = $detailStmt->get_result();
$detail = $detailResult->fetch_assoc();
$detailStmt->close();

$patientName = $detail ? trim($detail['patient_first_name'] . ' ' . $detail['patient_last_name']) : '';
$senderName = trim($user['first_name'] . ' ' . $user['last_name']);
if ($detail && $user['role'] === 'receptionist') {
    createNotification(
        $conn,
        $referralId,
        $detail['referrer_email'],
        $detail['referrer_phone'],
        'Feedback message received',
        "Feedback message from {$senderName} for referral #{$referralId} ({$patientName}).\n\n{$clinicalOutcome}",
        'email',
        'pending',
        $user['id'],
        (int)$detail['referrer_user_id']
    );
} elseif ($detail && $user['role'] === 'co' && !empty($detail['receptionist_email'])) {
    createNotification(
        $conn,
        $referralId,
        $detail['receptionist_email'],
        $detail['receptionist_phone'],
        'Feedback message received',
        "Feedback message from {$senderName} for referral #{$referralId} ({$patientName}).\n\n{$clinicalOutcome}",
        'email',
        'pending',
        $user['id'],
        (int)$detail['receptionist_user_id']
    );
}

sendResponse(['success' => true, 'message' => 'Feedback submitted successfully']);
?>
