<?php
// ===========================================
// Authentication API - Login Endpoint
// POST /auth/login.php
// ===========================================

require_once __DIR__ . '/../../config/db.php';
require_once dirname(__DIR__, 2) . '/includes/session.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError('Method not allowed', 405);
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    sendError('Invalid JSON input');
}

// Validate required fields
validateRequired($input, ['email', 'password']);

// Sanitize input
$email = trim($input['email']);
$password = $input['password'];

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    sendError('Invalid email format');
}

// Check user credentials
$stmt = $conn->prepare("
    SELECT id, email, password, role, first_name, last_name, facility_id
    FROM users
    WHERE email = ? AND is_active = TRUE
");

$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    sendError('Invalid email or password', 401);
}

$user = $result->fetch_assoc();

// Verify password (plain text for now - will hash later)
if ($password !== $user['password']) {
    sendError('Invalid email or password', 401);
}

// Start session and store user data
$_SESSION['user_id'] = $user['id'];
$_SESSION['user_email'] = $user['email'];
$_SESSION['user_role'] = $user['role'];
$_SESSION['user_first_name'] = $user['first_name'];
$_SESSION['user_last_name'] = $user['last_name'];
$_SESSION['user_facility_id'] = $user['facility_id'];

// Return success response
sendResponse([
    'success' => true,
    'message' => 'Login successful',
    'user' => [
        'id' => $user['id'],
        'email' => $user['email'],
        'role' => $user['role'],
        'first_name' => $user['first_name'],
        'last_name' => $user['last_name'],
        'facility_id' => $user['facility_id']
    ]
]);
?>