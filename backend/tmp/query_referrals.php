<?php
$conn = mysqli_connect('localhost', 'root', '', 'health_db');
if (!$conn) {
    echo 'conn failed';
    exit(1);
}
$res = mysqli_query($conn, 'SELECT id,status,referring_facility_id,receiving_facility_id,clinical_reason FROM referrals ORDER BY id');
while ($row = mysqli_fetch_assoc($res)) {
    echo json_encode($row) . PHP_EOL;
}
mysqli_close($conn);
?>