<?php
// ===========================================
// Users API - Admin User Management
// GET    /users/manage.php?action=list
// POST   /users/manage.php?action=create
// PUT    /users/manage.php?action=update&id=1
// DELETE /users/manage.php?action=delete&id=1
// ===========================================

require_once __DIR__ . '/../../config/db.php';
require_once dirname(__DIR__, 2) . '/includes/session.php';
require_once dirname(__DIR__, 2) . '/includes/audit.php';

if (!isLoggedIn()) {
    sendError('Authentication required', 401);
}

$admin = getCurrentUser();
if (!in_array($admin['role'], ['admin', 'super_admin'], true)) {
    sendError('Only admin can manage users', 403);
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'list';
$allowedRoles = ['admin', 'co', 'receptionist'];

function getJsonInput(): array {
    $input = json_decode(file_get_contents('php://input'), true);
    return is_array($input) ? $input : [];
}

function normalizeUserRole(string $role): string {
    return $role === 'super_admin' ? 'admin' : $role;
}

function validateFacility(mysqli $conn, ?int $facilityId): void {
    if (!$facilityId) {
        return;
    }

    $stmt = $conn->prepare('SELECT id FROM facilities WHERE id = ?');
    $stmt->bind_param('i', $facilityId);
    $stmt->execute();
    if ($stmt->get_result()->num_rows === 0) {
        sendError('Selected facility does not exist', 404);
    }
    $stmt->close();
}

function validateDepartment(mysqli $conn, ?int $departmentId, ?int $facilityId): void {
    if (!$departmentId || !$facilityId) {
        sendError('Department and facility are required for CO / Doctor users', 400);
    }

    $stmt = $conn->prepare('SELECT id FROM departments WHERE id = ? AND facility_id = ?');
    $stmt->bind_param('ii', $departmentId, $facilityId);
    $stmt->execute();
    if ($stmt->get_result()->num_rows === 0) {
        sendError('Selected department does not belong to selected facility', 400);
    }
    $stmt->close();
}

if ($method === 'GET' && $action === 'list') {
    $query = "
        SELECT
            u.id,
            u.email,
            CASE WHEN u.role = 'super_admin' THEN 'admin' ELSE u.role END AS role,
            u.first_name,
            u.last_name,
            u.phone,
            u.facility_id,
            u.is_active,
            u.created_at,
            f.name AS facility,
            doc.id AS doctor_id,
            doc.department_id,
            doc.license_number,
            doc.contact_phone,
            dep.name AS department
        FROM users u
        LEFT JOIN facilities f ON f.id = u.facility_id
        LEFT JOIN doctors doc ON doc.user_id = u.id
        LEFT JOIN departments dep ON dep.id = doc.department_id
        ORDER BY u.created_at DESC, u.id DESC
    ";

    $result = $conn->query($query);
    if (!$result) {
        sendError('Database query failed: ' . $conn->error, 500);
    }

    $users = [];
    while ($row = $result->fetch_assoc()) {
        $row['id'] = (int)$row['id'];
        $row['facility_id'] = $row['facility_id'] !== null ? (int)$row['facility_id'] : null;
        $row['doctor_id'] = $row['doctor_id'] !== null ? (int)$row['doctor_id'] : null;
        $row['department_id'] = $row['department_id'] !== null ? (int)$row['department_id'] : null;
        $row['is_active'] = (bool)$row['is_active'];
        $row['full_name'] = trim($row['first_name'] . ' ' . $row['last_name']);
        $users[] = $row;
    }

    sendResponse([
        'success' => true,
        'count' => count($users),
        'users' => $users,
    ]);
}

if ($method === 'POST' && $action === 'create') {
    $input = getJsonInput();
    validateRequired($input, ['first_name', 'last_name', 'email', 'password', 'role']);

    $firstName = trim($input['first_name']);
    $lastName = trim($input['last_name']);
    $email = strtolower(trim($input['email']));
    $password = (string)$input['password'];
    $role = normalizeUserRole(trim($input['role']));
    $phone = !empty($input['phone']) ? trim($input['phone']) : null;
    $facilityId = !empty($input['facility_id']) ? (int)$input['facility_id'] : null;
    $departmentId = !empty($input['department_id']) ? (int)$input['department_id'] : null;
    $licenseNumber = !empty($input['license_number']) ? trim($input['license_number']) : null;
    $contactPhone = !empty($input['contact_phone']) ? trim($input['contact_phone']) : $phone;

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        sendError('Invalid email format', 400);
    }
    if (!in_array($role, ['admin', 'co', 'receptionist'], true)) {
        sendError('Invalid role selected', 400);
    }
    if (strlen($password) < 8) {
        sendError('Password must be at least 8 characters', 400);
    }
    if (in_array($role, ['co', 'receptionist'], true) && !$facilityId) {
        sendError('Facility is required for CO / Doctor and Receptionist users', 400);
    }
    if ($role === 'co' && !$licenseNumber) {
        sendError('License number is required for CO / Doctor users', 400);
    }

    validateFacility($conn, $facilityId);
    if ($role === 'co') {
        validateDepartment($conn, $departmentId, $facilityId);
    }

    $emailStmt = $conn->prepare('SELECT id FROM users WHERE email = ?');
    $emailStmt->bind_param('s', $email);
    $emailStmt->execute();
    if ($emailStmt->get_result()->num_rows > 0) {
        sendError('Email is already registered', 409);
    }
    $emailStmt->close();

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    $conn->begin_transaction();

    try {
        $stmt = $conn->prepare(
            'INSERT INTO users (email, password, role, first_name, last_name, facility_id, phone, is_active)
             VALUES (?, ?, ?, ?, ?, ?, ?, 1)'
        );
        $stmt->bind_param('sssssis', $email, $passwordHash, $role, $firstName, $lastName, $facilityId, $phone);
        if (!$stmt->execute()) {
            throw new Exception($stmt->error);
        }
        $newUserId = $stmt->insert_id;
        $stmt->close();

        if ($role === 'co') {
            $doctorStmt = $conn->prepare(
                'INSERT INTO doctors (user_id, department_id, license_number, contact_phone)
                 VALUES (?, ?, ?, ?)'
            );
            $doctorStmt->bind_param('iiss', $newUserId, $departmentId, $licenseNumber, $contactPhone);
            if (!$doctorStmt->execute()) {
                throw new Exception($doctorStmt->error);
            }
            $doctorStmt->close();
        }

        logAudit($conn, $admin, 'admin_created_user', $newUserId, null, $role, "Created user {$email}");
        $conn->commit();
    } catch (Exception $e) {
        $conn->rollback();
        sendError('Unable to create user: ' . $e->getMessage(), 500);
    }

    sendResponse(['success' => true, 'message' => 'User created successfully', 'user_id' => $newUserId], 201);
}

if ($method === 'PUT' && $action === 'update') {
    $userId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($userId <= 0) {
        sendError('User id is required', 400);
    }
    if ($userId === (int)$admin['id']) {
        sendError('Use a separate account settings screen to edit the logged-in admin', 400);
    }

    $input = getJsonInput();
    validateRequired($input, ['first_name', 'last_name', 'email', 'role']);

    $firstName = trim($input['first_name']);
    $lastName = trim($input['last_name']);
    $email = strtolower(trim($input['email']));
    $role = normalizeUserRole(trim($input['role']));
    $phone = !empty($input['phone']) ? trim($input['phone']) : null;
    $facilityId = !empty($input['facility_id']) ? (int)$input['facility_id'] : null;
    $departmentId = !empty($input['department_id']) ? (int)$input['department_id'] : null;
    $licenseNumber = !empty($input['license_number']) ? trim($input['license_number']) : null;
    $contactPhone = !empty($input['contact_phone']) ? trim($input['contact_phone']) : $phone;
    $isActive = isset($input['is_active']) ? (int)(bool)$input['is_active'] : 1;

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        sendError('Invalid email format', 400);
    }
    if (!in_array($role, ['admin', 'co', 'receptionist'], true)) {
        sendError('Invalid role selected', 400);
    }
    if (in_array($role, ['co', 'receptionist'], true) && !$facilityId) {
        sendError('Facility is required for CO / Doctor and Receptionist users', 400);
    }
    if ($role === 'co' && !$licenseNumber) {
        sendError('License number is required for CO / Doctor users', 400);
    }

    validateFacility($conn, $facilityId);
    if ($role === 'co') {
        validateDepartment($conn, $departmentId, $facilityId);
    }

    $emailStmt = $conn->prepare('SELECT id FROM users WHERE email = ? AND id <> ?');
    $emailStmt->bind_param('si', $email, $userId);
    $emailStmt->execute();
    if ($emailStmt->get_result()->num_rows > 0) {
        sendError('Email is already registered by another user', 409);
    }
    $emailStmt->close();

    $conn->begin_transaction();

    try {
        $stmt = $conn->prepare(
            'UPDATE users
             SET email = ?, role = ?, first_name = ?, last_name = ?, facility_id = ?, phone = ?, is_active = ?
             WHERE id = ?'
        );
        $stmt->bind_param('ssssisii', $email, $role, $firstName, $lastName, $facilityId, $phone, $isActive, $userId);
        if (!$stmt->execute()) {
            throw new Exception($stmt->error);
        }
        if ($stmt->affected_rows === 0) {
            $exists = $conn->prepare('SELECT id FROM users WHERE id = ?');
            $exists->bind_param('i', $userId);
            $exists->execute();
            if ($exists->get_result()->num_rows === 0) {
                throw new Exception('User not found');
            }
            $exists->close();
        }
        $stmt->close();

        if ($role === 'co') {
            $doctorCheck = $conn->prepare('SELECT id FROM doctors WHERE user_id = ?');
            $doctorCheck->bind_param('i', $userId);
            $doctorCheck->execute();
            $doctorRow = $doctorCheck->get_result()->fetch_assoc();
            $doctorCheck->close();

            if ($doctorRow) {
                $doctorStmt = $conn->prepare(
                    'UPDATE doctors SET department_id = ?, license_number = ?, contact_phone = ? WHERE user_id = ?'
                );
                $doctorStmt->bind_param('issi', $departmentId, $licenseNumber, $contactPhone, $userId);
            } else {
                $doctorStmt = $conn->prepare(
                    'INSERT INTO doctors (user_id, department_id, license_number, contact_phone)
                     VALUES (?, ?, ?, ?)'
                );
                $doctorStmt->bind_param('iiss', $userId, $departmentId, $licenseNumber, $contactPhone);
            }
            if (!$doctorStmt->execute()) {
                throw new Exception($doctorStmt->error);
            }
            $doctorStmt->close();
        } else {
            $doctorStmt = $conn->prepare('DELETE FROM doctors WHERE user_id = ?');
            $doctorStmt->bind_param('i', $userId);
            if (!$doctorStmt->execute()) {
                throw new Exception($doctorStmt->error);
            }
            $doctorStmt->close();
        }

        logAudit($conn, $admin, 'admin_updated_user', $userId, null, $role, "Updated user {$email}");
        $conn->commit();
    } catch (Exception $e) {
        $conn->rollback();
        sendError('Unable to update user: ' . $e->getMessage(), 500);
    }

    sendResponse(['success' => true, 'message' => 'User updated successfully']);
}

if ($method === 'DELETE' && $action === 'delete') {
    $userId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($userId <= 0) {
        sendError('User id is required', 400);
    }
    if ($userId === (int)$admin['id']) {
        sendError('You cannot deactivate the account you are currently using', 400);
    }

    $stmt = $conn->prepare('UPDATE users SET is_active = 0 WHERE id = ?');
    $stmt->bind_param('i', $userId);
    if (!$stmt->execute()) {
        sendError('Unable to deactivate user', 500);
    }
    $stmt->close();

    logAudit($conn, $admin, 'admin_deactivated_user', $userId, null, 'inactive', 'Deactivated user account');
    sendResponse(['success' => true, 'message' => 'User deactivated successfully']);
}

sendError('Invalid action or method', 404);
?>
