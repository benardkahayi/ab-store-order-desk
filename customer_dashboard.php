<?php
session_start();
require 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'customer') {
    header("Location: customer_login.php");
    exit;
}

$customer_id = $_SESSION['customer_id'];
$order_id    = $_SESSION['order_id'] ?? '';

// Customer info
$stmt = $conn->prepare("SELECT * FROM customers WHERE id = ?");
$stmt->execute([$customer_id]);
$customer = $stmt->fetch();
if (!$customer) { session_destroy(); header("Location: customer_login.php"); exit; }

// Current order with shipment & payment
$current_order  = null;
$tracking_status = [];
if (!empty($order_id)) {
    $os = $conn->prepare("
        SELECT o.*,
               COALESCE(p.payment_method,'Not Set') AS payment_method,
               COALESCE(p.status,'pending')         AS payment_status,
               p.date_created                       AS payment_date,
               s.status                             AS shipment_status,
               s.tracking_number, s.courier, s.estimated_days
        FROM orders o
        LEFT JOIN payments p ON o.order_id = p.order_id
        LEFT JOIN shipments s ON o.order_id = s.order_id
        WHERE o.order_id = ? AND o.phone = ?
    ");
    $os->execute([$order_id, $customer['phone']]);
    $current_order = $os->fetch();

    if ($current_order) {
        $os_status = $current_order['status'];
        $pm_status = $current_order['payment_status'];
        $sh_status = $current_order['shipment_status'] ?? '';

        $tracking_status = [
            'ordered'   => true,
            'paid'      => in_array($pm_status, ['completed','Approved']),
            'purchased' => in_array($os_status, ['Processing','Shipped','In Transit','Arrived TZ','Delivered']),
            'shipped'   => in_array($os_status, ['Shipped','In Transit','Arrived TZ','Delivered']),
            'arrived'   => in_array($os_status, ['Arrived TZ','Delivered']) || $sh_status === 'arrived_tz',
            'delivered' => $os_status === 'Delivered',
        ];
    }
}

// Recent orders
$ro = $conn->prepare("
    SELECT o.*, s.status AS shipment_status
    FROM orders o
    LEFT JOIN shipments s ON o.order_id = s.order_id
    WHERE o.phone = ?
    ORDER BY o.date_created DESC LIMIT 5
");
$ro->execute([$customer['phone']]);
$recent_orders = $ro->fetchAll();

// Payment history
$ph = $conn->prepare("
    SELECT p.*, o.product_name
    FROM payments p
    JOIN orders o ON p.order_id = o.order_id
    WHERE o.phone = ?
    ORDER BY p.date_created DESC LIMIT 4
");
$ph->execute([$customer['phone']]);
$payments = $ph->fetchAll();

// Stats
$stmt = $conn->prepare("SELECT COUNT(*) FROM orders WHERE phone = ?");
$stmt->execute([$customer['phone']]);
$total_orders = $stmt->fetchColumn();

$stmt = $conn->prepare("SELECT COALESCE(SUM(total_price),0) FROM orders WHERE phone = ?");
$stmt->execute([$customer['phone']]);
$total_spent = $stmt->fetchColumn();

$stmt = $conn->prepare("SELECT COUNT(*) FROM orders WHERE phone = ? AND status IN ('Processing','Shipped','In Transit','Arrived TZ')");
$stmt->execute([$customer['phone']]);
$in_transit = $stmt->fetchColumn();

$stmt = $conn->prepare("SELECT COUNT(*) FROM orders WHERE phone = ? AND status = 'Delivered'");
$stmt->execute([$customer['phone']]);
$delivered_count = $stmt->fetchColumn();

$wallet_balance = 0; // Wallet feature placeholder

// How far through the timeline?
$steps_done = count(array_filter($tracking_status));
$total_steps = 6;
$progress_pct = $total_steps > 1 ? round(($steps_done - 1) / ($total_steps - 1) * 100) : 0;

$cname = $customer['name'];
$progress_pct = max(0, min(100, $progress_pct));

$badge_for = function ($s) {
    return match (true) {
        $s === 'Delivered'                      => 'sb-delivered',
        in_array($s, ['Shipped', 'In Transit']) => 'sb-transit',
        $s === 'Arrived TZ'                     => 'sb-arrived',
        $s === 'Cancelled'                      => 'sb-cancelled',
        default                                 => 'sb-processing',
    };
};
?>
<!DOCTYPE html>
<html lang="sw">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<title>Dashboard - AB FASHIONSTORE</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
/* Page-level styles that complement customer_layout.php */
.min-w-0{min-width:0}

/* ── Welcome strip ── */
.welcome-strip{
  background:linear-gradient(135deg,#1e3a8a,#3b82f6);
  border-radius:14px;padding:18px 22px;margin-bottom:20px;color:#fff;
  display:flex;justify-content:space-between;align-items:center;gap:14px;flex-wrap:wrap;
}
.welcome-strip h1{font-size:19px;font-weight:700;margin:0;overflow-wrap:anywhere}
.welcome-strip p{font-size:12px;opacity:.8;margin:4px 0 0}
.welcome-strip .btn{font-size:13px;font-weight:600;min-height:40px;display:inline-flex;align-items:center}

/* ── Stat cards ── */
.stat-card{height:100%;display:flex;flex-direction:column}
.stat-card .s-lbl{font-size:11px;color:#64748b;margin:0 0 4px}
.stat-card .s-val{font-weight:800;margin:0;line-height:1.15;font-size:24px;overflow-wrap:anywhere}
.stat-card .s-val.money{font-size:17px}
.stat-link{font-size:11px;color:#3b82f6;text-decoration:none;display:block;margin-top:auto;padding-top:8px}
.stat-link:hover{text-decoration:underline}

/* ── Tracking card ── */
.tracking-card{background:#fff;border-radius:14px;padding:22px;box-shadow:0 2px 8px rgba(0,0,0,.05);margin-bottom:20px}
.tracking-card h5{font-size:15px;font-weight:700;color:#0f172a;margin:0}
.tc-head{display:flex;justify-content:space-between;align-items:center;gap:8px 14px;flex-wrap:wrap}
.tc-id{display:flex;align-items:center;gap:8px;flex-wrap:wrap;font-size:12px;color:#64748b}
.tc-id strong{font-size:13px;color:#3b82f6;word-break:break-all}

/* Horizontal timeline (tablet / desktop) */
.timeline-wrap{position:relative;padding:30px 10px 10px}
.timeline-track{
  position:absolute;top:51px;left:calc(10px + 8.33%);right:calc(10px + 8.33%);
  height:3px;background:#e2e8f0;border-radius:4px;
}
.timeline-fill{height:100%;background:linear-gradient(90deg,#22c55e,#3b82f6);border-radius:4px;transition:width .6s}
.timeline-steps{display:flex;justify-content:space-between;position:relative;z-index:1}
.t-step{display:flex;flex-direction:column;align-items:center;gap:8px;flex:1;min-width:0}
.t-icon{
  width:44px;height:44px;border-radius:50%;
  display:flex;align-items:center;justify-content:center;font-size:17px;
  background:#e2e8f0;color:#94a3b8;border:3px solid #fff;box-shadow:0 0 0 2px #e2e8f0;
}
.t-icon.done{background:#22c55e;color:#fff;box-shadow:0 0 0 3px rgba(34,197,94,.25)}
.t-icon.active{background:#3b82f6;color:#fff;box-shadow:0 0 0 4px rgba(59,130,246,.25);animation:pulse-ring 1.5s infinite}
@keyframes pulse-ring{
  0%{box-shadow:0 0 0 3px rgba(59,130,246,.35)}
  70%{box-shadow:0 0 0 8px rgba(59,130,246,0)}
  100%{box-shadow:0 0 0 3px rgba(59,130,246,.35)}
}
.t-label{text-align:center;padding:0 2px}
.t-label .name{font-size:11px;font-weight:600;color:#334155;display:block;line-height:1.2}
.t-label .date{font-size:10px;color:#94a3b8;display:block;margin-top:2px}
.t-label .name.done{color:#15803d}
.t-label .name.active{color:#1d4ed8}

/* Vertical timeline (phones) */
.tl-mobile{display:none}

/* Order detail under the timeline */
.order-detail-card{
  background:#f8fafc;border-radius:10px;padding:14px 16px;
  display:flex;gap:16px;align-items:flex-start;margin-top:18px;
}
.order-img-placeholder{
  width:56px;height:56px;border-radius:8px;background:#e2e8f0;
  display:flex;align-items:center;justify-content:center;color:#94a3b8;font-size:22px;flex-shrink:0;
}
.od-meta{flex:1;min-width:0}
.od-meta strong{font-size:14px;color:#0f172a;display:block;overflow-wrap:anywhere}
.od-meta small{color:#64748b;font-size:12px}
.od-cols{display:flex;gap:10px 28px;margin-top:10px;flex-wrap:wrap}
.od-col label{font-size:10px;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px;display:block}
.od-col span{font-size:13px;font-weight:600;color:#1e293b;overflow-wrap:anywhere}
.od-action .btn{font-size:12px;min-height:38px;display:inline-flex;align-items:center;justify-content:center;white-space:nowrap}

/* Section heads */
.sec-head{display:flex;justify-content:space-between;align-items:center;gap:10px;margin-bottom:12px}
.sec-head h6{font-size:14px;font-weight:700;margin:0;color:#0f172a}
.sec-head .btn{font-size:12px;white-space:nowrap}

/* Mobile list rows (replace tables on phones) */
.m-row{
  display:flex;justify-content:space-between;align-items:center;gap:12px;
  padding:11px 0;border-bottom:1px solid #f1f5f9;text-decoration:none;color:inherit;
}
.m-row:last-child{border-bottom:none}
.m-row .m-main{min-width:0;flex:1}
.m-row .m-title{font-size:13px;font-weight:600;color:#1e293b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.m-row .m-sub{font-size:11px;color:#94a3b8;margin-top:2px}
.m-row .m-side{text-align:right;flex-shrink:0}
.m-row .m-amt{font-size:12.5px;font-weight:700;color:#0f172a;display:block;margin-bottom:3px}

/* Notifications card */
.notif-card{background:#fff;border-radius:13px;padding:18px;box-shadow:0 2px 8px rgba(0,0,0,.05)}
.notif-card .n-item{display:flex;align-items:flex-start;gap:10px;padding:9px 0;border-bottom:1px solid #f1f5f9}
.notif-card .n-item:last-child{border-bottom:none}
.notif-card .n-item p{font-size:12.5px;font-weight:600;margin:0;overflow-wrap:anywhere}
.notif-card .n-item span{font-size:11px;color:#94a3b8}
.ni-icon{width:34px;height:34px;border-radius:9px;flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:14px}
.ni-icon.truck{background:#dbeafe;color:#2563eb}
.ni-icon.pay{background:#dcfce7;color:#16a34a}
.ni-icon.ship{background:#ede9fe;color:#7c3aed}

/* Wallet card */
.wallet-card{background:linear-gradient(135deg,#1e3a8a,#3b82f6);border-radius:13px;padding:20px;color:#fff}
.wallet-card .balance{font-size:26px;font-weight:800;overflow-wrap:anywhere}
.wallet-card .label{font-size:11px;opacity:.7;margin-bottom:2px}
.wallet-card .avail{font-size:11px;opacity:.6;margin-top:2px}
.wallet-actions{display:flex;gap:8px;flex-wrap:wrap;margin-top:14px}
.wallet-actions button{
  flex:1 1 140px;min-height:40px;border-radius:8px;padding:8px 14px;
  font-size:13px;font-weight:600;cursor:pointer;
}
.btn-topup{background:rgba(255,255,255,.15);color:#fff;border:1.5px solid rgba(255,255,255,.3)}
.btn-topup:hover{background:rgba(255,255,255,.25)}
.btn-txhist{background:#fff;color:#1e3a8a;border:none}

/* Quick actions */
.qa-card{
  background:#fff;border-radius:13px;padding:14px;height:100%;
  box-shadow:0 2px 8px rgba(0,0,0,.05);
  display:flex;align-items:center;gap:12px;min-height:64px;
  text-decoration:none;color:inherit;transition:transform .2s,box-shadow .2s;
}
.qa-card:hover{transform:translateY(-3px);box-shadow:0 6px 16px rgba(0,0,0,.1);color:inherit}
.qa-icon{width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:17px;flex-shrink:0}
.qa-card .qa-title{font-size:13px;font-weight:600;color:#1e293b;margin:0;line-height:1.25}
.qa-card .qa-sub{font-size:11px;color:#94a3b8;margin:0}

/* ════ Tablet ════ */
@media (max-width:992px){
  .t-label .name{font-size:10.5px}
}

/* ════ Phones ════ */
@media (max-width:640px){
  .welcome-strip{padding:16px;border-radius:12px;margin-bottom:14px}
  .welcome-strip h1{font-size:16px}
  .welcome-strip .btn{width:100%;justify-content:center}

  .stat-card .s-val{font-size:20px}
  .stat-card .s-val.money{font-size:14px}

  .tracking-card{padding:16px 14px;border-radius:12px}
  .tracking-card h5{font-size:14px}

  /* Swap horizontal timeline for vertical one */
  .timeline-wrap{display:none}
  .tl-mobile{display:block;position:relative;padding:12px 0 0}
  .tl-mobile::before{content:'';position:absolute;top:28px;bottom:28px;left:19px;width:2px;background:#e2e8f0}
  .tl-mobile .tl-fill{position:absolute;top:28px;left:19px;width:2px;background:linear-gradient(180deg,#22c55e,#3b82f6);border-radius:2px}
  .tl-m-item{display:flex;align-items:center;gap:14px;padding:7px 0;position:relative;z-index:1}
  .tl-m-icon{
    width:40px;height:40px;border-radius:50%;display:flex;align-items:center;justify-content:center;
    font-size:14px;flex-shrink:0;background:#e2e8f0;color:#94a3b8;border:3px solid #fff;box-shadow:0 0 0 2px #e2e8f0;
  }
  .tl-m-icon.done{background:#22c55e;color:#fff;box-shadow:0 0 0 3px rgba(34,197,94,.25)}
  .tl-m-icon.active{background:#3b82f6;color:#fff;box-shadow:0 0 0 4px rgba(59,130,246,.25);animation:pulse-ring 1.5s infinite}
  .tl-m-label{flex:1;min-width:0;display:flex;justify-content:space-between;gap:8px;align-items:baseline}
  .tl-m-label .nm{font-size:13px;font-weight:600;color:#334155}
  .tl-m-label .nm.done{color:#15803d}
  .tl-m-label .nm.active{color:#1d4ed8}
  .tl-m-label .dt{font-size:11px;color:#94a3b8;white-space:nowrap}

  .order-detail-card{flex-wrap:wrap;gap:12px;padding:12px}
  .order-img-placeholder{width:46px;height:46px;font-size:18px}
  .od-cols{display:grid;grid-template-columns:1fr 1fr;gap:10px 14px}
  .od-action{flex-basis:100%}
  .od-action .btn{width:100%}

  .sec-head h6{font-size:13.5px}
  .notif-card{padding:14px;border-radius:12px}
  .wallet-card{padding:16px;border-radius:12px}
  .wallet-card .balance{font-size:22px}

  .qa-card{flex-direction:column;align-items:flex-start;gap:8px;padding:12px}
  .qa-icon{width:36px;height:36px;font-size:15px}
}
</style>
</head>
<body>
<?php $active_page = 'dashboard'; include 'customer_layout.php'; ?>

<div class="main-content">

  <!-- Welcome -->
  <div class="welcome-strip">
    <div>
      <h1>Hello, <?php echo htmlspecialchars($cname); ?> 👋</h1>
      <p>Here's what's happening with your orders today.</p>
    </div>
    <a href="track_order.php" class="btn btn-light">
      <i class="fas fa-truck me-2"></i>Track Order
    </a>
  </div>

  <!-- Stat cards: 2 per row on phones (last one full width), 3+2 on tablet, 5 across on xl -->
  <div class="row g-3 mb-4">
    <div class="col-6 col-md-4 col-xl">
      <div class="stat-card">
        <div class="d-flex justify-content-between align-items-start gap-2">
          <div class="min-w-0">
            <p class="s-lbl">My Orders</p>
            <h2 class="s-val" style="color:#3b82f6;"><?php echo $total_orders; ?></h2>
          </div>
          <div class="stat-icon" style="background:#dbeafe;color:#2563eb;"><i class="fas fa-shopping-bag"></i></div>
        </div>
        <a href="my_orders.php" class="stat-link">View all orders →</a>
      </div>
    </div>
    <div class="col-6 col-md-4 col-xl">
      <div class="stat-card">
        <div class="d-flex justify-content-between align-items-start gap-2">
          <div class="min-w-0">
            <p class="s-lbl">In Transit</p>
            <h2 class="s-val" style="color:#f59e0b;"><?php echo $in_transit; ?></h2>
          </div>
          <div class="stat-icon" style="background:#fef3c7;color:#d97706;"><i class="fas fa-truck"></i></div>
        </div>
        <a href="track_order.php" class="stat-link" style="color:#d97706;">View shipments →</a>
      </div>
    </div>
    <div class="col-6 col-md-4 col-xl">
      <div class="stat-card">
        <div class="d-flex justify-content-between align-items-start gap-2">
          <div class="min-w-0">
            <p class="s-lbl">Delivered</p>
            <h2 class="s-val" style="color:#22c55e;"><?php echo $delivered_count; ?></h2>
          </div>
          <div class="stat-icon" style="background:#dcfce7;color:#16a34a;"><i class="fas fa-check-circle"></i></div>
        </div>
        <a href="my_orders.php" class="stat-link" style="color:#16a34a;">View delivered →</a>
      </div>
    </div>
    <div class="col-6 col-md-6 col-xl">
      <div class="stat-card">
        <div class="d-flex justify-content-between align-items-start gap-2">
          <div class="min-w-0">
            <p class="s-lbl">Wallet Balance</p>
            <h2 class="s-val money" style="color:#3b82f6;">TZS <?php echo number_format($wallet_balance); ?></h2>
          </div>
          <div class="stat-icon" style="background:#dbeafe;color:#2563eb;"><i class="fas fa-wallet"></i></div>
        </div>
        <a href="#" class="stat-link">Top up wallet →</a>
      </div>
    </div>
    <div class="col-12 col-md-6 col-xl">
      <div class="stat-card">
        <div class="d-flex justify-content-between align-items-start gap-2">
          <div class="min-w-0">
            <p class="s-lbl">Total Spent</p>
            <h2 class="s-val money" style="color:#ef4444;">TZS <?php echo number_format($total_spent, 0); ?></h2>
          </div>
          <div class="stat-icon" style="background:#fee2e2;color:#dc2626;"><i class="fas fa-receipt"></i></div>
        </div>
        <a href="my_payments.php" class="stat-link" style="color:#ef4444;">View summary →</a>
      </div>
    </div>
  </div>

  <!-- Current Order Tracking -->
  <?php if ($current_order):
      $steps = [
          ['key'=>'ordered',   'icon'=>'fa-user-check',    'label'=>'Ordered',            'date'=> date('d M Y', strtotime($current_order['date_created']))],
          ['key'=>'paid',      'icon'=>'fa-check',         'label'=>'Paid',               'date'=> $current_order['payment_date'] ? date('d M Y', strtotime($current_order['payment_date'])) : '—'],
          ['key'=>'purchased', 'icon'=>'fa-shopping-cart', 'label'=>'Purchased in China', 'date'=>'—'],
          ['key'=>'shipped',   'icon'=>'fa-plane',         'label'=>'Shipped',            'date'=>'—'],
          ['key'=>'arrived',   'icon'=>'fa-plane-arrival', 'label'=>'Arrived in Tanzania','date'=>'—'],
          ['key'=>'delivered', 'icon'=>'fa-home',          'label'=>'Delivered',          'date'=>'—'],
      ];
      $active_key = null;
      foreach ($steps as $st) {
          if (!($tracking_status[$st['key']] ?? false)) { $active_key = $st['key']; break; }
      }
  ?>
  <div class="tracking-card">
    <div class="tc-head">
      <h5>Current Order Tracking</h5>
      <div class="tc-id">
        <span>Order ID:</span>
        <strong><?php echo htmlspecialchars($current_order['order_id']); ?></strong>
        <span class="sbadge <?php echo $badge_for($current_order['status']); ?>"><?php echo htmlspecialchars($current_order['status']); ?></span>
      </div>
    </div>

    <!-- Horizontal timeline (≥ 641px) -->
    <div class="timeline-wrap">
      <div class="timeline-track">
        <div class="timeline-fill" style="width:<?php echo $progress_pct; ?>%;"></div>
      </div>
      <div class="timeline-steps">
        <?php foreach ($steps as $st):
            $done = $tracking_status[$st['key']] ?? false;
            $cls  = $done ? 'done' : ($st['key'] === $active_key ? 'active' : '');
        ?>
        <div class="t-step">
          <div class="t-icon <?php echo $cls; ?>"><i class="fas <?php echo $st['icon']; ?>"></i></div>
          <div class="t-label">
            <span class="name <?php echo $cls; ?>"><?php echo $st['label']; ?></span>
            <span class="date"><?php echo $st['date']; ?></span>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Vertical timeline (≤ 640px) -->
    <div class="tl-mobile">
      <div class="tl-fill" style="height:calc((100% - 56px) * <?php echo $progress_pct / 100; ?>);"></div>
      <?php foreach ($steps as $st):
          $done = $tracking_status[$st['key']] ?? false;
          $cls  = $done ? 'done' : ($st['key'] === $active_key ? 'active' : '');
      ?>
      <div class="tl-m-item">
        <div class="tl-m-icon <?php echo $cls; ?>"><i class="fas <?php echo $st['icon']; ?>"></i></div>
        <div class="tl-m-label">
          <span class="nm <?php echo $cls; ?>"><?php echo $st['label']; ?></span>
          <span class="dt"><?php echo $st['date']; ?></span>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <div class="order-detail-card">
      <div class="order-img-placeholder"><i class="fas fa-box"></i></div>
      <div class="od-meta">
        <strong><?php echo htmlspecialchars($current_order['product_name']); ?></strong>
        <small>Qty: <?php echo (int)($current_order['quantity'] ?? 1); ?></small>
        <div class="od-cols">
          <div class="od-col">
            <label>Total Price</label>
            <span style="color:#3b82f6;">TZS <?php echo number_format($current_order['total_price'], 0); ?></span>
          </div>
          <div class="od-col">
            <label>Order Date</label>
            <span><?php echo date('d M Y', strtotime($current_order['date_created'])); ?></span>
          </div>
          <div class="od-col">
            <label>Payment Method</label>
            <span><?php echo htmlspecialchars($current_order['payment_method']); ?></span>
          </div>
          <?php if (!empty($current_order['tracking_number'])): ?>
          <div class="od-col">
            <label>Tracking No.</label>
            <span><?php echo htmlspecialchars($current_order['tracking_number']); ?></span>
          </div>
          <?php endif; ?>
        </div>
      </div>
      <div class="od-action flex-shrink-0">
        <a href="track_order.php?id=<?php echo urlencode($current_order['order_id']); ?>" class="btn btn-primary">
          <i class="fas fa-eye me-1"></i> View Tracking Details
        </a>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <div class="row g-3">

    <!-- Left: recent orders + payments -->
    <div class="col-lg-7 d-flex flex-column gap-3">

      <div class="table-card">
        <div class="sec-head">
          <h6>My Recent Orders</h6>
          <a href="my_orders.php" class="btn btn-sm btn-primary px-3">View All</a>
        </div>

        <?php if ($recent_orders): ?>
        <!-- Table (≥ md) -->
        <div class="d-none d-md-block table-responsive">
          <table class="table table-hover mb-0">
            <thead><tr><th>Order ID</th><th>Product</th><th>Status</th><th>Total</th><th>Date</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($recent_orders as $o): ?>
            <tr>
              <td style="white-space:nowrap;"><strong><?php echo htmlspecialchars($o['order_id']); ?></strong></td>
              <td style="max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?php echo htmlspecialchars($o['product_name']); ?></td>
              <td><span class="sbadge <?php echo $badge_for($o['status']); ?>"><?php echo htmlspecialchars($o['status']); ?></span></td>
              <td style="white-space:nowrap;">TZS <?php echo number_format($o['total_price'], 0); ?></td>
              <td style="font-size:11.5px;color:#94a3b8;white-space:nowrap;"><?php echo date('d M Y', strtotime($o['date_created'])); ?></td>
              <td><a href="track_order.php?id=<?php echo urlencode($o['order_id']); ?>" class="btn btn-sm btn-outline-primary" style="font-size:11px;padding:2px 10px;">View</a></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <!-- List (< md) -->
        <div class="d-md-none">
          <?php foreach ($recent_orders as $o): ?>
          <a class="m-row" href="track_order.php?id=<?php echo urlencode($o['order_id']); ?>">
            <div class="m-main">
              <div class="m-title"><?php echo htmlspecialchars($o['product_name']); ?></div>
              <div class="m-sub"><?php echo htmlspecialchars($o['order_id']); ?> · <?php echo date('d M Y', strtotime($o['date_created'])); ?></div>
            </div>
            <div class="m-side">
              <span class="m-amt">TZS <?php echo number_format($o['total_price'], 0); ?></span>
              <span class="sbadge <?php echo $badge_for($o['status']); ?>"><?php echo htmlspecialchars($o['status']); ?></span>
            </div>
          </a>
          <?php endforeach; ?>
        </div>
        <?php else: ?>
        <p class="text-center py-4 text-muted mb-0">No orders yet</p>
        <?php endif; ?>
      </div>

      <div class="table-card">
        <div class="sec-head">
          <h6>Payment History</h6>
          <a href="my_payments.php" class="btn btn-sm btn-primary px-3">View All</a>
        </div>

        <?php if ($payments): ?>
        <div class="d-none d-md-block table-responsive">
          <table class="table table-hover mb-0">
            <thead><tr><th>Trans ID</th><th>Order ID</th><th>Amount</th><th>Date</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($payments as $p): $ps = $p['status'] ?? 'pending'; ?>
            <tr>
              <td>#PAY-<?php echo str_pad($p['id'], 4, '0', STR_PAD_LEFT); ?></td>
              <td><?php echo htmlspecialchars($p['order_id']); ?></td>
              <td style="white-space:nowrap;">TZS <?php echo number_format($p['amount'], 0); ?></td>
              <td style="font-size:11.5px;color:#94a3b8;white-space:nowrap;"><?php echo date('d M Y', strtotime($p['date_created'])); ?></td>
              <td><span class="sbadge sb-<?php echo htmlspecialchars(strtolower($ps)); ?>"><?php echo htmlspecialchars(ucfirst($ps)); ?></span></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <div class="d-md-none">
          <?php foreach ($payments as $p): $ps = $p['status'] ?? 'pending'; ?>
          <div class="m-row">
            <div class="m-main">
              <div class="m-title">#PAY-<?php echo str_pad($p['id'], 4, '0', STR_PAD_LEFT); ?></div>
              <div class="m-sub"><?php echo htmlspecialchars($p['order_id']); ?> · <?php echo date('d M Y', strtotime($p['date_created'])); ?></div>
            </div>
            <div class="m-side">
              <span class="m-amt">TZS <?php echo number_format($p['amount'], 0); ?></span>
              <span class="sbadge sb-<?php echo htmlspecialchars(strtolower($ps)); ?>"><?php echo htmlspecialchars(ucfirst($ps)); ?></span>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php else: ?>
        <p class="text-center py-4 text-muted mb-0">No payments yet</p>
        <?php endif; ?>
      </div>
    </div>

    <!-- Right: notifications, wallet, quick actions -->
    <div class="col-lg-5 d-flex flex-column gap-3">

      <div class="notif-card">
        <div class="sec-head mb-2">
          <h6>Recent Notifications</h6>
          <a href="my_orders.php" style="font-size:12px;color:#3b82f6;">View All</a>
        </div>
        <?php if ($current_order): ?>
        <div class="n-item">
          <div class="ni-icon truck"><i class="fas fa-truck"></i></div>
          <div class="flex-grow-1 min-w-0">
            <p>Your order <?php echo htmlspecialchars($current_order['order_id']); ?> is <?php echo htmlspecialchars(strtolower($current_order['status'])); ?></p>
            <span><?php echo date('d M Y', strtotime($current_order['date_created'])); ?></span>
          </div>
        </div>
          <?php if ($tracking_status['paid'] ?? false): ?>
          <div class="n-item">
            <div class="ni-icon pay"><i class="fas fa-credit-card"></i></div>
            <div class="flex-grow-1 min-w-0">
              <p>Payment received for <?php echo htmlspecialchars($current_order['order_id']); ?></p>
              <span><?php echo $current_order['payment_date'] ? date('d M Y', strtotime($current_order['payment_date'])) : 'Recently'; ?></span>
            </div>
          </div>
          <?php endif; ?>
        <?php else: ?>
        <div class="n-item">
          <div class="ni-icon ship"><i class="fas fa-box"></i></div>
          <div>
            <p>No new notifications</p>
            <span>Place an order to get started</span>
          </div>
        </div>
        <?php endif; ?>
      </div>

      <div class="wallet-card">
        <p class="label mb-0">Wallet Balance</p>
        <div class="balance">TZS <?php echo number_format($wallet_balance); ?></div>
        <p class="avail mb-0">Available Balance</p>
        <div class="wallet-actions">
          <button class="btn-topup" type="button" onclick="alert('Top up feature coming soon!')">
            <i class="fas fa-plus me-1"></i> Top Up
          </button>
          <button class="btn-txhist" type="button" onclick="window.location='my_payments.php'">
            Transactions
          </button>
        </div>
      </div>

      <div>
        <h6 class="mb-2" style="font-size:13px;font-weight:700;color:#475569;">Quick Actions</h6>
        <div class="row g-2">
          <div class="col-6">
            <a href="track_order.php" class="qa-card">
              <div class="qa-icon" style="background:#dbeafe;color:#2563eb;"><i class="fas fa-truck"></i></div>
              <div><p class="qa-title">Track Shipment</p><p class="qa-sub">Track your package</p></div>
            </a>
          </div>
          <div class="col-6">
            <a href="my_orders.php" class="qa-card">
              <div class="qa-icon" style="background:#f3e8ff;color:#7c3aed;"><i class="fas fa-shopping-bag"></i></div>
              <div><p class="qa-title">My Orders</p><p class="qa-sub">View all orders</p></div>
            </a>
          </div>
          <div class="col-6">
            <a href="request_refund.php" class="qa-card">
              <div class="qa-icon" style="background:#fee2e2;color:#dc2626;"><i class="fas fa-undo"></i></div>
              <div><p class="qa-title">Refund Request</p><p class="qa-sub">Request refund</p></div>
            </a>
          </div>
          <div class="col-6">
            <a href="give_feedback.php" class="qa-card">
              <div class="qa-icon" style="background:#dcfce7;color:#16a34a;"><i class="fas fa-comment-dots"></i></div>
              <div><p class="qa-title">Give Feedback</p><p class="qa-sub">Share your feedback</p></div>
            </a>
          </div>
        </div>
      </div>

    </div>
  </div>

</div><!-- /main-content -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
