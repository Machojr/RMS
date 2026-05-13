<?php
// ===========================================
// Database Configuration
// Connects to MySQL database for RMS
// ===========================================

$host     = "localhost";
$dbname   = "health_db";
$username = "root";
$password = "";

// Create database connection
$conn = mysqli_connect($host, $username, $password, $dbname);

// Check connection
if (!$conn) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Database connection failed',
        'message' => mysqli_connect_error()
    ]);
    exit();
}

// Set charset to utf8
mysqli_set_charset($conn, "utf8");
?>