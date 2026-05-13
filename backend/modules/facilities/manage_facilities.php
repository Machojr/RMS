<?php
// ===========================================
// Facilities API - Get All Facilities
// GET /facilities/manage_facilities.php
// ===========================================

require_once __DIR__ . '/../../config/db.php';
require_once dirname(__DIR__, 2) . '/includes/session.php';

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendError('Method not allowed', 405);
}

// Check if user is logged in
if (!isLoggedIn()) {
    sendError('Authentication required', 401);
}

// Get all facilities
$result = $conn->query("
    SELECT id, name, tier, region, district, address, phone, email, capacity
    FROM facilities
    ORDER BY tier, name
");

if (!$result) {
    sendError('Database query failed');
}

$facilities = [];
while ($row = $result->fetch_assoc()) {
    $facilities[] = $row;
}

// Return facilities data
sendResponse([
    'success' => true,
    'facilities' => $facilities,
    'count' => count($facilities)
]);
?>