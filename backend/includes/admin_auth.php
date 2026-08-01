<?php
// backend/includes/admin_auth.php
// Admin helpers for system-wide user and reporting management.

require_once __DIR__ . '/session.php'; // Tunaingiza session.php iliyopo

function isSystemAdmin() {
    $role = $_SESSION['user_role'] ?? $_SESSION['role'] ?? null;
    return isset($_SESSION['user_id']) && in_array($role, ['admin', 'super_admin'], true);
}
?>
