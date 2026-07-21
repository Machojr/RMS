<?php
// ===========================================
// Dashboard API - Summary Endpoint
// GET /dashboard/summary.php
// ===========================================

require_once __DIR__ . '/../../config/db.php';
require_once dirname(__DIR__, 2) . '/includes/session.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendError('Method not allowed', 405);
}

if (!isLoggedIn()) {
    sendError('Authentication required', 401);
}

$user = getCurrentUser();

// Compute dashboard summary values
$referralsCount = 0;
$facilitiesCount = 0;
$usersCount = 0;
$pendingCount = 0;

// Referral counts depend on user role
if ($user['role'] === 'admin' || $user['role'] === 'moh') {
    $referralSql = "SELECT COUNT(*) AS total, SUM(status = 'pending') AS pending FROM referrals";
    $refFacilitySql = "SELECT COUNT(*) AS total FROM facilities";
    $userSql = "SELECT COUNT(*) AS total FROM users WHERE is_active = TRUE";
} elseif ($user['role'] === 'receptionist') {
    $referralSql = "SELECT COUNT(*) AS total, SUM(status = 'pending') AS pending
        FROM referrals r
        JOIN users u ON r.referring_co_id = u.id
        WHERE u.facility_id = ?";
    $refFacilitySql = "SELECT COUNT(*) AS total FROM facilities";
    $userSql = "SELECT COUNT(*) AS total FROM users WHERE is_active = TRUE AND facility_id = ?";
} else {
    $referralSql = "SELECT COUNT(*) AS total, SUM(status = 'pending') AS pending FROM referrals WHERE referring_co_id = ?";
    $refFacilitySql = "SELECT COUNT(*) AS total FROM facilities";
    $userSql = "SELECT COUNT(*) AS total FROM users WHERE is_active = TRUE AND id = ?";
}

$stmt = $conn->prepare($referralSql);
if ($user['role'] === 'receptionist') {
    $stmt->bind_param('i', $user['facility_id']);
} elseif ($user['role'] === 'co') {
    $stmt->bind_param('i', $user['id']);
}
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $referralsCount = (int)$row['total'];
    $pendingCount = (int)$row['pending'];
}
$stmt->close();

$stmt = $conn->prepare($refFacilitySql);
$stmt->execute();
$result = $stmt->get_result();
$facilitiesCount = (int)$result->fetch_assoc()['total'];
$stmt->close();

$stmt = $conn->prepare($userSql);
if ($user['role'] === 'receptionist') {
    $stmt->bind_param('i', $user['facility_id']);
} elseif ($user['role'] === 'co') {
    $stmt->bind_param('i', $user['id']);
}
$stmt->execute();
$result = $stmt->get_result();
$usersCount = (int)$result->fetch_assoc()['total'];
$stmt->close();

sendResponse([
    'success' => true,
    'summary' => [
        'referrals' => $referralsCount,
        'pending_referrals' => $pendingCount,
        'facilities' => $facilitiesCount,
        'active_users' => $usersCount,
        'user' => $user,
    ],
]);
?>
