<?php
session_start();
require 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'customer') {
    header("Location: customer_login.php"); exit;
}

$customer_id = $_SESSION['customer_id'];
$order_id    = $_GET['id'] ?? $_SESSION['order_id'] ?? '';

$stmt = $conn->prepare("SELECT * FROM customers WHERE id = ?");
$stmt->execute([$customer_id]);
$customer = $stmt->fetch();

$order    = null;
$steps    = [];
$progress = 0;

if ($order_id) {
    $stmt = $conn->prepare("
        SELECT o.*,
               p.status as payment_status, p.payment_method,
               s.status as shipment_status, s.tracking_number, s.courier, s.estimated_days
        FROM orders o
        LEFT JOIN payments p ON o.order_id = p.order_id
        LEFT JOIN shipments s ON o.order_id = s.order_id
        WHERE o.order_id = ? AND o.phone = ?
    ");
    $stmt->execute([$order_id, $customer['phone']]);
    $order = $stmt->fetch();
}

if ($order) {
    $paid      = in_array($order['payment_status'], ['completed','Approved']);
    $purchased = in_array($order['status'], ['Processing','Shipped','In Transit','Arrived TZ','Delivered']);
    $shipped   = in_array($order['status'], ['Shipped','In Transit','Arrived TZ','Delivered']);
    $arrived   = in_array($order['status'], ['Arrived TZ','Delivered']) || $order['shipment_status'] === 'arrived_tz';
    $done_del  = $order['status'] === 'Delivered';

    $steps = [
        ['icon' => 'fa-shopping-cart',  'label' => 'Ordered',   'done' => true,       'active' => false],
        ['icon' => 'fa-credit-card',    'label' => 'Payment',   'done' => $paid,      'active' => !$paid],
        ['icon' => 'fa-store',          'label' => 'Purchased', 'done' => $purchased, 'active' => !$purchased && $paid],
        ['icon' => 'fa-ship',           'label' => 'Shipped',   'done' => $shipped,   'active' => !$shipped && $purchased],
        ['icon' => 'fa-map-marker-alt', 'label' => 'Arrived TZ','done' => $arrived,   'active' => !$arrived && $shipped],
        ['icon' => 'fa-box-open',       'label' => 'Delivered', 'done' => $done_del,  'active' => !$done_del && $arrived],
    ];

    $done_count  = count(array_filter($steps, fn($s) => $s['done']));
    $total_steps = count($steps);
    $progress = $total_steps > 1 ? round(($done_count - 1) / ($total_steps - 1) * 100) : 0;
}

$ord_badge = ['Processing'=>'sb-processing','Shipped'=>'sb-shipped','In Transit'=>'sb-intransit','Arrived TZ'=>'sb-arrivedtz','Delivered'=>'sb-delivered','Cancelled'=>'sb-cancelled'];
?>
<!DOCTYPE html>
<html lang="sw">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<title>Track Order - AB FASHIONSTORE</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
.tracking-card{background:#fff;border-radius:14px;padding:24px;box-shadow:0 2px 8px rgba(0,0,0,.05);margin-bottom:20px}

/* ── Horizontal timeline (desktop) ── */
.timeline-wrap{position:relative;padding:30px 10px 10px}
.timeline-track{
  position:absolute;top:51px;left:calc(10px + 4.16%);right:calc(10px + 4.16%);
  height:3px;background:#e2e8f0;border-radius:4px;
}
.timeline-fill{height:100%;background:linear-gradient(90deg,#22c55e,#3b82f6);border-radius:4px;transition:width .6s}
.timeline-steps{display:flex;justify-content:space-between;position:relative;z-index:1}
.t-step{display:flex;flex-direction:column;align-items:center;gap:8px;flex:1}
.t-icon{
  width:44px;height:44px;border-radius:50%;
  display:flex;align-items:center;justify-content:center;
  font-size:17px;transition:all .3s;
  background:#e2e8f0;color:#94a3b8;border:3px solid #fff;
  box-shadow:0 0 0 2px #e2e8f0;
}
.t-icon.done{background:#22c55e;color:#fff;box-shadow:0 0 0 3px rgba(34,197,94,.25)}
.t-icon.active{
  background:#3b82f6;color:#fff;
  box-shadow:0 0 0 4px rgba(59,130,246,.25);
  animation:pulse-ring 1.5s infinite;
}
@keyframes pulse-ring{
  0%{box-shadow:0 0 0 3px rgba(59,130,246,.35)}
  70%{box-shadow:0 0 0 8px rgba(59,130,246,0)}
  100%{box-shadow:0 0 0 3px rgba(59,130,246,.35)}
}
.t-label{text-align:center}
.t-label .name{font-size:11px;font-weight:600;color:#334155;display:block;line-height:1.2}
.t-label .name.done{color:#15803d}
.t-label .name.active{color:#1d4ed8}

/* Info rows */
.info-row{
  display:flex;justify-content:space-between;align-items:flex-start;
  gap:14px;padding:10px 0;border-bottom:1px solid #f1f5f9;font-size:13.5px;
}
.info-row:last-child{border-bottom:none}
.info-row .ir-k{color:#64748b;flex-shrink:0}
.info-row .ir-v{font-weight:600;color:#1e293b;text-align:right;word-break:break-word;min-width:0}

/* Search form */
.search-form{
  display:flex;gap:10px;flex-wrap:wrap;
}
.search-form input{
  flex:1;min-width:200px;font-size:16px;padding:12px 16px;
  border:1.5px solid #e2e8f0;border-radius:10px;outline:none;
  min-height:48px;transition:border-color .2s;
}
.search-form input:focus{border-color:#3b82f6}
.search-form button{
  min-height:48px;padding:12px 24px;font-size:14px;font-weight:600;
}

/* ── Mobile vertical timeline ── */
.tl-mobile{display:none}

@media (max-width:640px){
  .tracking-card{padding:16px 14px;border-radius:12px}
  .tracking-card h5{font-size:14px}
  .page-header{flex-wrap:wrap;gap:12px}

  /* Hide horizontal timeline, show vertical */
  .timeline-wrap{display:none}
  .tl-mobile{
    display:block;position:relative;
    padding:14px 0 4px 0;
  }
  .tl-mobile::before{
    content:'';position:absolute;top:28px;bottom:20px;left:19px;
    width:2px;background:#e2e8f0;
  }
  .tl-mobile .tl-fill{
    position:absolute;top:28px;left:19px;width:2px;
    background:linear-gradient(180deg,#22c55e,#3b82f6);
    transition:height .6s;border-radius:2px;
  }
  .tl-m-item{
    display:flex;align-items:center;gap:14px;
    padding:8px 0;position:relative;z-index:1;
  }
  .tl-m-icon{
    width:40px;height:40px;border-radius:50%;
    display:flex;align-items:center;justify-content:center;
    font-size:14px;flex-shrink:0;
    background:#e2e8f0;color:#94a3b8;border:3px solid #fff;
    box-shadow:0 0 0 2px #e2e8f0;
  }
  .tl-m-icon.done{background:#22c55e;color:#fff;box-shadow:0 0 0 3px rgba(34,197,94,.25)}
  .tl-m-icon.active{
    background:#3b82f6;color:#fff;
    box-shadow:0 0 0 4px rgba(59,130,246,.25);
    animation:pulse-ring 1.5s infinite;
  }
  .tl-m-label{flex:1;min-width:0}
  .tl-m-label .nm{font-size:13px;font-weight:600;color:#334155;display:block}
  .tl-m-label .nm.done{color:#15803d}
  .tl-m-label .nm.active{color:#1d4ed8}
  .tl-m-label .st{
    font-size:11px;color:#94a3b8;display:block;margin-top:2px;
    text-transform:uppercase;letter-spacing:.05em;
  }

  .search-form{flex-direction:column}
  .search-form input,.search-form button{width:100%}

  .info-row{font-size:12.5px}
  .info-row .ir-v{font-size:12.5px}
}
</style>
</head>
<body>
<?php $active_page = 'track'; include 'customer_layout.php'; ?>

<div class="main-content">

  <div class="page-header">
    <div>
      <h1><i class="fas fa-truck me-2 text-primary"></i>Track Your Order</h1>
      <p>Enter your order ID or select from your orders</p>
    </div>
    <a href="my_orders.php" class="btn btn-outline-secondary btn-sm">
      <i class="fas fa-list me-1"></i> All My Orders
    </a>
  </div>

  <!-- Search -->
  <div class="form-card mb-4">
    <form method="GET" class="search-form">
      <input type="text" name="id"
             placeholder="Enter Order ID (e.g. ORD-20240115-ABCDEF)"
             value="<?php echo htmlspecialchars($order_id); ?>">
      <button type="submit" class="btn btn-primary">
        <i class="fas fa-search me-1"></i> Track
      </button>
    </form>
  </div>

  <?php if ($order_id && !$order): ?>
  <div class="alert alert-danger">
    <i class="fas fa-exclamation-circle me-2"></i>
    Order <strong><?php echo htmlspecialchars($order_id); ?></strong> not found or does not belong to your account.
  </div>
  <?php endif; ?>

  <?php if ($order): ?>

  <div class="tracking-card">
    <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
      <h5 class="fw-bold mb-0">
        <i class="fas fa-map-marked-alt me-2 text-primary"></i>
        Tracking: <?php echo htmlspecialchars($order['order_id']); ?>
      </h5>
      <span class="sbadge <?php echo $ord_badge[$order['status']] ?? 'sb-processing'; ?>"><?php echo $order['status']; ?></span>
    </div>

    <!-- Progress Bar -->
    <div class="my-3">
      <div class="d-flex justify-content-between mb-1">
        <small class="text-muted">Delivery Progress</small>
        <small class="fw-bold text-success"><?php echo $progress; ?>%</small>
      </div>
      <div class="progress" style="height:7px;border-radius:4px;">
        <div class="progress-bar" style="width:<?php echo $progress; ?>%;background:linear-gradient(90deg,#22c55e,#3b82f6);"></div>
      </div>
    </div>

    <!-- Desktop horizontal timeline -->
    <div class="timeline-wrap">
      <div class="timeline-track">
        <div class="timeline-fill" style="width:<?php echo $progress; ?>%"></div>
      </div>
      <div class="timeline-steps">
        <?php foreach ($steps as $s): ?>
        <div class="t-step">
          <div class="t-icon <?php echo $s['done'] ? 'done' : ($s['active'] ? 'active' : ''); ?>">
            <i class="fas <?php echo $s['icon']; ?>"></i>
          </div>
          <div class="t-label">
            <span class="name <?php echo $s['done'] ? 'done' : ($s['active'] ? 'active' : ''); ?>"><?php echo $s['label']; ?></span>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Mobile vertical timeline -->
    <div class="tl-mobile">
      <div class="tl-fill" style="height:<?php echo max(0, $progress - 8); ?>%"></div>
      <?php foreach ($steps as $s):
        $cls = $s['done'] ? 'done' : ($s['active'] ? 'active' : '');
      ?>
      <div class="tl-m-item">
        <div class="tl-m-icon <?php echo $cls; ?>">
          <i class="fas <?php echo $s['icon']; ?>"></i>
        </div>
        <div class="tl-m-label">
          <span class="nm <?php echo $cls; ?>"><?php echo $s['label']; ?></span>
          <span class="st"><?php echo $s['done'] ? 'Completed' : ($s['active'] ? 'In progress' : 'Pending'); ?></span>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Details -->
  <div class="row g-3">
    <div class="col-12 col-md-6">
      <div class="form-card">
        <h6 class="fw-bold mb-3"><i class="fas fa-box me-2 text-primary"></i>Order Details</h6>
        <div class="info-row">
          <span class="ir-k">Product</span>
          <span class="ir-v"><?php echo htmlspecialchars($order['product_name']); ?></span>
        </div>
        <div class="info-row">
          <span class="ir-k">Quantity</span>
          <span class="ir-v"><?php echo $order['quantity']; ?></span>
        </div>
        <div class="info-row">
          <span class="ir-k">Total Amount</span>
          <span class="ir-v text-success">TZS <?php echo number_format($order['total_price'], 0); ?></span>
        </div>
        <div class="info-row">
          <span class="ir-k">Order Date</span>
          <span class="ir-v"><?php echo date('d M Y', strtotime($order['date_created'])); ?></span>
        </div>
      </div>
    </div>
    <div class="col-12 col-md-6">
      <div class="form-card">
        <h6 class="fw-bold mb-3"><i class="fas fa-ship me-2 text-primary"></i>Payment &amp; Shipment</h6>
        <div class="info-row">
          <span class="ir-k">Payment Method</span>
          <span class="ir-v"><?php echo htmlspecialchars($order['payment_method'] ?? 'N/A'); ?></span>
        </div>
        <div class="info-row">
          <span class="ir-k">Payment Status</span>
          <span class="ir-v">
            <span class="sbadge <?php echo in_array($order['payment_status'], ['completed','Approved']) ? 'sb-completed' : 'sb-pending'; ?>">
              <?php echo ucfirst($order['payment_status'] ?? 'Pending'); ?>
            </span>
          </span>
        </div>
        <div class="info-row">
          <span class="ir-k">Tracking No.</span>
          <span class="ir-v">
            <?php if ($order['tracking_number']): ?>
              <code style="background:#f1f5f9;padding:2px 7px;border-radius:5px;font-size:12px;word-break:break-all;"><?php echo htmlspecialchars($order['tracking_number']); ?></code>
            <?php else: ?>
              <span class="text-muted" style="font-weight:400;font-size:12px;">Not assigned yet</span>
            <?php endif; ?>
          </span>
        </div>
        <div class="info-row">
          <span class="ir-k">Courier</span>
          <span class="ir-v"><?php echo htmlspecialchars($order['courier'] ?? 'N/A'); ?></span>
        </div>
      </div>
    </div>
  </div>

  <div class="text-center mt-4 d-flex flex-wrap gap-2 justify-content-center">
    <a href="my_orders.php" class="btn btn-outline-primary">
      <i class="fas fa-list me-1"></i> All My Orders
    </a>
    <?php if ($order['status'] === 'Delivered'): ?>
    <a href="request_refund.php?order_id=<?php echo urlencode($order['order_id']); ?>" class="btn btn-outline-danger">
      <i class="fas fa-undo me-1"></i> Request Refund
    </a>
    <?php endif; ?>
  </div>

  <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>