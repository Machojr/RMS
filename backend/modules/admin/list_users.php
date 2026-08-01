<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

// Unganisha database kwa njia salama
require_once __DIR__ . '/../../config/db.php';

// Angalia connection
if (!$conn) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

// Anzisha session
session_start();

// Angalia kama Admin wa mfumo
$role = $_SESSION['user_role'] ?? $_SESSION['role'] ?? null;
if (!isset($_SESSION['user_id']) || !in_array($role, ['admin', 'super_admin'], true)) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Query rahisi kabisa
$query = "
    SELECT
        u.id,
        u.email,
        CASE WHEN u.role = 'super_admin' THEN 'admin' ELSE u.role END AS role,
        u.first_name,
        u.last_name,
        u.phone,
        u.is_active,
        f.name AS facility,
        d.license_number,
        dep.name AS department
    FROM users u
    LEFT JOIN facilities f ON f.id = u.facility_id
    LEFT JOIN doctors d ON d.user_id = u.id
    LEFT JOIN departments dep ON dep.id = d.department_id
    ORDER BY u.id DESC
";
$result = mysqli_query($conn, $query);

if (!$result) {
    http_response_code(500);
    echo json_encode(['error' => 'Query failed: ' . mysqli_error($conn)]);
    exit;
}

$users = [];
while ($row = mysqli_fetch_assoc($result)) {
    $users[] = [
        'id' => $row['id'],
        'email' => $row['email'],
        'role' => $row['role'],
        'first_name' => $row['first_name'],
        'last_name' => $row['last_name'],
        'phone' => $row['phone'] ?? '',
        'is_active' => (bool)$row['is_active'],
        'facility' => $row['facility'] ?? 'N/A',
        'license_number' => $row['license_number'] ?? null,
        'department' => $row['department'] ?? null,
    ];
}

echo json_encode(['success' => true, 'count' => count($users), 'users' => $users]);
exit;
?>
