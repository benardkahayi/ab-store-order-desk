<?php
// Copy this file to db_config.php and fill in real credentials.
// db_config.php is gitignored and must never be committed.
$is_local = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1']);

if ($is_local) {
    $host     = "localhost";
    $dbname   = "order_desk";
    $username = "root";
    $password = "";
} else {
    $host     = "your-live-db-host";
    $dbname   = "your-live-db-name";
    $username = "your-live-db-username";
    $password = "your-live-db-password";
}
