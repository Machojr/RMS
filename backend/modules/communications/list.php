<?php
// ===========================================
// Communications API - List messages for a referral
// GET /communications/list.php
// ===========================================

require_once __DIR__ . '/../../config/db.php';
require_once dirname(__DIR__, 2) . '/includes/session.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendError('Method not allowed', 405);
}

if (!isLoggedIn()) {
    sendError('Authentication required', 401);
}

$referralId = isset($_GET['referral_id']) ? (int)$_GET['referral_id'] : 0;
if ($referralId <= 0) {
    sendError('referral_id is required', 400);
}

if (!canAccessReferral($referralId, $conn)) {
    sendError('Access denied', 403);
}

$stmt = $conn->prepare(
    'SELECT
         c.id,
         c.message,
         c.created_at,
         c.sender_id,
         su.first_name AS sender_first_name,
         su.last_name AS sender_last_name,
         c.recipient_id,
         ru.first_name AS recipient_first_name,
         ru.last_name AS recipient_last_name
     FROM communications c
     LEFT JOIN users su ON c.sender_id = su.id
     LEFT JOIN users ru ON c.recipient_id = ru.id
     WHERE c.referral_id = ?
     ORDER BY c.created_at ASC'
);
$stmt->bind_param('i', $referralId);
$stmt->execute();
$result = $stmt->get_result();
$messages = [];
while ($row = $result->fetch_assoc()) {
    $row['sender_name'] = trim($row['sender_first_name'] . ' ' . $row['sender_last_name']);
    $row['recipient_name'] = trim($row['recipient_first_name'] . ' ' . $row['recipient_last_name']);
    unset($row['sender_first_name'], $row['sender_last_name'], $row['recipient_first_name'], $row['recipient_last_name']);
    $messages[] = $row;
}
$stmt->close();

sendResponse([
    'success' => true,
    'messages' => $messages,
    'count' => count($messages),
]);
?>