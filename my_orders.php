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
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<title>My Orders - AB FASHIONSTORE</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
/* Page-level tweaks that complement customer_layout.php */
.orders-search{
  position:relative;max-width:280px;width:100%;
}
.orders-search i{
  position:absolute;left:12px;top:50%;transform:translateY(-50%);
  color:#94a3b8;font-size:13px;pointer-events:none;
}
.orders-search input{
  width:100%;padding:10px 14px 10px 34px;
  border:1.5px solid #e2e8f0;border-radius:24px;
  font-size:16px;background:#f8fafc;outline:none;
  min-height:42px;transition:border-color .2s,background .2s;
}
.orders-search input:focus{border-color:#3b82f6;background:#fff}

/* Mobile order cards (hidden on md+) */
.order-mcard{
  background:#fff;border:1px solid #e2e8f0;border-radius:12px;
  padding:14px;margin-bottom:10px;
}
.order-mcard-head{
  display:flex;justify-content:space-between;align-items:flex-start;
  gap:10px;margin-bottom:10px;
}
.order-mcard-head .oid{
  font-size:13px;font-weight:700;color:#0f172a;
  line-height:1.2;word-break:break-all;
}
.order-mcard-head .oid small{
  display:block;font-weight:400;color:#94a3b8;
  font-size:11px;margin-top:2px;
}
.order-mcard-body{
  display:grid;grid-template-columns:1fr auto;
  gap:6px 12px;font-size:12.5px;margin-bottom:10px;
  padding-bottom:10px;border-bottom:1px dashed #e2e8f0;
}
.order-mcard-body .lbl{color:#94a3b8;font-size:11px;display:block}
.order-mcard-body .val{font-weight:600;color:#1e293b}
.order-mcard-actions{display:flex;gap:8px;flex-wrap:wrap}
.order-mcard-actions .btn{flex:1;min-height:38px;font-size:12px;font-weight:600}

/* Table needs a bit tighter styling on small screens */
@media (max-width:991px){
  .page-header{flex-wrap:wrap;gap:12px}
}

@media (max-width:640px){
  .stat-card{padding:12px 13px}
  .stat-card h2, .stat-card h3{font-size:18px !important}
  .stat-icon{width:34px;height:34px;font-size:14px}
  .orders-search{max-width:100%}
  .table-card{padding:14px 12px;border-radius:12px}
}
</style>
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
    <div class="col-6 col-md-3">
      <div class="stat-card">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <p class="text-muted mb-1" style="font-size:11px;">Total Orders</p>
            <h2 class="fw-bold mb-0" style="font-size:24px;color:#3b82f6;"><?php echo count($orders); ?></h2>
          </div>
          <div class="stat-icon" style="background:#dbeafe;color:#2563eb;"><i class="fas fa-shopping-bag"></i></div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="stat-card">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <p class="text-muted mb-1" style="font-size:11px;">Total Value</p>
            <h3 class="fw-bold mb-0 text-success" style="font-size:16px;">TZS <?php echo number_format($total_value, 0); ?></h3>
          </div>
          <div class="stat-icon" style="background:#dcfce7;color:#16a34a;"><i class="fas fa-dollar-sign"></i></div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="stat-card">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <p class="text-muted mb-1" style="font-size:11px;">Delivered</p>
            <h2 class="fw-bold mb-0 text-success" style="font-size:24px;"><?php echo $delivered; ?></h2>
          </div>
          <div class="stat-icon" style="background:#dcfce7;color:#16a34a;"><i class="fas fa-check-circle"></i></div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="stat-card">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <p class="text-muted mb-1" style="font-size:11px;">In Progress</p>
            <h2 class="fw-bold mb-0 text-warning" style="font-size:24px;"><?php echo $in_progress; ?></h2>
          </div>
          <div class="stat-icon" style="background:#fef3c7;color:#d97706;"><i class="fas fa-truck"></i></div>
        </div>
      </div>
    </div>
  </div>

  <!-- Table / card container -->
  <div class="table-card">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
      <h5 class="fw-bold mb-0">All Orders</h5>
      <div class="orders-search">
        <i class="fas fa-search"></i>
        <input type="text" id="searchInput" placeholder="Search orders...">
      </div>
    </div>

    <?php if ($orders): ?>

      <!-- DESKTOP TABLE (hidden below md) -->
      <div class="d-none d-md-block table-responsive">
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

      <!-- MOBILE CARDS (hidden on md+) -->
      <div class="d-md-none" id="ordersCards">
        <?php foreach ($orders as $o): ?>
        <div class="order-mcard">
          <div class="order-mcard-head">
            <div class="oid">
              <?php echo htmlspecialchars($o['order_id']); ?>
              <small><?php echo date('d M Y', strtotime($o['date_created'])); ?></small>
            </div>
            <span class="sbadge <?php echo $order_badge[$o['status']] ?? 'sb-processing'; ?>"><?php echo $o['status']; ?></span>
          </div>

          <div class="order-mcard-body">
            <div>
              <span class="lbl">Product</span>
              <span class="val"><?php echo htmlspecialchars($o['product_name']); ?></span>
            </div>
            <div style="text-align:right;">
              <span class="lbl">Qty</span>
              <span class="val"><?php echo $o['quantity']; ?></span>
            </div>
            <div>
              <span class="lbl">Total</span>
              <span class="val">TZS <?php echo number_format($o['total_price'], 0); ?></span>
            </div>
            <div style="text-align:right;">
              <span class="lbl">Payment</span>
              <span class="sbadge <?php echo $pay_badge[$o['payment_status'] ?? 'pending'] ?? 'sb-pending'; ?>" style="font-size:10px;">
                <?php echo ucfirst($o['payment_status'] ?? 'Pending'); ?>
              </span>
            </div>
          </div>

          <div class="order-mcard-actions">
            <a href="track_order.php?id=<?php echo urlencode($o['order_id']); ?>" class="btn btn-outline-primary">
              <i class="fas fa-truck me-1"></i> Track
            </a>
            <?php if ($o['status'] === 'Delivered'): ?>
            <a href="request_refund.php?order_id=<?php echo urlencode($o['order_id']); ?>" class="btn btn-outline-danger">
              <i class="fas fa-undo me-1"></i> Refund
            </a>
            <?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>
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
/* Search — filters both desktop table rows and mobile cards */
document.getElementById('searchInput').addEventListener('keyup', function() {
  const v = this.value.toLowerCase();
  document.querySelectorAll('#ordersTable tbody tr').forEach(r => {
    r.style.display = r.textContent.toLowerCase().includes(v) ? '' : 'none';
  });
  document.querySelectorAll('#ordersCards .order-mcard').forEach(c => {
    c.style.display = c.textContent.toLowerCase().includes(v) ? '' : 'none';
  });
});
</script>
</body>
</html>