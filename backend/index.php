<?php
// ===========================================
// RMS API Entry Point
// Handles all API requests and routes them to appropriate modules
// ===========================================

header('Content-Type: application/json');

$allowedOrigins = [
    'http://localhost:3000',
    'http://localhost:5173',
    'http://localhost:80',
    'http://127.0.0.1:5173',
    'http://127.0.0.1:3000',
];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowedOrigins, true)) {
    header('Access-Control-Allow-Origin: ' . $origin);
}
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

// Handle preflight OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Include database configuration
require_once __DIR__ . '/config/db.php';

// Get the request method and path
$request_method = $_SERVER['REQUEST_METHOD'];
$request_uri = $_SERVER['REQUEST_URI'];

// Remove query parameters from URI
$path = parse_url($request_uri, PHP_URL_PATH);

// Remove any local install path before /backend so the API works from
// /rms/backend, /temp/RMS/backend, or another XAMPP subfolder.
$backendPosition = stripos($path, '/backend');
if ($backendPosition !== false) {
    $path = substr($path, $backendPosition + strlen('/backend'));
}
$path = preg_replace('#^/index\.php#i', '', $path);

// Split path into segments
$path_segments = explode('/', trim($path, '/'));

// Default root response
if (empty($path_segments[0])) {
    echo json_encode(['message' => 'RMS backend API is active', 'endpoints' => ['POST /auth/login', 'POST /auth/logout', 'GET /facilities/manage_facilities']]);
    exit();
}

// Remove optional .php extension on the action segment
if (isset($path_segments[1])) {
    $path_segments[1] = preg_replace('/\.php$/', '', $path_segments[1]);
}

// Route the request
try {
    switch ($path_segments[0]) {
        case 'auth':
            require_once __DIR__ . '/modules/auth/' . $path_segments[1] . '.php';
            break;

        case 'referrals':
            require_once __DIR__ . '/modules/referrals/' . $path_segments[1] . '.php';
            break;

        case 'feedback':
            require_once __DIR__ . '/modules/feedback/' . $path_segments[1] . '.php';
            break;

        case 'notifications':
            require_once __DIR__ . '/modules/notifications/' . $path_segments[1] . '.php';
            break;

        case 'dashboard':
            require_once __DIR__ . '/modules/dashboard/' . $path_segments[1] . '.php';
            break;

        case 'facilities':
            require_once __DIR__ . '/modules/facilities/' . $path_segments[1] . '.php';
            break;

        case 'patients':
            require_once __DIR__ . '/modules/patients/' . $path_segments[1] . '.php';
            break;

        case 'departments':
            require_once __DIR__ . '/modules/departments/' . $path_segments[1] . '.php';
            break;

        case 'doctors':
            require_once __DIR__ . '/modules/doctors/' . $path_segments[1] . '.php';
            break;

        case 'communications':
            require_once __DIR__ . '/modules/communications/' . $path_segments[1] . '.php';
            break;

        default:
            http_response_code(404);
            echo json_encode(['error' => 'API endpoint not found']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error', 'message' => $e->getMessage()]);
}
?>
