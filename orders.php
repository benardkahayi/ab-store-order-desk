<?php
session_start();
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['role'] !== 'admin') {
    header("Location: admin_login.php"); exit;
}

if (isset($_GET['delete'])) {
    $stmt = $conn->prepare("DELETE FROM orders WHERE order_id = ?");
    $stmt->execute([$_GET['delete']]);
    header("Location: orders.php?deleted=1"); exit;
}

$orders = $conn->query("
    SELECT o.*, c.name AS customer_name
    FROM orders o
    LEFT JOIN customers c ON o.customer_id = c.id
    ORDER BY o.date_created DESC
")->fetchAll();

$total_orders    = count($orders);
$total_revenue   = $conn->query("SELECT COALESCE(SUM(total_price),0) FROM orders")->fetchColumn();
$pending_orders  = $conn->query("SELECT COUNT(*) FROM orders WHERE status='Processing'")->fetchColumn();
$shipped_orders  = $conn->query("SELECT COUNT(*) FROM orders WHERE status IN ('Shipped','In Transit','Arrived TZ')")->fetchColumn();
$delivered_orders= $conn->query("SELECT COUNT(*) FROM orders WHERE status='Delivered'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="sw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders - AB FASHIONSTORE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>
<?php $active_page = 'orders'; include 'admin_layout.php'; ?>

<div class="main-content">

    <!-- Page Header -->
    <div class="page-header">
        <div>
            <h1><i class="fas fa-shopping-cart me-2 text-primary"></i>Orders</h1>
            <p>Manage and track all customer orders</p>
        </div>
        <a href="add_order.php" class="btn btn-primary">
            <i class="fas fa-plus me-1"></i> Create New Order
        </a>
    </div>

    <!-- Stats -->
    <div class="row g-3 mb-4">
        <div class="col-xl col-md-6">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted mb-1" style="font-size:12px;">Total Orders</p>
                        <h2 class="fw-bold mb-0"><?php echo number_format($total_orders); ?></h2>
                    </div>
                    <div class="stat-icon bg-primary bg-opacity-10 text-primary"><i class="fas fa-shopping-cart"></i></div>
                </div>
            </div>
        </div>
        <div class="col-xl col-md-6">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted mb-1" style="font-size:12px;">Total Revenue</p>
                        <h2 class="fw-bold mb-0" style="font-size:18px;">TZS <?php echo number_format($total_revenue, 0); ?></h2>
                    </div>
                    <div class="stat-icon bg-success bg-opacity-10 text-success"><i class="fas fa-money-bill-wave"></i></div>
                </div>
            </div>
        </div>
        <div class="col-xl col-md-6">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted mb-1" style="font-size:12px;">Processing</p>
                        <h2 class="fw-bold mb-0 text-warning"><?php echo number_format($pending_orders); ?></h2>
                    </div>
                    <div class="stat-icon bg-warning bg-opacity-10 text-warning"><i class="fas fa-clock"></i></div>
                </div>
            </div>
        </div>
        <div class="col-xl col-md-6">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted mb-1" style="font-size:12px;">In Transit</p>
                        <h2 class="fw-bold mb-0 text-info"><?php echo number_format($shipped_orders); ?></h2>
                    </div>
                    <div class="stat-icon bg-info bg-opacity-10 text-info"><i class="fas fa-truck"></i></div>
                </div>
            </div>
        </div>
        <div class="col-xl col-md-6">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted mb-1" style="font-size:12px;">Delivered</p>
                        <h2 class="fw-bold mb-0 text-success"><?php echo number_format($delivered_orders); ?></h2>
                    </div>
                    <div class="stat-icon bg-success bg-opacity-10 text-success"><i class="fas fa-check-circle"></i></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Alerts -->
    <?php if (isset($_GET['deleted'])): ?>
        <div class="alert alert-danger alert-dismissible fade show mb-3">
            <i class="fas fa-trash me-2"></i> Order deleted.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if (isset($_GET['updated'])): ?>
        <div class="alert alert-success alert-dismissible fade show mb-3">
            <i class="fas fa-check-circle me-2"></i> Order status updated.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Orders Table -->
    <div class="table-card">
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <h5 class="fw-bold mb-0">All Orders</h5>
            <div class="d-flex gap-2 flex-wrap">
                <div style="position:relative;">
                    <i class="fas fa-search" style="position:absolute;left:11px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:13px;"></i>
                    <input type="text" id="searchInput" class="tbl-search" placeholder="Search orders...">
                </div>
                <select id="statusFilter" class="form-select form-select-sm" style="width:140px;font-size:13px;">
                    <option value="">All Status</option>
                    <option>Processing</option>
                    <option>Shipped</option>
                    <option>In Transit</option>
                    <option>Arrived TZ</option>
                    <option>Delivered</option>
                    <option>Cancelled</option>
                </select>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover" id="ordersTable">
                <thead><tr>
                    <th>Order ID</th>
                    <th>Customer</th>
                    <th>Product</th>
                    <th>Qty</th>
                    <th>Total Price</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr></thead>
                <tbody>
                <?php if ($orders): foreach ($orders as $o):
                    $s = $o['status'];
                    $bc = match($s) {
                        'Processing'             => 'sb-processing',
                        'Shipped'                => 'sb-shipped',
                        'In Transit'             => 'sb-intransit',
                        'Arrived TZ'             => 'sb-arrivedtz',
                        'Delivered'              => 'sb-delivered',
                        'Cancelled'              => 'sb-cancelled',
                        default                  => 'sb-processing'
                    };
                ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($o['order_id']); ?></strong></td>
                    <td><?php echo htmlspecialchars($o['customer_name'] ?? 'N/A'); ?></td>
                    <td style="max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?php echo htmlspecialchars($o['product_name']); ?></td>
                    <td><?php echo $o['quantity']; ?></td>
                    <td><strong>TZS <?php echo number_format($o['total_price'], 0); ?></strong></td>
                    <td><span class="sbadge <?php echo $bc; ?>"><?php echo $s; ?></span></td>
                    <td style="color:#94a3b8;font-size:12px;"><?php echo date('d M Y', strtotime($o['date_created'])); ?></td>
                    <td>
                        <div class="d-flex gap-1 flex-wrap">
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" style="font-size:12px;">
                                    <i class="fas fa-edit"></i> Status
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="update_order_status.php?order_id=<?php echo urlencode($o['order_id']); ?>&status=Processing">Processing</a></li>
                                    <li><a class="dropdown-item" href="update_order_status.php?order_id=<?php echo urlencode($o['order_id']); ?>&status=Shipped">Shipped</a></li>
                                    <li><a class="dropdown-item" href="update_order_status.php?order_id=<?php echo urlencode($o['order_id']); ?>&status=In Transit">In Transit</a></li>
                                    <li><a class="dropdown-item" href="update_order_status.php?order_id=<?php echo urlencode($o['order_id']); ?>&status=Arrived TZ">Arrived TZ</a></li>
                                    <li><a class="dropdown-item" href="update_order_status.php?order_id=<?php echo urlencode($o['order_id']); ?>&status=Delivered">Delivered</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item text-danger" href="update_order_status.php?order_id=<?php echo urlencode($o['order_id']); ?>&status=Cancelled">Cancelled</a></li>
                                </ul>
                            </div>
                            <a href="view_order.php?id=<?php echo urlencode($o['order_id']); ?>" class="btn btn-sm btn-outline-info" style="font-size:12px;" title="View"><i class="fas fa-eye"></i></a>
                            <a href="?delete=<?php echo urlencode($o['order_id']); ?>" class="btn btn-sm btn-outline-danger" style="font-size:12px;" title="Delete" onclick="return confirm('Delete this order permanently?')"><i class="fas fa-trash"></i></a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; else: ?>
                <tr><td colspan="8" class="text-center py-5">
                    <i class="fas fa-shopping-cart fa-4x text-muted mb-3 d-block"></i>
                    <h5 class="text-muted">No Orders Found</h5>
                    <a href="add_order.php" class="btn btn-primary mt-2">Create First Order</a>
                </td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const tbl = document.getElementById('ordersTable');
document.getElementById('searchInput').addEventListener('keyup', function() {
    const v = this.value.toLowerCase();
    tbl.querySelectorAll('tbody tr').forEach(r => r.style.display = r.textContent.toLowerCase().includes(v) ? '' : 'none');
});
document.getElementById('statusFilter').addEventListener('change', function() {
    const v = this.value;
    tbl.querySelectorAll('tbody tr').forEach(r => {
        const badge = r.querySelector('.sbadge');
        r.style.display = (!v || (badge && badge.textContent.trim() === v)) ? '' : 'none';
    });
});
</script>
</body>
</html>
