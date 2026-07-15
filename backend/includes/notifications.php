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
?>
