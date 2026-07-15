<?php
// ===========================================
// Notifications API - List Notifications
// GET /notifications/list.php
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
        n.id,
        n.sender_user_id,
        n.recipient_user_id,
        n.reply_to_notification_id,
        n.type,
        n.recipient_email,
        n.recipient_phone,
        n.subject,
        n.message,
        n.sent_at,
        n.status,
        n.error_message,
        r.id AS referral_id,
        r.status AS referral_status,
        r.rejection_reason,
        doc.user_id AS assigned_doctor_user_id,
        p.first_name AS patient_first_name,
        p.last_name AS patient_last_name,
        su.first_name AS sender_first_name,
        su.last_name AS sender_last_name,
        ru.first_name AS recipient_first_name,
        ru.last_name AS recipient_last_name
    FROM notifications n
    LEFT JOIN referrals r ON n.referral_id = r.id
    LEFT JOIN patients p ON r.patient_id = p.id
    LEFT JOIN doctors doc ON r.assigned_doctor_id = doc.id
    LEFT JOIN users su ON n.sender_user_id = su.id
    LEFT JOIN users ru ON n.recipient_user_id = ru.id
";

$conversationOnly = " (n.sender_user_id IS NOT NULL OR n.recipient_user_id IS NOT NULL OR n.reply_to_notification_id IS NOT NULL)";

if ($user['role'] === 'moh') {
    $query .= " WHERE" . $conversationOnly . " ORDER BY n.sent_at DESC";
    $stmt = $conn->prepare($query);
} elseif ($user['role'] === 'receptionist') {
    $query .= " WHERE (r.referring_facility_id = ? OR r.receiving_facility_id = ?) AND" . $conversationOnly . " ORDER BY n.sent_at DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ii', $user['facility_id'], $user['facility_id']);
} else {
    $query .= " WHERE (r.referring_co_id = ? OR n.recipient_user_id = ? OR n.sender_user_id = ?) AND" . $conversationOnly . " ORDER BY n.sent_at DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('iii', $user['id'], $user['id'], $user['id']);
}

$stmt->execute();
$result = $stmt->get_result();
$notifications = [];
while ($row = $result->fetch_assoc()) {
    $row['patient_name'] = trim($row['patient_first_name'] . ' ' . $row['patient_last_name']);
    $row['sender_name'] = trim(($row['sender_first_name'] ?? '') . ' ' . ($row['sender_last_name'] ?? ''));
    $row['recipient_name'] = trim(($row['recipient_first_name'] ?? '') . ' ' . ($row['recipient_last_name'] ?? ''));
    $row['can_reply'] = ((int)$row['recipient_user_id'] === (int)$user['id'])
        || ((int)$row['sender_user_id'] === (int)$user['id'])
        || ($user['role'] === 'receptionist');
    $row['can_decide_referral'] = ((int)$row['recipient_user_id'] === (int)$user['id'])
        && ((int)$row['assigned_doctor_user_id'] === (int)$user['id'])
        && in_array($row['referral_status'], ['pending', 'in_progress'], true);
    unset($row['patient_first_name'], $row['patient_last_name'], $row['sender_first_name'], $row['sender_last_name'], $row['recipient_first_name'], $row['recipient_last_name']);
    $notifications[] = $row;
}

sendResponse([
    'success' => true,
    'notifications' => $notifications,
    'count' => count($notifications),
]);
?>
