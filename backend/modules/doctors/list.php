<?php
// ===========================================
// Doctors API - List Doctors by Facility/Department
// GET /doctors/list.php
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
$departmentId = isset($_GET['department_id']) ? (int)$_GET['department_id'] : 0;

if ($facilityId <= 0) {
    sendError('facility_id is required', 400);
}

$query = "
    SELECT
        doc.id,
        u.id AS user_id,
        u.first_name,
        u.last_name,
        u.email,
        u.phone,
        doc.license_number,
        doc.contact_phone,
        dep.id AS department_id,
        dep.name AS department_name
    FROM doctors doc
    JOIN users u ON doc.user_id = u.id
    JOIN departments dep ON doc.department_id = dep.id
    WHERE dep.facility_id = ?";

$params = 'i';
$values = [$facilityId];
if ($departmentId > 0) {
    $query .= ' AND dep.id = ?';
    $params .= 'i';
    $values[] = $departmentId;
}
$query .= ' ORDER BY dep.name, u.last_name, u.first_name';

$stmt = $conn->prepare($query);
if (!$stmt) {
    sendError('Database error preparing statement', 500);
}
$stmt->bind_param($params, ...$values);
$stmt->execute();
$result = $stmt->get_result();
$doctors = [];
while ($row = $result->fetch_assoc()) {
    $row['full_name'] = trim($row['first_name'] . ' ' . $row['last_name']);
    $doctors[] = $row;
}
$stmt->close();

sendResponse([
    'success' => true,
    'doctors' => $doctors,
    'count' => count($doctors),
]);
?>