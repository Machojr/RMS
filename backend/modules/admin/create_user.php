<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST');
require_once '../../config/db.php';
session_start();

// Thibitisha ni Admin wa mfumo
$sessionRole = $_SESSION['user_role'] ?? $_SESSION['role'] ?? null;
if (!isset($_SESSION['user_id']) || !in_array($sessionRole, ['admin', 'super_admin'], true)) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo json_encode(['error' => 'Invalid input']);
    exit;
}

$email = filter_var($input['email'] ?? '', FILTER_SANITIZE_EMAIL);
$password = $input['password'] ?? '';
$role = $input['role'] ?? '';
$first_name = trim($input['first_name'] ?? '');
$last_name = trim($input['last_name'] ?? '');
$phone = trim($input['phone'] ?? '');
$facility_id = isset($input['facility_id']) && !empty($input['facility_id']) ? (int)$input['facility_id'] : 'NULL';

// Validate
if (empty($email) || empty($password) || empty($role) || empty($first_name) || empty($last_name)) {
    echo json_encode(['error' => 'All fields are required']);
    exit;
}
if ($role === 'super_admin') {
    $role = 'admin';
}
if (!in_array($role, ['admin', 'co', 'receptionist'], true)) {
    echo json_encode(['error' => 'Invalid role']);
    exit;
}

// Check if email exists
$check = mysqli_query($conn, "SELECT id FROM users WHERE email = '$email'");
if (mysqli_num_rows($check) > 0) {
    echo json_encode(['error' => 'Email already exists']);
    exit;
}

// Hash password
$hashed = password_hash($password, PASSWORD_DEFAULT);

// Insert user
$facility_sql = ($facility_id === 'NULL') ? 'NULL' : "'$facility_id'";
$query = "INSERT INTO users (email, password, role, first_name, last_name, facility_id, phone, is_active) 
          VALUES ('$email', '$hashed', '$role', '$first_name', '$last_name', $facility_sql, '$phone', 1)";
if (!mysqli_query($conn, $query)) {
    echo json_encode(['error' => 'Database error: ' . mysqli_error($conn)]);
    exit;
}

echo json_encode(['success' => true, 'message' => 'User created successfully']);
?>
