<?php
session_start();
require 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'customer') {
    header("Location: customer_login.php"); exit;
}

$customer_id = $_SESSION['customer_id'];
$stmt = $conn->prepare("SELECT * FROM customers WHERE id = ?");
$stmt->execute([$customer_id]);
$customer = $stmt->fetch();

$orders_stmt = $conn->prepare("
    SELECT o.*, p.status as payment_status, p.payment_method
    FROM orders o
    LEFT JOIN payments p ON o.order_id = p.order_id
    WHERE o.phone = ?
    ORDER BY o.date_created DESC
");
$orders_stmt->execute([$customer['phone']]);
$orders = $orders_stmt->fetchAll();

$total_value  = array_sum(array_column($orders, 'total_price'));
$delivered    = count(array_filter($orders, fn($o) => $o['status'] === 'Delivered'));
$in_progress  = count(array_filter($orders, fn($o) => in_array($o['status'], ['Processing','Shipped','In Transit','Arrived TZ'])));

$order_badge = [
    'Processing'  => 'sb-processing',
    'Shipped'     => 'sb-shipped',
    'In Transit'  => 'sb-intransit',
    'Arrived TZ'  => 'sb-arrivedtz',
    'Delivered'   => 'sb-delivered',
    'Cancelled'   => 'sb-cancelled',
];
$pay_badge = ['completed'=>'sb-completed','Approved'=>'sb-completed','pending'=>'sb-pending','failed'=>'sb-failed','refunded'=>'sb-refunded'];
?>
<!DOCTYPE html>
<html lang="sw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - AB FASHIONSTORE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>
<?php $active_page = 'orders'; include 'customer_layout.php'; ?>

<div class="main-content">

    <div class="page-header">
        <div>
            <h1><i class="fas fa-shopping-bag me-2 text-primary"></i>My Orders</h1>
            <p>Welcome, <strong><?php echo htmlspecialchars($customer['name']); ?></strong></p>
        </div>
        <a href="request_refund.php" class="btn btn-outline-danger btn-sm">
            <i class="fas fa-undo me-1"></i> Request Refund
        </a>
    </div>

    <!-- Stats -->
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div><p class="text-muted mb-1" style="font-size:11px;">Total Orders</p><h2 class="fw-bold mb-0" style="font-size:24px;color:#3b82f6;"><?php echo count($orders); ?></h2></div>
                    <div class="stat-icon" style="background:#dbeafe;color:#2563eb;"><i class="fas fa-shopping-bag"></i></div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div><p class="text-muted mb-1" style="font-size:11px;">Total Value</p><h3 class="fw-bold mb-0 text-success" style="font-size:16px;">TZS <?php echo number_format($total_value, 0); ?></h3></div>
                    <div class="stat-icon" style="background:#dcfce7;color:#16a34a;"><i class="fas fa-dollar-sign"></i></div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div><p class="text-muted mb-1" style="font-size:11px;">Delivered</p><h2 class="fw-bold mb-0 text-success" style="font-size:24px;"><?php echo $delivered; ?></h2></div>
                    <div class="stat-icon" style="background:#dcfce7;color:#16a34a;"><i class="fas fa-check-circle"></i></div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div><p class="text-muted mb-1" style="font-size:11px;">In Progress</p><h2 class="fw-bold mb-0 text-warning" style="font-size:24px;"><?php echo $in_progress; ?></h2></div>
                    <div class="stat-icon" style="background:#fef3c7;color:#d97706;"><i class="fas fa-truck"></i></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="table-card">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <h5 class="fw-bold mb-0">All Orders</h5>
            <div style="position:relative;">
                <i class="fas fa-search" style="position:absolute;left:11px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:13px;"></i>
                <input type="text" id="searchInput" class="tbl-search" placeholder="Search orders...">
            </div>
        </div>
        <?php if ($orders): ?>
        <div class="table-responsive">
            <table class="table table-hover" id="ordersTable">
                <thead><tr>
                    <th>Order ID</th><th>Product</th><th>Qty</th><th>Unit Price</th>
                    <th>Total</th><th>Status</th><th>Payment</th><th>Date</th><th>Actions</th>
                </tr></thead>
                <tbody>
                <?php foreach ($orders as $o): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($o['order_id']); ?></strong></td>
                    <td style="max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?php echo htmlspecialchars($o['product_name']); ?></td>
                    <td><?php echo $o['quantity']; ?></td>
                    <td style="color:#64748b;">TZS <?php echo number_format($o['unit_price'], 0); ?></td>
                    <td><strong>TZS <?php echo number_format($o['total_price'], 0); ?></strong></td>
                    <td><span class="sbadge <?php echo $order_badge[$o['status']] ?? 'sb-processing'; ?>"><?php echo $o['status']; ?></span></td>
                    <td>
                        <div style="font-size:11.5px;color:#64748b;"><?php echo $o['payment_method'] ?? 'N/A'; ?></div>
                        <span class="sbadge <?php echo $pay_badge[$o['payment_status'] ?? 'pending'] ?? 'sb-pending'; ?>" style="font-size:10px;">
                            <?php echo ucfirst($o['payment_status'] ?? 'Pending'); ?>
                        </span>
                    </td>
                    <td style="color:#94a3b8;font-size:12px;"><?php echo date('d M Y', strtotime($o['date_created'])); ?></td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="track_order.php?id=<?php echo urlencode($o['order_id']); ?>" class="btn btn-sm btn-outline-primary" style="font-size:11px;padding:3px 8px;">
                                <i class="fas fa-truck"></i> Track
                            </a>
                            <?php if ($o['status'] === 'Delivered'): ?>
                            <a href="request_refund.php?order_id=<?php echo urlencode($o['order_id']); ?>" class="btn btn-sm btn-outline-danger" style="font-size:11px;padding:3px 8px;">
                                <i class="fas fa-undo"></i>
                            </a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="mt-2 text-end text-muted small">
            <?php echo count($orders); ?> orders &nbsp;|&nbsp; Total: <strong>TZS <?php echo number_format($total_value, 0); ?></strong>
        </div>
        <?php else: ?>
        <div class="text-center py-5">
            <i class="fas fa-shopping-bag fa-4x text-muted mb-3 d-block"></i>
            <h5 class="text-muted">No Orders Yet</h5>
            <p class="text-muted">You haven't placed any orders yet.</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('searchInput').addEventListener('keyup', function() {
    const v = this.value.toLowerCase();
    document.querySelectorAll('#ordersTable tbody tr').forEach(r => {
        r.style.display = r.textContent.toLowerCase().includes(v) ? '' : 'none';
    });
});
</script>
</body>
</html>
