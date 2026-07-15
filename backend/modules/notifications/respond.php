<?php
// ===========================================
// Notifications API - Doctor Referral Decision
// POST /notifications/respond.php
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

validateRequired($input, ['notification_id', 'decision']);

$notificationId = (int)$input['notification_id'];
$decision = trim($input['decision']);
$reason = isset($input['reason']) ? trim($input['reason']) : '';

if (!in_array($decision, ['accepted', 'rejected'], true)) {
    sendError('Decision must be accepted or rejected', 400);
}

if ($decision === 'rejected' && $reason === '') {
    sendError('Rejection reason is required', 400);
}

$user = getCurrentUser();

$stmt = $conn->prepare(
    'SELECT
        n.id,
        n.referral_id,
        n.sender_user_id,
        n.recipient_user_id,
        n.subject,
        r.status AS referral_status,
        r.assigned_doctor_id,
        r.receiving_facility_id,
        p.first_name AS patient_first_name,
        p.last_name AS patient_last_name,
        su.email AS sender_email,
        su.phone AS sender_phone,
        su.first_name AS sender_first_name,
        su.last_name AS sender_last_name,
        doc.user_id AS assigned_doctor_user_id
     FROM notifications n
     JOIN referrals r ON n.referral_id = r.id
     JOIN patients p ON r.patient_id = p.id
     JOIN doctors doc ON r.assigned_doctor_id = doc.id
     LEFT JOIN users su ON n.sender_user_id = su.id
     WHERE n.id = ?'
);
if (!$stmt) {
    sendError('Database error preparing statement', 500);
}
$stmt->bind_param('i', $notificationId);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    sendError('Notification not found', 404);
}
$notification = $result->fetch_assoc();
$stmt->close();

$isAssignedDoctor = (int)$notification['assigned_doctor_user_id'] === (int)$user['id'];
$isRecipient = (int)$notification['recipient_user_id'] === (int)$user['id'];
if (!$isAssignedDoctor || !$isRecipient) {
    sendError('Only the assigned doctor can respond to this referral notification', 403);
}

if (!in_array($notification['referral_status'], ['pending', 'in_progress'], true)) {
    sendError('This referral has already been decided', 400);
}

$timestampSql = $decision === 'accepted'
    ? 'accepted_at = NOW(), rejected_at = NULL, rejection_reason = NULL'
    : 'rejected_at = NOW(), rejection_reason = ?';

if ($decision === 'accepted') {
    $updateStmt = $conn->prepare("UPDATE referrals SET status = ?, {$timestampSql} WHERE id = ?");
} else {
    $updateStmt = $conn->prepare("UPDATE referrals SET status = ?, {$timestampSql} WHERE id = ?");
}

if (!$updateStmt) {
    sendError('Database error preparing referral decision update', 500);
}

if ($decision === 'accepted') {
    $updateStmt->bind_param('si', $decision, $notification['referral_id']);
} else {
    $updateStmt->bind_param('ssi', $decision, $reason, $notification['referral_id']);
}

if (!$updateStmt->execute()) {
    sendError('Unable to update referral decision', 500);
}
$updateStmt->close();

$doctorName = trim($user['first_name'] . ' ' . $user['last_name']);
$patientName = trim($notification['patient_first_name'] . ' ' . $notification['patient_last_name']);
$decisionLabel = $decision === 'accepted' ? 'accepted' : 'rejected';
$subject = "Referral #{$notification['referral_id']} {$decisionLabel} by doctor";
$message = "Doctor {$doctorName} has {$decisionLabel} referral #{$notification['referral_id']} for {$patientName}.";
if ($decision === 'rejected') {
    $message .= "\n\nReason: {$reason}";
}

$created = createNotification(
    $conn,
    (int)$notification['referral_id'],
    $notification['sender_email'],
    $notification['sender_phone'],
    $subject,
    $message,
    'email',
    'pending',
    $user['id'],
    (int)$notification['sender_user_id'],
    $notificationId
);

if (!$created) {
    sendError('Referral decision saved, but notification back to receptionist failed', 500);
}

sendResponse([
    'success' => true,
    'message' => "Referral {$decisionLabel} successfully",
]);
?>
