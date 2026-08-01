<?php
// backend/scripts/migrate_super_admin.php
// Endesha hii mara MOJA tu kwa browser, kisha uifute kwa usalama.

require_once '../config/db.php';

echo "<h2>RMS Super Admin Migration</h2>";
echo "<pre>";

// 1. Angalia kama column ya 'role' tayari ina 'super_admin'
$check_role = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'role'");
$row = mysqli_fetch_assoc($check_role);
$current_type = $row['Type'] ?? '';

if (strpos($current_type, 'super_admin') === false) {
    // Badilisha ENUM
    $sql1 = "ALTER TABLE users MODIFY COLUMN role ENUM('co', 'receptionist', 'moh', 'super_admin') NOT NULL";
    if (mysqli_query($conn, $sql1)) {
        echo "✅ 1. Role ENUM imebadilishwa kuongeza 'super_admin'.\n";
    } else {
        echo "❌ 1. Imeshindwa kubadilisha role: " . mysqli_error($conn) . "\n";
    }
} else {
    echo "ℹ️  1. Role ENUM tayari ina 'super_admin'. Hakuna mabadiliko.\n";
}

// 2. Hakikisha facility_id inaruhusu NULL
$sql2 = "ALTER TABLE users MODIFY COLUMN facility_id INT NULL";
if (mysqli_query($conn, $sql2)) {
    echo "✅ 2. facility_id imebadilishwa kuruhusu NULL.\n";
} else {
    echo "❌ 2. Imeshindwa kubadilisha facility_id: " . mysqli_error($conn) . "\n";
}

// 3. Angalia kama Super Admin tayari yupo
$check_user = mysqli_query($conn, "SELECT id FROM users WHERE email = 'admin@rms.go.tz'");
if (mysqli_num_rows($check_user) == 0) {
    $sql3 = "INSERT INTO users (email, password, role, first_name, last_name, facility_id, phone, is_active) 
             VALUES ('admin@rms.go.tz', 'Admin@2026', 'super_admin', 'System', 'Administrator', NULL, '+255 700 000 000', 1)";
    if (mysqli_query($conn, $sql3)) {
        echo "✅ 3. Super Admin (admin@rms.go.tz / password: Admin@2026) imeingizwa.\n";
    } else {
        echo "❌ 3. Imeshindwa kuingiza Super Admin: " . mysqli_error($conn) . "\n";
    }
} else {
    echo "ℹ️  3. Super Admin tayari yupo kwenye database.\n";
}

echo "\n🎉 Migration imekamilika! Sasa unaweza kufuta faili hili (migrate_super_admin.php) kwa usalama.";
echo "</pre>";
?>