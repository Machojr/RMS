<?php
function logAudit($conn, $user, $action, $referralId = null, $previousStatus = null, $newStatus = null, $remarks = null) {
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
    $stmt = $conn->prepare(
        'INSERT INTO audit_logs (user_id, role, action, referral_id, previous_status, new_status, remarks, ip_address)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
    );
    if (!$stmt) {
        return false;
    }

    $userId = isset($user['id']) ? (int)$user['id'] : null;
    $role = $user['role'] ?? null;
    $stmt->bind_param('ississss', $userId, $role, $action, $referralId, $previousStatus, $newStatus, $remarks, $ipAddress);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}
?>
