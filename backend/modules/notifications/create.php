<?php
// ===========================================
// Notifications API - Create Notification
// POST /notifications/create.php
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

$user = getCurrentUser();
if ($user['role'] !== 'receptionist') {
    sendError('Only receptionists can create referral notifications', 403);
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    sendError('Invalid JSON input');
}

validateRequired($input, ['referral_id', 'department_id', 'recipient_doctor_id', 'notification_type']);

$referralId = (int)$input['referral_id'];
$departmentId = (int)$input['department_id'];
$recipientDoctorId = (int)$input['recipient_doctor_id'];
$notificationType = trim($input['notification_type']);
$note = isset($input['note']) ? trim($input['note']) : null;

if ($notificationType !== 'email' && $notificationType !== 'sms' && $notificationType !== 'both') {
    sendError('notification_type must be email, sms, or both', 400);
}

// ensure receptionist controls referral for their facility
$referralStmt = $conn->prepare(
    'SELECT r.id, r.receiving_facility_id, r.receiving_department_id, r.patient_id, p.first_name AS patient_first_name, p.last_name AS patient_last_name, f.name AS facility_name, r.urgency, r.clinical_reason, r.requested_services
     FROM referrals r
     JOIN patients p ON r.patient_id = p.id
     JOIN facilities f ON r.receiving_facility_id = f.id
     WHERE r.id = ? AND r.receiving_facility_id = ?'
);
$referralStmt->bind_param('ii', $referralId, $user['facility_id']);
$referralStmt->execute();
$referralResult = $referralStmt->get_result();
if ($referralResult->num_rows === 0) {
    sendError('Referral not found or not accessible', 404);
}
$referral = $referralResult->fetch_assoc();
$referralStmt->close();

// ensure recipient is a doctor in the selected department and receiving facility
$recipientStmt = $conn->prepare(
    'SELECT u.id AS user_id, u.email, u.phone, u.first_name, u.last_name, dep.name AS department_name
     FROM doctors doc
     JOIN users u ON doc.user_id = u.id
     JOIN departments dep ON doc.department_id = dep.id
     WHERE doc.id = ?
       AND doc.department_id = ?
       AND dep.facility_id = ?'
);
$recipientStmt->bind_param('iii', $recipientDoctorId, $departmentId, $referral['receiving_facility_id']);
$recipientStmt->execute();
$recipientResult = $recipientStmt->get_result();
if ($recipientResult->num_rows === 0) {
    sendError('Recipient doctor is not authorized for this referral', 403);
}
$recipient = $recipientResult->fetch_assoc();
$recipientStmt->close();

$recipientEmail = strtolower(trim($recipient['email'] ?? ''));
if (($notificationType === 'email' || $notificationType === 'both')
    && ($recipientEmail === '' || substr($recipientEmail, -10) === '@rms.go.tz')
) {
    sendError('Selected doctor must have a real email address before email notifications can be sent', 400);
}

$assignStmt = $conn->prepare(
    'UPDATE referrals
     SET receiving_department_id = ?, assigned_doctor_id = ?
     WHERE id = ? AND receiving_facility_id = ?'
);
$assignStmt->bind_param('iiii', $departmentId, $recipientDoctorId, $referralId, $referral['receiving_facility_id']);
if (!$assignStmt->execute()) {
    sendError('Unable to assign notification department and doctor to referral', 500);
}
$assignStmt->close();

$patientName = trim($referral['patient_first_name'] . ' ' . $referral['patient_last_name']);
$subject = "Referral #{$referralId} notification";
$message = "Referral #{$referralId} for {$patientName}\n";
$message .= "Receiving facility: {$referral['facility_name']}\n";
$message .= "Department: {$recipient['department_name']}\n";
$message .= "Urgency: {$referral['urgency']}\n";
$message .= "Clinical reason: {$referral['clinical_reason']}\n";
$message .= "Requested services: {$referral['requested_services']}\n";
if (!empty($note)) {
    $message .= "\nNote from receptionist: {$note}\n";
}

$created = false;
if ($notificationType === 'email' || $notificationType === 'both') {
    if (!empty($recipient['email'])) {
        $created = createNotification(
            $conn,
            $referralId,
            $recipient['email'],
            $recipient['phone'],
            $subject,
            $message,
            'email',
            'pending',
            $user['id'],
            $recipient['user_id']
        ) || $created;
    }
}
if ($notificationType === 'sms' || $notificationType === 'both') {
    if (!empty($recipient['phone'])) {
        $created = createNotification(
            $conn,
            $referralId,
            $recipient['email'],
            $recipient['phone'],
            $subject,
            $message,
            'sms',
            'pending',
            $user['id'],
            $recipient['user_id']
        ) || $created;
    }
}

if (!$created) {
    sendError('Unable to create notification; recipient contact details are missing', 500);
}

sendResponse(['success' => true, 'message' => 'Notification created successfully']);
?>
