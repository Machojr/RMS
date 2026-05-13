<?php
// ===========================================
// Authentication API - Logout Endpoint
// POST /auth/logout.php
// ===========================================

require_once dirname(__DIR__, 2) . '/includes/session.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError('Method not allowed', 405);
}

// Destroy session
session_destroy();

// Return success response
sendResponse([
    'success' => true,
    'message' => 'Logout successful'
]);
?>