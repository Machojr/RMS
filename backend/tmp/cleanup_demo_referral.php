<?php
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$referralId = 1;
$conn->begin_transaction();

try {
    $stmt = $conn->prepare('DELETE FROM notifications WHERE referral_id = ?');
    $stmt->bind_param('i', $referralId);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare('DELETE FROM feedback WHERE referral_id = ?');
    $stmt->bind_param('i', $referralId);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare('DELETE FROM referrals WHERE id = ?');
    $stmt->bind_param('i', $referralId);
    $stmt->execute();
    $stmt->close();

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Demo referral removed']);
} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>