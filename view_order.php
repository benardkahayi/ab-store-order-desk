<?php
session_start();
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['role'] !== 'admin') {
    header("Location: admin_login.php");
    exit;
}

$order_id = $_GET['id'] ?? '';
if (!$order_id) {
    header("Location: orders.php");
    exit;
}

$stmt = $conn->prepare("
    SELECT o.*, c.email, c.address,
           p.payment_method, p.status as payment_status,
           p.amount as payment_amount, p.transaction_id,
           s.status as shipment_status, s.tracking_number, s.courier, s.estimated_days
    FROM orders o
    LEFT JOIN customers c ON o.customer_id = c.id
    LEFT JOIN payments p ON o.order_id = p.order_id
    LEFT JOIN shipments s ON o.order_id = s.order_id
    WHERE o.order_id = ?
");
$stmt->execute([$order_id]);
$order = $stmt->fetch();

if (!$order) {
    header("Location: orders.php");
    exit;
}

$status_colors = [
    'Processing' => 'warning',
    'Shipped'    => 'info',
    'Delivered'  => 'success',
    'Cancelled'  => 'danger',
];
$color = $status_colors[$order['status']] ?? 'secondary';
?>
<!DOCTYPE html>
<html lang="sw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details - <?php echo htmlspecialchars($order_id); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f0f2f5; overflow-x: hidden; }

        .sidebar {
            position: fixed; left: 0; top: 0; width: 280px; height: 100%;
            background: linear-gradient(180deg, #0f1729 0%, #1a1a2e 100%);
            z-index: 1000; box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }
        .sidebar-header { padding: 25px 20px; border-bottom: 1px solid rgba(255,255,255,0.1); margin-bottom: 20px; }
        .logo { display: flex; align-items: center; gap: 12px; }
        .logo-icon {
            width: 45px; height: 45px;
            background: white; border-radius: 12px; padding: 3px; display: flex; align-items: center; justify-content: center;
        }
        .logo-text h3 { color: white; font-size: 16px; font-weight: 700; margin: 0; }
        .logo-text p  { color: rgba(255,255,255,0.6); font-size: 10px; margin: 0; }
        .sidebar-nav { padding: 0 15px; }
        .sidebar-nav a {
            color: rgba(255,255,255,0.7); padding: 12px 15px; text-decoration: none;
            display: flex; align-items: center; gap: 12px;
            border-radius: 10px; margin-bottom: 5px; transition: all 0.3s;
        }
        .sidebar-nav a:hover, .sidebar-nav a.active { background: rgba(255,255,255,0.1); color: white; }
        .sidebar-nav a i { width: 20px; }
        .main-content { margin-left: 280px; padding: 20px 30px; }
        .page-header {
            background: white; border-radius: 20px; padding: 25px 30px;
            margin-bottom: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .info-card {
            background: white; border-radius: 15px; padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 20px;
        }
        .info-card h6 { color: #6c757d; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 15px; }
        .info-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #f0f0f0; }
        .info-row:last-child { border-bottom: none; }
        .info-label { color: #6c757d; font-size: 14px; }
        .info-value { font-weight: 600; font-size: 14px; }
        @media (max-width: 992px) {
            .sidebar { width: 80px; }
            .sidebar .logo-text, .sidebar .sidebar-nav a span { display: none; }
            .main-content { margin-left: 80px; }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <div class="logo">
                <div class="logo-icon"><img src="image/logo.png" alt="AB FASHIONSTORE" style="width:38px;height:38px;object-fit:cover;border-radius:8px;"></div>
                <div class="logo-text">
                    <h3>AB FASHIONSTORE</h3>
                    <p>Blessings Collection</p>
                </div>
            </div>
        </div>
        <div class="sidebar-nav">
            <a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i><span>Dashboard</span></a>
            <a href="orders.php" class="active"><i class="fas fa-shopping-cart"></i><span>Orders</span></a>
            <a href="customers.php"><i class="fas fa-users"></i><span>Customers</span></a>
            <a href="products.php"><i class="fas fa-box"></i><span>Products</span></a>
            <a href="payments.php"><i class="fas fa-credit-card"></i><span>Payments</span></a>
            <a href="shipments.php"><i class="fas fa-truck"></i><span>Shipments</span></a>
            <hr style="border-color: rgba(255,255,255,0.1); margin: 15px 0;">
            <a href="admin_logout.php" style="color: #ef4444;"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="page-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="display-6 fw-bold mb-0">
                        <i class="fas fa-file-invoice me-2 text-primary"></i>Order Details
                    </h1>
                    <p class="text-muted mb-0 mt-1">Order ID: <strong><?php echo htmlspecialchars($order_id); ?></strong></p>
                </div>
                <div class="d-flex gap-2">
                    <span class="badge bg-<?php echo $color; ?> fs-6 px-3 py-2"><?php echo $order['status']; ?></span>
                    <a href="orders.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Back to Orders
                    </a>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Order Info -->
            <div class="col-lg-6">
                <div class="info-card">
                    <h6><i class="fas fa-shopping-cart me-2"></i>Order Information</h6>
                    <div class="info-row">
                        <span class="info-label">Order ID</span>
                        <span class="info-value"><?php echo htmlspecialchars($order['order_id']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Product</span>
                        <span class="info-value"><?php echo htmlspecialchars($order['product_name']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Quantity</span>
                        <span class="info-value"><?php echo $order['quantity']; ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Unit Price</span>
                        <span class="info-value">TZS <?php echo number_format($order['unit_price'], 0); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Total Price</span>
                        <span class="info-value text-success">TZS <?php echo number_format($order['total_price'], 0); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Status</span>
                        <span class="info-value">
                            <span class="badge bg-<?php echo $color; ?>"><?php echo $order['status']; ?></span>
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Date Created</span>
                        <span class="info-value"><?php echo date('d M Y, H:i', strtotime($order['date_created'])); ?></span>
                    </div>
                    <?php if ($order['description']): ?>
                    <div class="info-row">
                        <span class="info-label">Description</span>
                        <span class="info-value"><?php echo htmlspecialchars($order['description']); ?></span>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Update Status -->
                <div class="info-card">
                    <h6><i class="fas fa-edit me-2"></i>Update Order Status</h6>
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="update_order_status.php?order_id=<?php echo $order['order_id']; ?>&status=Processing" class="btn btn-warning btn-sm">
                            <i class="fas fa-spinner me-1"></i> Processing
                        </a>
                        <a href="update_order_status.php?order_id=<?php echo $order['order_id']; ?>&status=Shipped" class="btn btn-info btn-sm text-white">
                            <i class="fas fa-truck me-1"></i> Shipped
                        </a>
                        <a href="update_order_status.php?order_id=<?php echo $order['order_id']; ?>&status=Delivered" class="btn btn-success btn-sm">
                            <i class="fas fa-check-circle me-1"></i> Delivered
                        </a>
                        <a href="update_order_status.php?order_id=<?php echo $order['order_id']; ?>&status=Cancelled"
                           class="btn btn-danger btn-sm"
                           onclick="return confirm('Cancel this order?')">
                            <i class="fas fa-times me-1"></i> Cancel
                        </a>
                    </div>
                </div>
            </div>

            <!-- Customer & Payment Info -->
            <div class="col-lg-6">
                <div class="info-card">
                    <h6><i class="fas fa-user me-2"></i>Customer Information</h6>
                    <div class="info-row">
                        <span class="info-label">Name</span>
                        <span class="info-value"><?php echo htmlspecialchars($order['customer_name']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Phone</span>
                        <span class="info-value"><?php echo htmlspecialchars($order['phone']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Email</span>
                        <span class="info-value"><?php echo htmlspecialchars($order['email'] ?? 'N/A'); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Address</span>
                        <span class="info-value"><?php echo htmlspecialchars($order['address'] ?? 'N/A'); ?></span>
                    </div>
                </div>

                <div class="info-card">
                    <h6><i class="fas fa-credit-card me-2"></i>Payment Information</h6>
                    <?php if ($order['payment_amount']): ?>
                    <div class="info-row">
                        <span class="info-label">Amount Paid</span>
                        <span class="info-value text-success">TZS <?php echo number_format($order['payment_amount'], 0); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Method</span>
                        <span class="info-value"><?php echo htmlspecialchars($order['payment_method'] ?? 'N/A'); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Status</span>
                        <span class="info-value">
                            <span class="badge bg-<?php echo $order['payment_status'] == 'completed' ? 'success' : 'warning'; ?>">
                                <?php echo ucfirst($order['payment_status'] ?? 'pending'); ?>
                            </span>
                        </span>
                    </div>
                    <?php if ($order['transaction_id']): ?>
                    <div class="info-row">
                        <span class="info-label">Transaction ID</span>
                        <span class="info-value"><?php echo htmlspecialchars($order['transaction_id']); ?></span>
                    </div>
                    <?php endif; ?>
                    <?php else: ?>
                    <p class="text-muted mb-0">No payment recorded yet.</p>
                    <?php endif; ?>
                </div>

                <?php if ($order['tracking_number']): ?>
                <div class="info-card">
                    <h6><i class="fas fa-truck me-2"></i>Shipment Information</h6>
                    <div class="info-row">
                        <span class="info-label">Tracking Number</span>
                        <span class="info-value"><?php echo htmlspecialchars($order['tracking_number']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Courier</span>
                        <span class="info-value"><?php echo htmlspecialchars($order['courier'] ?? 'N/A'); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Est. Days</span>
                        <span class="info-value"><?php echo htmlspecialchars($order['estimated_days'] ?? 'N/A'); ?> days</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Shipment Status</span>
                        <span class="info-value"><?php echo htmlspecialchars($order['shipment_status'] ?? 'N/A'); ?></span>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
