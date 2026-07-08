<?php
// Debug endpoint - shows data availability
require_once __DIR__ . '/../../config/db.php';

// Check data
$referrals_count = $conn->query("SELECT COUNT(*) as c FROM referrals")->fetch_assoc();
$departments_count = $conn->query("SELECT COUNT(*) as c FROM departments")->fetch_assoc();
$doctors_count = $conn->query("SELECT COUNT(*) as c FROM doctors")->fetch_assoc();

echo json_encode([
    'referrals' => $referrals_count['c'],
    'departments' => $departments_count['c'],
    'doctors' => $doctors_count['c'],
    'sample_referral' => $conn->query("SELECT id, receiving_facility_id FROM referrals LIMIT 1")->fetch_assoc(),
    'sample_departments' => $conn->query("SELECT id, name, facility_id FROM departments LIMIT 3")->fetch_all(MYSQLI_ASSOC),
], JSON_PRETTY_PRINT);
?>
