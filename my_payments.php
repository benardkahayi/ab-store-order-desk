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

$payments_stmt = $conn->prepare("
    SELECT p.*, o.product_name, o.status as order_status
    FROM payments p
    JOIN orders o ON p.order_id = o.order_id
    WHERE o.phone = ?
    ORDER BY p.date_created DESC
");
$payments_stmt->execute([$customer['phone']]);
$payments = $payments_stmt->fetchAll();

$total_paid = array_sum(array_column(
    array_filter($payments, fn($p) => in_array($p['status'], ['completed','Approved'])),
    'amount'
));
$pending_count = count(array_filter($payments, fn($p) => !in_array($p['status'], ['completed','Approved','failed','refunded'])));

$pay_badge = ['completed'=>'sb-completed','Approved'=>'sb-completed','pending'=>'sb-pending','failed'=>'sb-failed','refunded'=>'sb-refunded'];
$ord_badge = ['Processing'=>'sb-processing','Shipped'=>'sb-shipped','In Transit'=>'sb-intransit','Arrived TZ'=>'sb-arrivedtz','Delivered'=>'sb-delivered','Cancelled'=>'sb-cancelled'];
?>
<!DOCTYPE html>
<html lang="sw">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<title>My Payments - AB FASHIONSTORE</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
.payment-mcard{
  background:#fff;border:1px solid #e2e8f0;border-radius:12px;
  padding:14px;margin-bottom:10px;
}
.payment-mcard-head{
  display:flex;justify-content:space-between;align-items:flex-start;
  gap:10px;margin-bottom:10px;
}
.payment-mcard-head .amt{
  font-size:16px;font-weight:800;color:#15803d;line-height:1.1;
}
.payment-mcard-head .amt small{
  display:block;font-weight:500;color:#94a3b8;
  font-size:10.5px;text-transform:uppercase;letter-spacing:.06em;margin-top:2px;
}
.payment-mcard-body{
  display:grid;grid-template-columns:1fr 1fr;gap:8px 12px;
  font-size:12.5px;padding:10px 0;
  border-top:1px dashed #e2e8f0;border-bottom:1px dashed #e2e8f0;
  margin-bottom:10px;
}
.payment-mcard-body .lbl{
  display:block;color:#94a3b8;font-size:10.5px;
  text-transform:uppercase;letter-spacing:.05em;margin-bottom:2px;
}
.payment-mcard-body .val{font-weight:600;color:#1e293b;word-break:break-word}
.payment-mcard-foot{
  display:flex;justify-content:space-between;align-items:center;
  gap:8px;flex-wrap:wrap;
}
.payment-mcard-foot .date{font-size:11px;color:#94a3b8}

@media (max-width:640px){
  .stat-card{padding:12px 13px}
  .stat-card h2, .stat-card h3{font-size:18px !important}
  .stat-icon{width:34px;height:34px;font-size:14px}
  .table-card{padding:14px 12px;border-radius:12px}
  .page-header{flex-wrap:wrap;gap:12px}
}
</style>
</head>
<body>
<?php $active_page = 'payments'; include 'customer_layout.php'; ?>

<div class="main-content">

  <div class="page-header">
    <div>
      <h1><i class="fas fa-credit-card me-2 text-primary"></i>My Payments</h1>
      <p>Payment history for <strong><?php echo htmlspecialchars($customer['name']); ?></strong></p>
    </div>
    <a href="customer_dashboard.php" class="btn btn-outline-secondary btn-sm">
      <i class="fas fa-arrow-left me-1"></i> Dashboard
    </a>
  </div>

  <!-- Stats -->
  <div class="row g-3 mb-4">
    <div class="col-6 col-md-4">
      <div class="stat-card">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <p class="text-muted mb-1" style="font-size:11px;">Total Transactions</p>
            <h2 class="fw-bold mb-0" style="font-size:24px;color:#3b82f6;"><?php echo count($payments); ?></h2>
          </div>
          <div class="stat-icon" style="background:#dbeafe;color:#2563eb;"><i class="fas fa-list"></i></div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-4">
      <div class="stat-card">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <p class="text-muted mb-1" style="font-size:11px;">Total Paid</p>
            <h3 class="fw-bold mb-0 text-success" style="font-size:15px;">TZS <?php echo number_format($total_paid, 0); ?></h3>
          </div>
          <div class="stat-icon" style="background:#dcfce7;color:#16a34a;"><i class="fas fa-check-circle"></i></div>
        </div>
      </div>
    </div>
    <div class="col-12 col-md-4">
      <div class="stat-card">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <p class="text-muted mb-1" style="font-size:11px;">Pending</p>
            <h2 class="fw-bold mb-0 text-warning" style="font-size:24px;"><?php echo $pending_count; ?></h2>
          </div>
          <div class="stat-icon" style="background:#fef3c7;color:#d97706;"><i class="fas fa-clock"></i></div>
        </div>
      </div>
    </div>
  </div>

  <!-- Content -->
  <div class="table-card">
    <h5 class="fw-bold mb-3">Payment History</h5>

    <?php if ($payments): ?>

      <!-- DESKTOP TABLE -->
      <div class="d-none d-md-block table-responsive">
        <table class="table table-hover align-middle">
          <thead><tr>
            <th>#</th><th>Order ID</th><th>Product</th><th>Amount (TZS)</th>
            <th>Method</th><th>Payment Status</th><th>Order Status</th><th>Date</th>
          </tr></thead>
          <tbody>
          <?php foreach ($payments as $i => $p): ?>
          <tr>
            <td style="color:#94a3b8;"><?php echo $i + 1; ?></td>
            <td><strong><?php echo htmlspecialchars($p['order_id']); ?></strong></td>
            <td style="font-size:12.5px;max-width:130px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?php echo htmlspecialchars($p['product_name']); ?></td>
            <td><strong class="text-success">TZS <?php echo number_format($p['amount'], 0); ?></strong></td>
            <td style="font-size:12.5px;"><?php echo htmlspecialchars($p['payment_method'] ?? 'N/A'); ?></td>
            <td><span class="sbadge <?php echo $pay_badge[$p['status']] ?? 'sb-pending'; ?>"><?php echo ucfirst($p['status'] ?? 'Pending'); ?></span></td>
            <td><span class="sbadge <?php echo $ord_badge[$p['order_status']] ?? 'sb-processing'; ?>"><?php echo $p['order_status']; ?></span></td>
            <td style="color:#94a3b8;font-size:12px;"><?php echo date('d M Y', strtotime($p['date_created'])); ?></td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <!-- MOBILE CARDS -->
      <div class="d-md-none">
        <?php foreach ($payments as $i => $p): ?>
        <div class="payment-mcard">
          <div class="payment-mcard-head">
            <div class="amt">
              TZS <?php echo number_format($p['amount'], 0); ?>
              <small>Payment</small>
            </div>
            <span class="sbadge <?php echo $pay_badge[$p['status']] ?? 'sb-pending'; ?>">
              <?php echo ucfirst($p['status'] ?? 'Pending'); ?>
            </span>
          </div>

          <div class="payment-mcard-body">
            <div>
              <span class="lbl">Order ID</span>
              <span class="val"><?php echo htmlspecialchars($p['order_id']); ?></span>
            </div>
            <div>
              <span class="lbl">Method</span>
              <span class="val"><?php echo htmlspecialchars($p['payment_method'] ?? 'N/A'); ?></span>
            </div>
            <div style="grid-column:span 2;">
              <span class="lbl">Product</span>
              <span class="val"><?php echo htmlspecialchars($p['product_name']); ?></span>
            </div>
          </div>

          <div class="payment-mcard-foot">
            <span class="date"><?php echo date('d M Y', strtotime($p['date_created'])); ?></span>
            <span class="sbadge <?php echo $ord_badge[$p['order_status']] ?? 'sb-processing'; ?>">
              <?php echo $p['order_status']; ?>
            </span>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <div class="mt-2 text-end text-muted small">
        <?php echo count($payments); ?> transactions &nbsp;|&nbsp; Total paid: <strong>TZS <?php echo number_format($total_paid, 0); ?></strong>
      </div>

    <?php else: ?>
      <div class="text-center py-5">
        <i class="fas fa-credit-card fa-4x text-muted mb-3 d-block"></i>
        <h5 class="text-muted">No Payments Yet</h5>
        <p class="text-muted">No payment records found on your account.</p>
      </div>
    <?php endif; ?>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>