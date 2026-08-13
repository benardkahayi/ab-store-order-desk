<?php
session_start();
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['role'] !== 'admin') {
    header("Location: admin_login.php");
    exit;
}

$order_id = $_GET['order_id'] ?? '';
$status   = $_GET['status'] ?? '';
$allowed  = ['Processing', 'Shipped', 'Delivered', 'Cancelled'];

if ($order_id && in_array($status, $allowed)) {
    $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
    $stmt->execute([$status, $order_id]);
}

header("Location: orders.php?updated=1");
exit;
