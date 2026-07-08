<?php
// ===========================================
// Communications API - Send a message for a referral
// POST /communications/create.php
// ===========================================

require_once __DIR__ . '/../../config/db.php';
require_once dirname(__DIR__, 2) . '/includes/session.php';

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

validateRequired($input, ['referral_id', 'recipient_id', 'message']);

$referralId = (int)$input['referral_id'];
$recipientId = (int)$input['recipient_id'];
$message = trim($input['message']);
if ($message === '') {
    sendError('Message cannot be empty', 400);
}

$user = getCurrentUser();
if (!canAccessReferral($referralId, $conn)) {
    sendError('Access denied', 403);
}

if ($user['role'] !== 'receptionist') {
    sendError('Only receptionists can initiate referral communication', 403);
}

// Ensure recipient is a receiving doctor for this referral
$recipientStmt = $conn->prepare(
    'SELECT u.id
     FROM users u
     JOIN doctors doc ON doc.user_id = u.id
     JOIN referrals r ON r.id = ?
     JOIN departments dep ON doc.department_id = dep.id
     WHERE u.id = ?
       AND r.receiving_department_id = dep.id
       AND r.receiving_facility_id = dep.facility_id'
);
$recipientStmt->bind_param('ii', $referralId, $recipientId);
$recipientStmt->execute();
$recipientResult = $recipientStmt->get_result();
if ($recipientResult->num_rows === 0) {
    sendError('Recipient is not a receiving doctor for this referral', 403);
}
$recipientStmt->close();

$stmt = $conn->prepare('INSERT INTO communications (referral_id, sender_id, recipient_id, message) VALUES (?, ?, ?, ?)');
$stmt->bind_param('iiis', $referralId, $user['id'], $recipientId, $message);
if (!$stmt->execute()) {
    sendError('Unable to save message', 500);
}
$stmt->close();

sendResponse(['success' => true, 'message' => 'Message sent successfully']);
?>