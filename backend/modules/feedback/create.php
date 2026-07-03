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
if ($user['role'] !== 'admin' && $user['role'] !== 'moh') {
    sendError('Only Admin or MoH can submit feedback', 403);
}

// Verify referral access for Admins
if ($user['role'] === 'admin') {
    $stmt = $conn->prepare('SELECT id FROM referrals WHERE id = ? AND receiving_facility_id = ?');
    $stmt->bind_param('ii', $referralId, $user['facility_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        sendError('Referral not found or access denied', 403);
    }
    $stmt->close();
}

$stmt = $conn->prepare(
    'INSERT INTO feedback (
        referral_id, sent_by_admin_id, department, referral_serial_no, referral_diagnosis,
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

// Notify referring CO about the new feedback
$detailStmt = $conn->prepare(
    'SELECT p.first_name AS patient_first_name, p.last_name AS patient_last_name, u.email AS referrer_email, u.phone AS referrer_phone
     FROM referrals r
     JOIN patients p ON r.patient_id = p.id
     JOIN users u ON r.referring_co_id = u.id
     WHERE r.id = ?'
);
$detailStmt->bind_param('i', $referralId);
$detailStmt->execute();
$detailResult = $detailStmt->get_result();
$detail = $detailResult->fetch_assoc();
$detailStmt->close();

if ($detail) {
    notifyFeedbackCreated(
        $conn,
        $referralId,
        $detail['referrer_email'],
        $detail['referrer_phone'],
        trim($detail['patient_first_name'] . ' ' . $detail['patient_last_name'])
    );
}

sendResponse(['success' => true, 'message' => 'Feedback submitted successfully']);
?>
