<?php
// ===========================================
// Notifications API - Reply to Notification
// POST /notifications/reply.php
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

validateRequired($input, ['notification_id', 'message']);

$notificationId = (int)$input['notification_id'];
$messageText = trim($input['message']);
if ($messageText === '') {
    sendError('Reply message cannot be empty', 400);
}

$user = getCurrentUser();

$stmt = $conn->prepare(
    'SELECT
        n.id,
        n.referral_id,
        n.subject,
        n.sender_user_id,
        n.recipient_user_id,
        r.receiving_facility_id,
        su.email AS sender_email,
        su.phone AS sender_phone,
        su.first_name AS sender_first_name,
        su.last_name AS sender_last_name,
        ru.email AS recipient_email,
        ru.phone AS recipient_phone,
        ru.first_name AS recipient_first_name,
        ru.last_name AS recipient_last_name
     FROM notifications n
     JOIN referrals r ON n.referral_id = r.id
     LEFT JOIN users su ON n.sender_user_id = su.id
     LEFT JOIN users ru ON n.recipient_user_id = ru.id
     WHERE n.id = ?'
);
$stmt->bind_param('i', $notificationId);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    sendError('Notification not found', 404);
}
$notification = $result->fetch_assoc();
$stmt->close();

$isSender = (int)$notification['sender_user_id'] === (int)$user['id'];
$isRecipient = (int)$notification['recipient_user_id'] === (int)$user['id'];
$isFacilityReceptionist = $user['role'] === 'receptionist' && (int)$notification['receiving_facility_id'] === (int)$user['facility_id'];

if (!$isSender && !$isRecipient && !$isFacilityReceptionist) {
    sendError('Access denied', 403);
}

if ($isRecipient) {
    $targetUserId = (int)$notification['sender_user_id'];
    $targetEmail = $notification['sender_email'];
    $targetPhone = $notification['sender_phone'];
    $targetName = trim($notification['sender_first_name'] . ' ' . $notification['sender_last_name']);
} else {
    $targetUserId = (int)$notification['recipient_user_id'];
    $targetEmail = $notification['recipient_email'];
    $targetPhone = $notification['recipient_phone'];
    $targetName = trim($notification['recipient_first_name'] . ' ' . $notification['recipient_last_name']);
}

if ($targetUserId <= 0) {
    sendError('Original notification recipient/sender is missing', 400);
}

$subject = 'Re: ' . ($notification['subject'] ?: 'Referral notification');
$senderName = trim($user['first_name'] . ' ' . $user['last_name']);
$replyMessage = "Reply from {$senderName}\n\n{$messageText}";

$created = createNotification(
    $conn,
    (int)$notification['referral_id'],
    $targetEmail,
    $targetPhone,
    $subject,
    $replyMessage,
    'email',
    'pending',
    $user['id'],
    $targetUserId,
    $notificationId
);

if (!$created) {
    sendError("Unable to send reply to {$targetName}", 500);
}

sendResponse(['success' => true, 'message' => 'Reply sent successfully']);
?>
