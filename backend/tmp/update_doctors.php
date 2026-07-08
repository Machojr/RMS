<?php
require __DIR__ . '/../config/db.php';

// Update emails
mysqli_query($conn, "UPDATE users SET email='aymanrwenda@gmail.com' WHERE id=23");
mysqli_query($conn, "UPDATE users SET email='abdulkarimmacho@gmail.com' WHERE id=24");
mysqli_query($conn, "UPDATE users SET email='aymanmussojr@gmail.com' WHERE id=25");

// Verify the three doctors
$res = mysqli_query($conn, "
  SELECT 
    u.id,
    u.email,
    u.first_name,
    u.last_name,
    u.phone,
    d.contact_phone,
    dep.name AS department_name,
    f.name AS facility_name
  FROM users u
  JOIN doctors d ON u.id = d.user_id
  JOIN departments dep ON d.department_id = dep.id
  JOIN facilities f ON u.facility_id = f.id
  WHERE u.id IN (23, 24, 25)
  ORDER BY f.name, u.first_name
");

echo "=== THREE DOCTORS UPDATED ===\n";
while ($row = mysqli_fetch_assoc($res)) {
    echo "\n" . str_repeat("-", 60) . "\n";
    echo "Doctor: " . $row['first_name'] . " " . $row['last_name'] . "\n";
    echo "Email: " . $row['email'] . "\n";
    echo "Phone: " . $row['phone'] . "\n";
    echo "Contact Phone: " . $row['contact_phone'] . "\n";
    echo "Department: " . $row['department_name'] . "\n";
    echo "Hospital: " . $row['facility_name'] . "\n";
}
echo "\n" . str_repeat("-", 60) . "\n";
?>
