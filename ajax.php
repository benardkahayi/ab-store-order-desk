<?php
session_start();
$pdo = new PDO("mysql:host=localhost;dbname=order_desk", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$section = $_GET['section'] ?? '';

if($section === "dashboard"){
    echo "<h3>Welcome to Admin Dashboard</h3><p>Select a section from the left menu to view details.</p>";
}

elseif($section === "orders"){
    // your existing orders code
}

elseif($section === "customers"){
    // your existing customers code
}

elseif($section === "refunds"){
    // REFUNDS SECTION
    $stmt = $pdo->query("SELECT r.*, c.name AS customer_name, o.id AS order_id 
                         FROM refunds r 
                         JOIN customers c ON r.customer_id = c.id 
                         JOIN orders o ON r.order_id = o.id 
                         ORDER BY r.request_date DESC");

    echo "<h3>Refund Requests</h3>";
    echo "<table class='table table-bordered'>
            <tr>
                <th>ID</th>
                <th>Customer</th>
                <th>Order ID</th>
                <th>Reason</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Action</th>
                <th>Requested On</th>
            </tr>";

    while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
        echo "<tr>
                <td>{$row['id']}</td>
                <td>{$row['customer_name']}</td>
                <td>{$row['order_id']}</td>
                <td>{$row['reason']}</td>
                <td>{$row['amount']}</td>
                <td>{$row['status']}</td>
                <td>
                    <form method='POST' action='update_refund_status.php' style='display:flex; gap:5px;'>
                        <input type='hidden' name='id' value='{$row['id']}'>
                        <select name='status' class='form-select form-select-sm'>
                            <option value='Pending' ".($row['status']=='Pending'?'selected':'').">Pending</option>
                            <option value='Approved' ".($row['status']=='Approved'?'selected':'').">Approved</option>
                            <option value='Rejected' ".($row['status']=='Rejected'?'selected':'').">Rejected</option>
                        </select>
                        <button class='btn btn-sm btn-primary'>Update</button>
                    </form>
                </td>
                <td>{$row['request_date']}</td>
              </tr>";
    }

    echo "</table>";
}
?>
