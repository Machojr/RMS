<?php
// SMTP configuration for RMS email notifications.
// For production, move these values to environment variables or a file outside web root.
return [
    'host' => 'smtp.gmail.com',
    'port' => 587,
    'username' => 'abdulkarimmacho@gmail.com',
    'password' => 'bidn bpam leas mqvm',
    'encryption' => 'tls',
    'from_email' => 'abdulkarimmacho@gmail.com',
    'from_name' => 'RMS Notifications',
    'timeout' => 20,
];
?>
