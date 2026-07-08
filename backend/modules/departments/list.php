<?php
// ===========================================
// Departments API - List Departments by Facility
// GET /departments/list.php
// ===========================================

require_once __DIR__ . '/../../config/db.php';
require_once dirname(__DIR__, 2) . '/includes/session.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendError('Method not allowed', 405);
}

if (!isLoggedIn()) {
    sendError('Authentication required', 401);
}

$facilityId = isset($_GET['facility_id']) ? (int)$_GET['facility_id'] : 0;
if ($facilityId <= 0) {
    sendError('facility_id is required', 400);
}

$stmt = $conn->prepare('SELECT id, name FROM departments WHERE facility_id = ? ORDER BY name');
$stmt->bind_param('i', $facilityId);
$stmt->execute();
$result = $stmt->get_result();
$departments = [];
while ($row = $result->fetch_assoc()) {
    $departments[] = $row;
}
$stmt->close();

sendResponse([
    'success' => true,
    'departments' => $departments,
    'count' => count($departments),
]);
?>