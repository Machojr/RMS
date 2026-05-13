<?php
$conn = mysqli_connect('127.0.0.1', 'root', '', 'health_db');
if (!$conn) {
    echo 'CONN_FAIL: ' . mysqli_connect_error();
    exit(1);
}
$res = mysqli_query($conn, 'DESCRIBE users');
if (!$res) {
    echo 'QUERY_FAIL: ' . mysqli_error($conn);
    exit(1);
}
while ($row = mysqli_fetch_assoc($res)) {
    echo $row['Field'] . '|' . $row['Type'] . "\n";
}
mysqli_close($conn);
