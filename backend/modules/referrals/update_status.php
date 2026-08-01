<?php
// ===========================================
// Referrals API - Update Referral Status
// POST /referrals/update_status.php
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

validateRequired($input, ['referral_id', 'status']);

$referralId = (int)$input['referral_id'];
$status = $input['status'];
$allowed = ['accepted','rejected'];
if (!in_array($status, $allowed, true)) {
    sendError('Invalid status value', 400);
}

$user = getCurrentUser();

$detailStmt = $conn->prepare(
    'SELECT
        r.id,
        r.status,
        r.doctor_decision,
        r.doctor_decision_reason,
        r.rejection_reason,
        r.receiving_facility_id,
        p.first_name AS patient_first_name,
        p.last_name AS patient_last_name,
        referrer.id AS referrer_user_id,
        referrer.email AS referrer_email,
        referrer.phone AS referrer_phone,
        f2.name AS receiving_facility_name
     FROM referrals r
     JOIN patients p ON r.patient_id = p.id
     JOIN users referrer ON r.referring_co_id = referrer.id
     JOIN facilities f2 ON r.receiving_facility_id = f2.id
     WHERE r.id = ?'
);
if (!$detailStmt) {
    sendError('Database error preparing referral lookup', 500);
}
$detailStmt->bind_param('i', $referralId);
$detailStmt->execute();
$detailResult = $detailStmt->get_result();
if ($detailResult->num_rows === 0) {
    sendError('Referral not found', 404);
}
$detail = $detailResult->fetch_assoc();
$detailStmt->close();

if ($user['role'] === 'receptionist') {
    if ((int)$detail['receiving_facility_id'] !== (int)$user['facility_id']) {
        sendError('Access denied', 403);
    }
} elseif (!in_array($user['role'], ['admin', 'super_admin'], true)) {
    sendError('Only Receptionist or Admin can confirm referral status', 403);
}

if ($detail['status'] !== 'pending') {
    sendError('Referral final status has already been confirmed', 409);
}

if ($status !== $detail['doctor_decision']) {
    sendError('Receptionist can only confirm the doctor decision', 400);
}

$timestampSql = $status === 'accepted'
    ? 'accepted_at = NOW(), rejected_at = NULL, rejection_reason = NULL'
    : 'rejected_at = NOW(), rejection_reason = ?';

$stmt = $conn->prepare("UPDATE referrals SET status = ?, {$timestampSql} WHERE id = ? AND status = 'pending' AND doctor_decision = ?");
if (!$stmt) {
    sendError('Database error preparing statement', 500);
}

if ($status === 'accepted') {
    $stmt->bind_param('sis', $status, $referralId, $status);
} else {
    $reason = $detail['doctor_decision_reason'] ?: 'Rejected by receiving doctor';
    $stmt->bind_param('ssis', $status, $reason, $referralId, $status);
}
$ok = $stmt->execute();
if (!$ok) {
    sendError('Unable to update referral status', 500);
}
if ($stmt->affected_rows === 0) {
    sendError('Referral status was already confirmed', 409);
}
$stmt->close();

if ($detail) {
    $patientName = trim($detail['patient_first_name'] . ' ' . $detail['patient_last_name']);
    $subject = "Referral #{$referralId} {$status}";
    $message = "Referral #{$referralId} for {$patientName} has been {$status} by {$detail['receiving_facility_name']}.";
    if ($status === 'rejected' && !empty($detail['doctor_decision_reason'])) {
        $message .= "\n\nReason: {$detail['doctor_decision_reason']}";
    }
    createNotification(
        $conn,
        $referralId,
        $detail['referrer_email'],
        $detail['referrer_phone'],
        $subject,
        $message,
        'email',
        'pending',
        $user['id'],
        (int)$detail['referrer_user_id']
    );
}

logAudit($conn, $user, 'receptionist_confirm_referral_status', $referralId, 'pending', $status, $status === 'rejected' ? $detail['doctor_decision_reason'] : null);

sendResponse(['success' => true, 'message' => 'Referral status confirmed']);

?>
