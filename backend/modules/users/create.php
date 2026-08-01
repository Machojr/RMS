<?php
// ===========================================
// Users API - Create User
// POST /users/create.php
// ===========================================

require_once __DIR__ . '/../../config/db.php';
require_once dirname(__DIR__, 2) . '/includes/session.php';
require_once dirname(__DIR__, 2) . '/includes/audit.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError('Method not allowed', 405);
}

if (!isLoggedIn()) {
    sendError('Authentication required', 401);
}

$admin = getCurrentUser();
if (!in_array($admin['role'], ['admin', 'super_admin'], true)) {
    sendError('Only admin can create users', 403);
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    sendError('Invalid JSON input');
}

validateRequired($input, ['first_name', 'last_name', 'email', 'password', 'role']);

$firstName = trim($input['first_name']);
$lastName = trim($input['last_name']);
$email = strtolower(trim($input['email']));
$password = (string)$input['password'];
$role = trim($input['role']);
$phone = !empty($input['phone']) ? trim($input['phone']) : null;
$facilityId = !empty($input['facility_id']) ? (int)$input['facility_id'] : null;
$departmentId = !empty($input['department_id']) ? (int)$input['department_id'] : null;
$licenseNumber = !empty($input['license_number']) ? trim($input['license_number']) : null;
$contactPhone = !empty($input['contact_phone']) ? trim($input['contact_phone']) : $phone;

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    sendError('Invalid email format', 400);
}

if ($role === 'super_admin') {
    $role = 'admin';
}

if (!in_array($role, ['admin', 'co', 'receptionist'], true)) {
    sendError('Invalid role selected', 400);
}

if (strlen($password) < 8) {
    sendError('Password must be at least 8 characters', 400);
}

if (in_array($role, ['co', 'receptionist'], true) && !$facilityId) {
    sendError('Facility is required for CO and Receptionist users', 400);
}

if ($role === 'co' && (!$departmentId || !$licenseNumber)) {
    sendError('Department and doctor/CO license number are required for CO users', 400);
}

$emailStmt = $conn->prepare('SELECT id FROM users WHERE email = ?');
if (!$emailStmt) {
    sendError('Database error preparing email check', 500);
}
$emailStmt->bind_param('s', $email);
$emailStmt->execute();
if ($emailStmt->get_result()->num_rows > 0) {
    sendError('Email is already registered', 409);
}
$emailStmt->close();

if ($facilityId) {
    $facilityStmt = $conn->prepare('SELECT id FROM facilities WHERE id = ?');
    $facilityStmt->bind_param('i', $facilityId);
    $facilityStmt->execute();
    if ($facilityStmt->get_result()->num_rows === 0) {
        sendError('Selected facility does not exist', 404);
    }
    $facilityStmt->close();
}

if ($role === 'co') {
    $departmentStmt = $conn->prepare('SELECT id FROM departments WHERE id = ? AND facility_id = ?');
    $departmentStmt->bind_param('ii', $departmentId, $facilityId);
    $departmentStmt->execute();
    if ($departmentStmt->get_result()->num_rows === 0) {
        sendError('Selected department does not belong to selected facility', 400);
    }
    $departmentStmt->close();
}

$passwordHash = password_hash($password, PASSWORD_DEFAULT);
$conn->begin_transaction();

try {
    $stmt = $conn->prepare(
        'INSERT INTO users (email, password, role, first_name, last_name, facility_id, phone, is_active)
         VALUES (?, ?, ?, ?, ?, ?, ?, 1)'
    );
    if (!$stmt) {
        throw new Exception('Unable to prepare user insert');
    }
    $stmt->bind_param('sssssis', $email, $passwordHash, $role, $firstName, $lastName, $facilityId, $phone);
    if (!$stmt->execute()) {
        throw new Exception('Unable to create user');
    }
    $newUserId = $stmt->insert_id;
    $stmt->close();

    if ($role === 'co') {
        $doctorStmt = $conn->prepare(
            'INSERT INTO doctors (user_id, department_id, license_number, contact_phone)
             VALUES (?, ?, ?, ?)'
        );
        if (!$doctorStmt) {
            throw new Exception('Unable to prepare doctor profile insert');
        }
        $doctorStmt->bind_param('iiss', $newUserId, $departmentId, $licenseNumber, $contactPhone);
        if (!$doctorStmt->execute()) {
            throw new Exception('Unable to create doctor/CO profile');
        }
        $doctorStmt->close();
    }

    logAudit($conn, $admin, 'admin_created_user', null, null, null, "Created {$role} user {$email}");
    $conn->commit();
} catch (Exception $e) {
    $conn->rollback();
    sendError($e->getMessage(), 500);
}

sendResponse([
    'success' => true,
    'message' => 'User created successfully',
    'user_id' => $newUserId,
]);
?>
