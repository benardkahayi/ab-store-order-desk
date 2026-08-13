<?php
session_start();
require 'db.php';

if(!isset($_SESSION['admin'])) { header("Location: admin_login.php"); exit; }

$id = $_GET['id'];
$stmt = $conn->prepare("SELECT * FROM orders WHERE id=?");
$stmt->execute([$id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $stmt = $conn->prepare("UPDATE orders SET customer_name=?, phone=?, order_id=?, product_name=?, status=? WHERE id=?");
    $stmt->execute([$_POST['customer_name'], $_POST['phone'], $_POST['order_id'], $_POST['product_name'], $_POST['status'], $id]);
    header("Location: orders.php");
}
?>

<form method="POST">
<input type="text" name="customer_name" value="<?= $order['customer_name'] ?>" required>
<input type="text" name="phone" value="<?= $order['phone'] ?>" required>
<input type="text" name="order_id" value="<?= $order['order_id'] ?>" required>
<input type="text" name="product_name" value="<?= $order['product_name'] ?>" required>
<select name="status" required>
<option value="Processing" <?= $order['status']=='Processing'?'selected':'' ?>>Processing</option>
<option value="Shipped" <?= $order['status']=='Shipped'?'selected':'' ?>>Shipped</option>
<option value="Delivered" <?= $order['status']=='Delivered'?'selected':'' ?>>Delivered</option>
</select>
<button type="submit">Update Order</button>
</form>
