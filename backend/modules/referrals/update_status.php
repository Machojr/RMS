<?php
// ===========================================
// Referrals API - Update Referral Status
// POST /referrals/update_status.php
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

validateRequired($input, ['referral_id', 'status']);

$referralId = (int)$input['referral_id'];
$status = $input['status'];
$allowed = ['pending','accepted','in_progress','completed','rejected'];
if (!in_array($status, $allowed, true)) {
    sendError('Invalid status value', 400);
}

$user = getCurrentUser();

// Check access
if ($user['role'] === 'admin') {
    if (!canAccessReferral($referralId, $conn)) {
        sendError('Access denied', 403);
    }
} elseif ($user['role'] !== 'moh') {
    sendError('Only Admin or MoH can update referral status', 403);
}

// Prepare update and timestamp fields
$timestampField = '';
switch ($status) {
    case 'accepted':
        $timestampField = "accepted_at = NOW(), rejected_at = NULL, completed_at = NULL";
        break;
    case 'in_progress':
        // Ensure an accepted timestamp exists when moving to in-progress
        $timestampField = "accepted_at = IFNULL(accepted_at, NOW())";
        break;
    case 'completed':
        $timestampField = "completed_at = NOW()";
        break;
    case 'rejected':
        $timestampField = "rejected_at = NOW()";
        break;
    default:
        $timestampField = "";
}

// Note: 'in_progress' doesn't have a dedicated column in schema; we'll only update status for that.
$sql = "UPDATE referrals SET status = ?" . ($timestampField ? ", $timestampField" : "") . " WHERE id = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    sendError('Database error preparing statement', 500);
}
$stmt->bind_param('si', $status, $referralId);
$ok = $stmt->execute();
if (!$ok) {
    sendError('Unable to update referral status', 500);
}
$stmt->close();

// Load referral recipient details for notification
$detailStmt = $conn->prepare(
    'SELECT r.id, p.first_name AS patient_first_name, p.last_name AS patient_last_name, u.email AS referrer_email, u.phone AS referrer_phone, f2.name AS receiving_facility_name
     FROM referrals r
     JOIN patients p ON r.patient_id = p.id
     JOIN users u ON r.referring_co_id = u.id
     JOIN facilities f2 ON r.receiving_facility_id = f2.id
     WHERE r.id = ?'
);
$detailStmt->bind_param('i', $referralId);
$detailStmt->execute();
$detailResult = $detailStmt->get_result();
$detail = $detailResult->fetch_assoc();
$detailStmt->close();

if ($detail) {
    notifyReferralStatusChange(
        $conn,
        $referralId,
        $status,
        $detail['referrer_email'],
        $detail['referrer_phone'],
        trim($detail['patient_first_name'] . ' ' . $detail['patient_last_name']),
        $detail['receiving_facility_name']
    );
}

sendResponse(['success' => true, 'message' => 'Referral status updated']);

?>
