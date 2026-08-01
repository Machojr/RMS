<?php
// ===========================================
// Session Management & Authentication Helper
// Handles user sessions and role-based access control
// ===========================================

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get current user data from session
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    

    return [
        'id' => $_SESSION['user_id'],
        'email' => $_SESSION['user_email'],
        'role' => $_SESSION['user_role'],
        'first_name' => $_SESSION['user_first_name'],
        'last_name' => $_SESSION['user_last_name'],
        'facility_id' => $_SESSION['user_facility_id']
    ];
}

/**
 * Check if user has specific role
 */
function hasRole($required_role) {
    $user = getCurrentUser();
    return $user && $user['role'] === $required_role;
}

/**
 * Check if user can access specific resource
 */
function canAccessReferral($referral_id, $conn) {
    $user = getCurrentUser();
    if (!$user) return false;

    // COs can access their own referrals or referrals for their receiving department if they are a receiving doctor
    if ($user['role'] === 'co') {
        $stmt = $conn->prepare(
            "SELECT r.id
             FROM referrals r
             LEFT JOIN doctors doc ON doc.user_id = ?
             LEFT JOIN departments dep ON doc.department_id = dep.id
             WHERE r.id = ?
               AND (
                   r.referring_co_id = ?
                   OR (
                       r.receiving_department_id = dep.id
                       AND r.receiving_facility_id = dep.facility_id
                   )
               )"
        );
        $stmt->bind_param("iii", $user['id'], $referral_id, $user['id']);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->num_rows > 0;
    }

    // Receptionists can access referrals for their facility (as receiving facility)
    if ($user['role'] === 'receptionist') {
        $stmt = $conn->prepare("SELECT id FROM referrals WHERE id = ? AND receiving_facility_id = ?");
        $stmt->bind_param("ii", $referral_id, $user['facility_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->num_rows > 0;
    }

    // Admin can access all referrals for oversight/reporting.
    return in_array($user['role'], ['admin', 'super_admin'], true);
}

/**
 * Send JSON response
 */
function sendResponse($data, $status_code = 200) {
    http_response_code($status_code);
    echo json_encode($data);
    exit();
}

/**
 * Send error response
 */
function sendError($message, $status_code = 400) {
    sendResponse(['error' => $message], $status_code);
}

/**
 * Validate required fields in request
 */
function validateRequired($data, $required_fields) {
    $missing = [];
    foreach ($required_fields as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            $missing[] = $field;
        }
    }

    if (!empty($missing)) {
        sendError('Missing required fields: ' . implode(', ', $missing));
    }
}
?>
