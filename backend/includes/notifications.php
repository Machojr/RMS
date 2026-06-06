<?php
// ===========================================
// Notification Helper
// ===========================================

function createNotification($conn, $referralId, $recipientEmail, $recipientPhone, $subject, $message, $type = 'email', $status = 'pending') {
    $stmt = $conn->prepare(
        'INSERT INTO notifications (referral_id, type, recipient_email, recipient_phone, subject, message, status) VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('issssss', $referralId, $type, $recipientEmail, $recipientPhone, $subject, $message, $status);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}

function notifyReferralStatusChange($conn, $referralId, $status, $recipientEmail, $recipientPhone, $patientName, $receivingFacilityName) {
    $subject = 'Referral status updated';
    $message = "Referral #{$referralId} for {$patientName} is now {$status}.";
    if ($status === 'accepted') {
        $message = "Referral #{$referralId} for {$patientName} has been accepted by {$receivingFacilityName}.";
    } elseif ($status === 'in_progress') {
        $message = "Referral #{$referralId} for {$patientName} is now in progress at {$receivingFacilityName}.";
    } elseif ($status === 'completed') {
        $message = "Referral #{$referralId} for {$patientName} has been completed by {$receivingFacilityName}.";
    } elseif ($status === 'rejected') {
        $message = "Referral #{$referralId} for {$patientName} has been rejected by {$receivingFacilityName}.";
    }

    $saved = false;
    if (!empty($recipientEmail)) {
        $saved = createNotification($conn, $referralId, $recipientEmail, $recipientPhone, $subject, $message, 'email', 'pending');
    }
    if (!empty($recipientPhone)) {
        createNotification($conn, $referralId, $recipientEmail, $recipientPhone, $subject, $message, 'sms', 'pending');
    }
    return $saved;
}

function notifyFeedbackCreated($conn, $referralId, $recipientEmail, $recipientPhone, $patientName) {
    $subject = 'Clinical feedback submitted';
    $message = "Feedback has been added for referral #{$referralId} ({$patientName}).";
    $saved = false;
    if (!empty($recipientEmail)) {
        $saved = createNotification($conn, $referralId, $recipientEmail, $recipientPhone, $subject, $message, 'email', 'pending');
    }
    if (!empty($recipientPhone)) {
        createNotification($conn, $referralId, $recipientEmail, $recipientPhone, $subject, $message, 'sms', 'pending');
    }
    return $saved;
}
?>