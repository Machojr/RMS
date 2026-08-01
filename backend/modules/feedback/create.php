<?php
// ===========================================
// Feedback API - Create Feedback
// POST /feedback/create.php
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
if ($user['role'] !== 'co' && !in_array($user['role'], ['admin', 'super_admin'], true)) {
    sendError('Only receiving doctors/COs can submit feedback', 403);
}

$accessStmt = $conn->prepare(
    'SELECT r.id
     FROM referrals r
     JOIN doctors doc ON r.assigned_doctor_id = doc.id
     WHERE r.id = ?
       AND (r.status = "accepted" OR (r.status = "pending" AND r.doctor_decision = "accepted"))
       AND doc.user_id = ?'
);
if ($user['role'] === 'co') {
    $stmt = $accessStmt;
} else {
    $stmt = $conn->prepare('SELECT id FROM referrals WHERE id = ?');
}

if (!$stmt) {
    sendError('Database error preparing referral access check', 500);
}
if ($user['role'] === 'co') {
    $stmt->bind_param('ii', $referralId, $user['id']);
} else {
    $stmt->bind_param('i', $referralId);
}
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    sendError('Referral not found, not accepted by doctor, or not assigned to this doctor', 403);
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

$completeStmt = $conn->prepare(
    'UPDATE referrals
     SET status = "completed", completed_at = NOW()
     WHERE id = ?
       AND (status = "accepted" OR (status = "pending" AND doctor_decision = "accepted"))'
);
if (!$completeStmt) {
    sendError('Database error preparing referral completion', 500);
}
$completeStmt->bind_param('i', $referralId);
if (!$completeStmt->execute()) {
    sendError('Feedback saved, but referral completion failed', 500);
}
$completed = $completeStmt->affected_rows > 0;
$completeStmt->close();

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
    // Receptionists no longer submit clinical feedback.
} elseif ($detail && !empty($detail['referrer_email'])) {
    createNotification(
        $conn,
        $referralId,
        $detail['referrer_email'],
        $detail['referrer_phone'],
        'Feedback message received',
        "Feedback from {$senderName} for referral #{$referralId} ({$patientName}).\n\n"
            . "Clinical outcome: {$clinicalOutcome}\n"
            . "Treatment: {$treatmentGiven}\n"
            . "Follow-up: {$followUpInstructions}\n"
            . "Comments: {$comments}",
        'email',
        'pending',
        $user['id'],
        (int)$detail['referrer_user_id']
    );
}

if ($completed) {
    logAudit($conn, $user, 'feedback_submitted_referral_completed', $referralId, 'accepted', 'completed', 'Referral completed automatically after doctor feedback');
}

sendResponse(['success' => true, 'message' => 'Feedback submitted successfully and referral marked completed']);
?>
