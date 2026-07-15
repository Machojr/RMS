<?php
// ===========================================
// Notification Helper
// ===========================================

function sendEmailNotification($recipientEmail, $subject, $message) {
    if (empty($recipientEmail)) {
        return ['sent' => false, 'error' => 'Recipient email is missing'];
    }

    $configPath = dirname(__DIR__) . '/config/mail.php';
    if (!file_exists($configPath)) {
        return ['sent' => false, 'error' => 'SMTP configuration file is missing'];
    }

    $mailConfig = require $configPath;
    $required = ['host', 'port', 'username', 'password', 'from_email', 'from_name'];
    foreach ($required as $key) {
        if (empty($mailConfig[$key])) {
            return ['sent' => false, 'error' => "SMTP configuration value '{$key}' is missing"];
        }
    }

    return sendSmtpEmail($mailConfig, $recipientEmail, $subject, $message);
}

function readSmtpResponse($socket) {
    $response = '';
    while (($line = fgets($socket, 515)) !== false) {
        $response .= $line;
        if (isset($line[3]) && $line[3] === ' ') {
            break;
        }
    }
    return $response;
}

function sendSmtpCommand($socket, $command, $expectedCodes) {
    fwrite($socket, $command . "\r\n");
    $response = readSmtpResponse($socket);
    $code = (int)substr($response, 0, 3);
    if (!in_array($code, $expectedCodes, true)) {
        return ['ok' => false, 'error' => trim($response)];
    }
    return ['ok' => true, 'response' => $response];
}

function sendSmtpEmail($config, $recipientEmail, $subject, $message) {
    $host = $config['host'];
    $port = (int)$config['port'];
    $timeout = isset($config['timeout']) ? (int)$config['timeout'] : 20;

    $socket = @stream_socket_client(
        "tcp://{$host}:{$port}",
        $errno,
        $errstr,
        $timeout,
        STREAM_CLIENT_CONNECT
    );

    if (!$socket) {
        return ['sent' => false, 'error' => "SMTP connection failed: {$errstr} ({$errno})"];
    }

    stream_set_timeout($socket, $timeout);
    $greeting = readSmtpResponse($socket);
    if ((int)substr($greeting, 0, 3) !== 220) {
        fclose($socket);
        return ['sent' => false, 'error' => 'SMTP greeting failed: ' . trim($greeting)];
    }

    $serverName = 'localhost';
    $commands = [
        ["EHLO {$serverName}", [250]],
    ];

    foreach ($commands as [$command, $codes]) {
        $result = sendSmtpCommand($socket, $command, $codes);
        if (!$result['ok']) {
            fclose($socket);
            return ['sent' => false, 'error' => 'SMTP command failed: ' . $result['error']];
        }
    }

    if (($config['encryption'] ?? '') === 'tls') {
        $result = sendSmtpCommand($socket, 'STARTTLS', [220]);
        if (!$result['ok']) {
            fclose($socket);
            return ['sent' => false, 'error' => 'SMTP STARTTLS failed: ' . $result['error']];
        }

        if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            fclose($socket);
            return ['sent' => false, 'error' => 'Unable to enable TLS encryption for SMTP'];
        }

        $result = sendSmtpCommand($socket, "EHLO {$serverName}", [250]);
        if (!$result['ok']) {
            fclose($socket);
            return ['sent' => false, 'error' => 'SMTP EHLO after TLS failed: ' . $result['error']];
        }
    }

    $authCommands = [
        ['AUTH LOGIN', [334]],
        [base64_encode($config['username']), [334]],
        [base64_encode($config['password']), [235]],
        ['MAIL FROM:<' . $config['from_email'] . '>', [250]],
        ['RCPT TO:<' . $recipientEmail . '>', [250, 251]],
        ['DATA', [354]],
    ];

    foreach ($authCommands as [$command, $codes]) {
        $result = sendSmtpCommand($socket, $command, $codes);
        if (!$result['ok']) {
            fclose($socket);
            return ['sent' => false, 'error' => 'SMTP command failed: ' . $result['error']];
        }
    }

    $safeSubject = str_replace(["\r", "\n"], ' ', $subject);
    $safeFromName = str_replace(['"', "\r", "\n"], '', $config['from_name']);
    $body = str_replace("\n.", "\n..", str_replace("\r\n", "\n", $message));
    $emailData = [
        'From: "' . $safeFromName . '" <' . $config['from_email'] . '>',
        'To: <' . $recipientEmail . '>',
        'Subject: ' . $safeSubject,
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        '',
        $body,
        '.',
    ];

    fwrite($socket, implode("\r\n", $emailData) . "\r\n");
    $response = readSmtpResponse($socket);
    if ((int)substr($response, 0, 3) !== 250) {
        fclose($socket);
        return ['sent' => false, 'error' => 'SMTP DATA failed: ' . trim($response)];
    }

    sendSmtpCommand($socket, 'QUIT', [221]);
    fclose($socket);

    return ['sent' => true, 'error' => null];
}

function createNotification($conn, $referralId, $recipientEmail, $recipientPhone, $subject, $message, $type = 'email', $status = 'pending', $senderUserId = null, $recipientUserId = null, $replyToNotificationId = null) {
    $errorMessage = null;
    if ($type === 'email') {
        $emailResult = sendEmailNotification($recipientEmail, $subject, $message);
        $status = $emailResult['sent'] ? 'sent' : 'failed';
        $errorMessage = $emailResult['error'];
    }

    $stmt = $conn->prepare(
        'INSERT INTO notifications (
            referral_id, sender_user_id, recipient_user_id, reply_to_notification_id,
            type, recipient_email, recipient_phone, subject, message, status, error_message
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param(
        'iiiisssssss',
        $referralId,
        $senderUserId,
        $recipientUserId,
        $replyToNotificationId,
        $type,
        $recipientEmail,
        $recipientPhone,
        $subject,
        $message,
        $status,
        $errorMessage
    );
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

function notifyReceivingReceptionistOfReferral($conn, $referralId) {
    $stmt = $conn->prepare(
        'SELECT
            r.id,
            r.urgency,
            r.clinical_reason,
            p.first_name AS patient_first_name,
            p.last_name AS patient_last_name,
            rf.name AS referring_facility,
            tf.name AS receiving_facility,
            sender.id AS sender_user_id,
            sender.first_name AS sender_first_name,
            sender.last_name AS sender_last_name,
            receptionist.id AS receptionist_user_id,
            receptionist.email AS receptionist_email,
            receptionist.phone AS receptionist_phone
         FROM referrals r
         JOIN patients p ON r.patient_id = p.id
         JOIN facilities rf ON r.referring_facility_id = rf.id
         JOIN facilities tf ON r.receiving_facility_id = tf.id
         JOIN users sender ON r.referring_co_id = sender.id
         JOIN users receptionist ON receptionist.facility_id = r.receiving_facility_id
            AND receptionist.role = "receptionist"
            AND receptionist.is_active = 1
         WHERE r.id = ?
         ORDER BY receptionist.id ASC
         LIMIT 1'
    );
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('i', $referralId);
    $stmt->execute();
    $result = $stmt->get_result();
    $detail = $result->fetch_assoc();
    $stmt->close();

    if (!$detail || empty($detail['receptionist_email'])) {
        return false;
    }

    $patientName = trim($detail['patient_first_name'] . ' ' . $detail['patient_last_name']);
    $senderName = trim($detail['sender_first_name'] . ' ' . $detail['sender_last_name']);
    $subject = "New referral #{$referralId} received";
    $message = "A new referral has been sent to {$detail['receiving_facility']}.\n\n";
    $message .= "Referral: #{$referralId}\n";
    $message .= "Patient: {$patientName}\n";
    $message .= "From: {$detail['referring_facility']}\n";
    $message .= "Sent by: {$senderName}\n";
    $message .= "Urgency: {$detail['urgency']}\n";
    $message .= "Clinical reason: {$detail['clinical_reason']}\n\n";
    $message .= "Please review this referral in the Feedback section and continue communication there.";

    return createNotification(
        $conn,
        $referralId,
        $detail['receptionist_email'],
        $detail['receptionist_phone'],
        $subject,
        $message,
        'email',
        'pending',
        (int)$detail['sender_user_id'],
        (int)$detail['receptionist_user_id']
    );
}
?>
