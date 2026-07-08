<?php
// ===========================================
// Referrals API - Assign a doctor to a referral
// POST /referrals/assign_doctor.php
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

validateRequired($input, ['referral_id', 'doctor_id']);

$referralId = (int)$input['referral_id'];
$doctorId = (int)$input['doctor_id'];

$user = getCurrentUser();
if ($user['role'] !== 'receptionist') {
    sendError('Only receptionists can assign doctors', 403);
}

if (!canAccessReferral($referralId, $conn)) {
    sendError('Access denied', 403);
}

$verifyStmt = $conn->prepare(
    'SELECT r.receiving_facility_id, r.receiving_department_id
     FROM referrals r
     WHERE r.id = ?'
);
$verifyStmt->bind_param('i', $referralId);
$verifyStmt->execute();
$referralResult = $verifyStmt->get_result();
if ($referralResult->num_rows === 0) {
    sendError('Referral not found', 404);
}
$referral = $referralResult->fetch_assoc();
$verifyStmt->close();

$doctorStmt = $conn->prepare(
    'SELECT doc.id
     FROM doctors doc
     JOIN departments dep ON doc.department_id = dep.id
     WHERE doc.id = ?
       AND dep.facility_id = ?
       AND dep.id = ?'
);
$doctorStmt->bind_param('iii', $doctorId, $referral['receiving_facility_id'], $referral['receiving_department_id']);
$doctorStmt->execute();
$doctorResult = $doctorStmt->get_result();
if ($doctorResult->num_rows === 0) {
    sendError('Doctor is not assigned to the referral department or facility', 403);
}
$doctorStmt->close();

$updateStmt = $conn->prepare('UPDATE referrals SET assigned_doctor_id = ? WHERE id = ?');
$updateStmt->bind_param('ii', $doctorId, $referralId);
if (!$updateStmt->execute()) {
    sendError('Unable to assign doctor', 500);
}
$updateStmt->close();

sendResponse(['success' => true, 'message' => 'Doctor assigned successfully']);
?>