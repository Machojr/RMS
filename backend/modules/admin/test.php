<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../config/db.php';

echo "<h2>Database Connection Test</h2>";

if ($conn) {
    echo "<p style='color:green'>✅ Database connected successfully!</p>";
    
    $result = mysqli_query($conn, "SELECT COUNT(*) as total FROM users");
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        echo "<p>Total users: " . $row['total'] . "</p>";
    } else {
        echo "<p style='color:red'>❌ Query failed: " . mysqli_error($conn) . "</p>";
    }
} else {
    echo "<p style='color:red'>❌ Database connection failed!</p>";
}
?>