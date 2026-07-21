<?php
// ===========================================
// Users API - List Users
// GET /users/list.php
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
if ($user['role'] !== 'admin') {
    sendError('Only admin can manage users', 403);
}

$query = "
    SELECT
        u.id,
        u.email,
        u.role,
        u.first_name,
        u.last_name,
        u.phone,
        u.facility_id,
        u.is_active,
        u.created_at,
        f.name AS facility_name,
        doc.id AS doctor_id,
        doc.department_id,
        doc.license_number,
        doc.contact_phone,
        dep.name AS department_name
    FROM users u
    LEFT JOIN facilities f ON u.facility_id = f.id
    LEFT JOIN doctors doc ON doc.user_id = u.id
    LEFT JOIN departments dep ON dep.id = doc.department_id
    ORDER BY u.created_at DESC, u.id DESC
";

$stmt = $conn->prepare($query);
if (!$stmt) {
    sendError('Database error preparing users list', 500);
}
$stmt->execute();
$result = $stmt->get_result();
$users = [];
while ($row = $result->fetch_assoc()) {
    $row['full_name'] = trim($row['first_name'] . ' ' . $row['last_name']);
    $users[] = $row;
}
$stmt->close();

sendResponse([
    'success' => true,
    'users' => $users,
    'count' => count($users),
]);
?>
