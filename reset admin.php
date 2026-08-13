<?php
require 'db.php';

$new_password = "12345";
$hash = password_hash($new_password, PASSWORD_DEFAULT);

$stmt = $conn->prepare("UPDATE admins SET password = ? WHERE username = 'admin'");
$stmt->execute([$hash]);

echo "Admin password reset successfully!";
?>
