<?php
session_start();
require 'db.php';
if(!isset($_SESSION['admin'])) exit('Access denied');

$page = $_GET['page'] ?? '';
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// === HOME ===
if($page=='home'){
    $total = $conn->query("SELECT COUNT(*) FROM orders")->fetchColumn();
    $pending = $conn->query("SELECT COUNT(*) FROM orders WHERE status='Processing'")->fetchColumn();
    $shipped = $conn->query("SELECT COUNT(*) FROM orders WHERE status='Shipped'")->fetchColumn();
    $delivered = $conn->query("SELECT COUNT(*) FROM orders WHERE status='Delivered'")->fetchColumn();
    echo "<h1>Welcome Admin - {$_SESSION['admin']}</h1>
    <div class='cards'>
        <div class='card'>Total Orders<br><strong>$total</strong></div>
        <div class='card'>Pending<br><strong>$pending</strong></div>
        <div class='card'>Shipped<br><strong>$shipped</strong></div>
        <div class='card'>Delivered<br><strong>$delivered</strong></div>
    </div>";
}

// === ORDERS ===
elseif($page=='orders' || $action){
    if($action=='add_order'){
        $stmt = $conn->prepare("INSERT INTO orders (customer_name, phone, order_id, product_name, status, date_created) VALUES (?,?,?,?,?,NOW())");
        $stmt->execute([$_POST['customer_name'],$_POST['phone'],$_POST['order_id'],$_POST['product_name'],$_POST['status']]);
        echo "Order added!";
        exit;
    }
    if($action=='delete_order'){
        $id = $_GET['id'];
        $conn->prepare("DELETE FROM orders WHERE id=?")->execute([$id]);
        echo "Order deleted!";
        exit;
    }
    $orders = $conn->query("SELECT * FROM orders ORDER BY date_created DESC")->fetchAll();
    echo "<h2>Orders</h2>
    <form id='addOrderForm'>
        <input type='text' name='customer_name' placeholder='Customer Name' required>
        <input type='text' name='phone' placeholder='Phone' required>
        <input type='text' name='order_id' placeholder='Order ID' required>
        <input type='text' name='product_name' placeholder='Product Name' required>
        <select name='status' required>
            <option value='Processing'>Processing</option>
            <option value='Shipped'>Shipped</option>
            <option value='Delivered'>Delivered</option>
        </select>
        <button type='submit'>Add Order</button>
    </form>
    <p id='msg' style='color:green;'></p>
    <table border='1'>
    <tr><th>ID</th><th>Name</th><th>Order ID</th><th>Status</th><th>Actions</th></tr>";
    foreach($orders as $o){
        echo "<tr>
        <td>{$o['id']}</td>
        <td>{$o['customer_name']}</td>
        <td>{$o['order_id']}</td>
        <td>{$o['status']}</td>
        <td>
            <a href='#' class='deleteOrder' data-id='{$o['id']}'><i class='bi bi-trash'></i></a>
        </td>
        </tr>";
    }
    echo "</table>";
}

// === REFUNDS ===
elseif($page=='refunds'){
    $refunds = $conn->query("SELECT * FROM refunds ORDER BY date_created DESC")->fetchAll();
    echo "<h2>Refunds</h2><table border='1'>
    <tr><th>ID</th><th>Order ID</th><th>Customer</th><th>Status</th><th>Action</th></tr>";
    foreach($refunds as $r){
        echo "<tr>
        <td>{$r['id']}</td>
        <td>{$r['order_id']}</td>
        <td>{$r['customer_name']}</td>
        <td>{$r['status']}</td>
        <td>
            <a href='#' class='refundAction' data-id='{$r['id']}' data-act='approve'><i class='bi bi-check-circle'></i></a>
            <a href='#' class='refundAction' data-id='{$r['id']}' data-act='reject'><i class='bi bi-x-circle'></i></a>
        </td>
        </tr>";
    }
    echo "</table>";
}

// Refund approve/reject
elseif($action=='refund'){
    $id = $_GET['id'];
    $do = $_GET['do'];
    $status = ($do=='approve') ? 'Approved' : 'Rejected';
    $conn->prepare("UPDATE refunds SET status=? WHERE id=?")->execute([$status,$id]);
    echo "Refund $status!";
    exit;
}

// === CUSTOMERS ===
elseif($page=='customers'){
    $customers = $conn->query("SELECT * FROM customers")->fetchAll();
    echo "<h2>Customers</h2><table border='1'>
    <tr><th>ID</th><th>Name</th><th>Phone</th><th>Email</th><th>Orders</th></tr>";
    foreach($customers as $c){
        echo "<tr>
        <td>{$c['id']}</td>
        <td>{$c['name']}</td>
        <td>{$c['phone']}</td>
        <td>{$c['email']}</td>
        <td><a href='#' onclick=\"alert('Show orders for {$c['name']}')\"><i class='bi bi-eye'></i> View</a></td>
        </tr>";
    }
    echo "</table>";
}
?>
