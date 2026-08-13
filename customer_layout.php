<?php
// customer_layout.php — shared sidebar + navbar for all customer pages
// Required before include: $active_page, $customer (array from DB)
// Optional: $wallet_balance (defaults to 0)
if (!isset($wallet_balance)) $wallet_balance = 0;
$cname = $customer['name'] ?? 'Customer';
$cphone = $customer['phone'] ?? '';

$page_titles = [
    'dashboard' => ['title' => 'Dashboard',       'sub' => 'Overview of your account'],
    'orders'    => ['title' => 'My Orders',        'sub' => 'All your orders in one place'],
    'track'     => ['title' => 'Track Shipment',   'sub' => 'Real-time shipment tracking'],
    'payments'  => ['title' => 'My Payments',      'sub' => 'Your payment history'],
    'refund'    => ['title' => 'Refund Requests',  'sub' => 'Submit and track refund requests'],
    'feedback'  => ['title' => 'Give Feedback',    'sub' => 'Share your experience with us'],
    'profile'   => ['title' => 'My Profile',       'sub' => 'Manage your account information'],
];
$pt = $page_titles[$active_page] ?? ['title' => 'Dashboard', 'sub' => ''];

// ── Notifications ──
$_notif_orders = $conn->prepare("
    SELECT order_id, product_name, status, date_created
    FROM orders WHERE phone = ?
    ORDER BY date_created DESC LIMIT 6
");
$_notif_orders->execute([$cphone]);
$_notif_orders = $_notif_orders->fetchAll();

$_notif_refunds = $conn->prepare("
    SELECT r.id, r.status, r.date_created, o.product_name, o.order_id
    FROM refunds r LEFT JOIN orders o ON r.order_id = o.order_id
    WHERE r.customer_id = ?
    ORDER BY r.date_created DESC LIMIT 3
");
$_notif_refunds->execute([$customer['id'] ?? 0]);
$_notif_refunds = $_notif_refunds->fetchAll();

$_active_orders = count(array_filter($_notif_orders, fn($o) => !in_array($o['status'], ['Delivered','Cancelled'])));
$_pending_refunds = count(array_filter($_notif_refunds, fn($r) => $r['status'] === 'Pending'));
$_notif_count = $_active_orders + $_pending_refunds;
?>
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Inter','Segoe UI',sans-serif; background: #f0f2f5; overflow-x: hidden; }

    /* ── Sidebar ── */
    .sidebar {
        position: fixed; left: 0; top: 0; width: 250px; height: 100%;
        background: linear-gradient(180deg,#0f1729 0%,#1a1a2e 100%);
        z-index: 1000; overflow-y: auto; display: flex; flex-direction: column;
        box-shadow: 3px 0 15px rgba(0,0,0,0.15);
        scrollbar-width: none;
    }
    .sidebar::-webkit-scrollbar { display: none; }
    .sidebar-header {
        padding: 20px 16px 14px;
        border-bottom: 1px solid rgba(255,255,255,0.08);
    }
    .logo { display: flex; align-items: center; gap: 10px; }
    .logo-icon {
        width: 40px; height: 40px; background: white; border-radius: 10px;
        padding: 3px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;
    }
    .logo-icon img { width: 34px; height: 34px; object-fit: cover; border-radius: 7px; }
    .logo-text h3 { color: white; font-size: 13px; font-weight: 700; margin: 0; line-height: 1.2; }
    .logo-text p  { color: rgba(255,255,255,0.5); font-size: 10px; margin: 0; }

    .cust-profile {
        margin: 12px; border-radius: 12px;
        background: rgba(255,255,255,0.07);
        padding: 14px 12px;
        display: flex; align-items: center; gap: 10px;
    }
    .cust-avatar {
        width: 40px; height: 40px; border-radius: 50%;
        background: linear-gradient(135deg,#3b82f6,#6366f1);
        display: flex; align-items: center; justify-content: center;
        color: white; font-weight: 700; font-size: 16px; flex-shrink: 0;
    }
    .cust-info .cname { color: white; font-size: 12.5px; font-weight: 600; margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 130px; }
    .cust-info .role-badge {
        background: #3b82f6; color: white; font-size: 9px; font-weight: 700;
        padding: 1px 6px; border-radius: 20px; text-transform: uppercase;
    }
    .verified-tick { color: #22c55e; font-size: 13px; margin-left: auto; flex-shrink: 0; }

    .sidebar-nav { padding: 6px 10px; flex: 1; }
    .sidebar-nav a {
        color: rgba(255,255,255,0.65); padding: 9px 11px; text-decoration: none;
        display: flex; align-items: center; gap: 10px;
        border-radius: 8px; margin-bottom: 2px; transition: all 0.2s; font-size: 13px;
    }
    .sidebar-nav a:hover { background: rgba(255,255,255,0.08); color: white; }
    .sidebar-nav a.active { background: rgba(59,130,246,0.22); color: white; }
    .sidebar-nav a.active i { color: #60a5fa; }
    .sidebar-nav a i { width: 17px; font-size: 13px; }
    .wallet-pill {
        margin-left: auto; background: rgba(59,130,246,0.3);
        color: #93c5fd; font-size: 10px; font-weight: 700;
        padding: 1px 7px; border-radius: 20px;
    }
    .nav-divider { border-color: rgba(255,255,255,0.08); margin: 6px 0; }

    .promo-box {
        margin: 10px 10px 8px; border-radius: 14px;
        background: linear-gradient(135deg,#1e3a8a,#1e40af);
        padding: 14px 12px; position: relative; overflow: hidden;
    }
    .promo-box::after {
        content: '✈'; position: absolute; right: -4px; bottom: -6px;
        font-size: 48px; opacity: 0.12;
    }
    .promo-box h6 { color: white; font-size: 12px; font-weight: 700; margin-bottom: 4px; }
    .promo-box p  { color: rgba(255,255,255,0.7); font-size: 10px; margin-bottom: 8px; line-height: 1.4; }
    .promo-box .btn-promo {
        background: #3b82f6; color: white; border: none; border-radius: 6px;
        padding: 4px 12px; font-size: 11px; font-weight: 600; cursor: pointer;
    }
    .wa-support {
        padding: 10px 14px 14px; display: flex; align-items: center; gap: 8px;
        border-top: 1px solid rgba(255,255,255,0.08);
    }
    .wa-support .wa-icon {
        width: 32px; height: 32px; background: #dcfce7; border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        color: #16a34a; font-size: 16px; flex-shrink: 0;
    }
    .wa-support .wa-text p { color: rgba(255,255,255,0.5); font-size: 9px; margin: 0; }
    .wa-support .wa-text span { color: white; font-size: 11px; font-weight: 600; }

    /* ── Top Navbar ── */
    .top-navbar {
        position: fixed; top: 0; left: 250px; right: 0; height: 62px;
        background: white; z-index: 999;
        display: flex; align-items: center; padding: 0 24px; gap: 14px;
        box-shadow: 0 1px 8px rgba(0,0,0,0.07);
    }
    .navbar-welcome { flex: 1; }
    .navbar-welcome h5 { font-size: 15px; font-weight: 700; color: #0f172a; margin: 0; }
    .navbar-welcome p  { font-size: 11px; color: #94a3b8; margin: 0; }
    .navbar-search { flex: 0 0 200px; position: relative; }
    .navbar-search input {
        width: 100%; padding: 7px 14px 7px 34px;
        border: 1.5px solid #e2e8f0; border-radius: 20px;
        font-size: 12.5px; background: #f8fafc; outline: none;
    }
    .navbar-search i { position: absolute; left: 11px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 12px; }
    .navbar-icons { display: flex; align-items: center; gap: 6px; }
    .nav-icon-btn {
        width: 34px; height: 34px; border-radius: 9px;
        border: none; background: #f1f5f9; color: #64748b;
        display: flex; align-items: center; justify-content: center;
        cursor: pointer; font-size: 14px; position: relative;
        text-decoration: none; transition: background 0.2s;
    }
    .nav-icon-btn:hover { background: #e2e8f0; color: #1e293b; }
    .nav-icon-btn .dot {
        position: absolute; top: 5px; right: 5px;
        width: 7px; height: 7px; background: #ef4444; border-radius: 50%;
        border: 1.5px solid white;
    }
    .nav-icon-btn.wa { background: #dcfce7; color: #16a34a; }
    .navbar-profile {
        display: flex; align-items: center; gap: 7px;
        padding: 3px 10px 3px 4px;
        border: 1.5px solid #e2e8f0; border-radius: 24px; cursor: pointer;
        text-decoration: none;
    }
    .navbar-profile .pa {
        width: 28px; height: 28px; border-radius: 50%;
        background: linear-gradient(135deg,#3b82f6,#6366f1);
        display: flex; align-items: center; justify-content: center;
        color: white; font-weight: 700; font-size: 11px;
    }
    .navbar-profile .pname  { font-size: 12px; font-weight: 600; color: #1e293b; }
    .navbar-profile .pphone { font-size: 10px; color: #94a3b8; }

    /* ── Main Content ── */
    .main-content { margin-left: 250px; padding: 78px 22px 30px; }

    /* ── Shared components ── */
    .page-header {
        background: white; border-radius: 14px; padding: 18px 22px;
        margin-bottom: 22px; box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap;
    }
    .page-header h1 { font-size: 20px; font-weight: 700; color: #0f172a; margin: 0; }
    .page-header p  { font-size: 12px; color: #64748b; margin: 4px 0 0; }

    .stat-card {
        background: white; border-radius: 13px; padding: 16px 18px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .stat-card:hover { transform: translateY(-3px); box-shadow: 0 6px 18px rgba(0,0,0,0.08); }
    .stat-icon {
        width: 42px; height: 42px; border-radius: 11px;
        display: flex; align-items: center; justify-content: center; font-size: 18px;
    }

    .table-card { background: white; border-radius: 13px; padding: 18px 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
    .table th { font-size: 11px; font-weight: 600; color: #64748b; background: #f8fafc; text-transform: uppercase; letter-spacing: 0.4px; padding: 10px 14px; }
    .table td { font-size: 13px; vertical-align: middle; padding: 11px 14px; }

    .form-card { background: white; border-radius: 14px; padding: 26px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }

    .tbl-search {
        border: 1.5px solid #e2e8f0; border-radius: 20px; padding: 7px 14px 7px 34px;
        font-size: 12.5px; background: #f8fafc; outline: none; width: 200px;
    }

    /* Status badges */
    .sbadge { display: inline-block; padding: 3px 9px; border-radius: 20px; font-size: 11px; font-weight: 600; }
    .sb-processing { background: #fef9c3; color: #854d0e; }
    .sb-shipped    { background: #dbeafe; color: #1d4ed8; }
    .sb-transit    { background: #ede9fe; color: #5b21b6; }
    .sb-arrived    { background: #fff7ed; color: #c2410c; }
    .sb-intransit  { background: #ede9fe; color: #5b21b6; }
    .sb-arrivedtz  { background: #fff7ed; color: #c2410c; }
    .sb-delivered  { background: #dcfce7; color: #15803d; }
    .sb-cancelled  { background: #fee2e2; color: #b91c1c; }
    .sb-completed  { background: #dcfce7; color: #15803d; }
    .sb-pending    { background: #fef9c3; color: #854d0e; }
    .sb-failed     { background: #fee2e2; color: #b91c1c; }
    .sb-refunded   { background: #f3e8ff; color: #6b21a8; }

    /* ── Notification dropdown ── */
    .notif-badge {
        position: absolute; top: 4px; right: 4px;
        width: 16px; height: 16px; background: #ef4444; border-radius: 50%;
        border: 2px solid white; display: flex; align-items: center;
        justify-content: center; font-size: 9px; font-weight: 700; color: white;
    }
    .notif-dropdown {
        width: 320px; padding: 0; border: none;
        border-radius: 14px; box-shadow: 0 10px 40px rgba(0,0,0,0.14);
        overflow: hidden;
    }
    .notif-header {
        padding: 14px 16px; background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        display: flex; justify-content: space-between; align-items: center;
    }
    .notif-header h6 { font-size: 13px; font-weight: 700; color: #0f172a; margin: 0; }
    .notif-header span { font-size: 11px; color: #64748b; }
    .notif-list { max-height: 320px; overflow-y: auto; }
    .notif-item {
        display: flex; align-items: flex-start; gap: 10px;
        padding: 11px 16px; border-bottom: 1px solid #f1f5f9;
        text-decoration: none; transition: background 0.15s;
    }
    .notif-item:hover { background: #f8fafc; }
    .notif-item:last-child { border-bottom: none; }
    .ni {
        width: 36px; height: 36px; border-radius: 10px; flex-shrink: 0;
        display: flex; align-items: center; justify-content: center; font-size: 15px;
    }
    .ni-truck   { background: #dbeafe; color: #2563eb; }
    .ni-ship    { background: #ede9fe; color: #7c3aed; }
    .ni-done    { background: #dcfce7; color: #16a34a; }
    .ni-pin     { background: #fff7ed; color: #ea580c; }
    .ni-process { background: #fef9c3; color: #d97706; }
    .ni-cancel  { background: #fee2e2; color: #dc2626; }
    .ni-refund  { background: #f3e8ff; color: #7c3aed; }
    .ni-refund-ok { background: #dcfce7; color: #16a34a; }
    .ni-refund-no { background: #fee2e2; color: #dc2626; }
    .notif-body { flex: 1; min-width: 0; }
    .notif-body .nb-title { font-size: 12.5px; font-weight: 600; color: #1e293b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .notif-body .nb-sub   { font-size: 11px; color: #64748b; margin-top: 1px; }
    .notif-body .nb-time  { font-size: 10px; color: #94a3b8; margin-top: 2px; }
    .notif-footer {
        padding: 10px 16px; text-align: center;
        border-top: 1px solid #e2e8f0; background: #f8fafc;
    }
    .notif-footer a { font-size: 12px; font-weight: 600; color: #3b82f6; text-decoration: none; }
    .notif-empty { padding: 28px; text-align: center; color: #94a3b8; font-size: 13px; }

    /* ── WhatsApp button ── */
    .nav-icon-btn.wa { background: #dcfce7; color: #16a34a; }
    .nav-icon-btn.wa:hover { background: #bbf7d0; }

    @media (max-width: 992px) {
        .sidebar { width: 64px; }
        .logo-text, .cust-info, .verified-tick, .sidebar-nav a span,
        .wallet-pill, .promo-box, .wa-support .wa-text { display: none; }
        .cust-profile { justify-content: center; }
        .top-navbar { left: 64px; }
        .main-content { margin-left: 64px; }
        .navbar-welcome p, .navbar-search { display: none; }
    }
</style>

<!-- ══ SIDEBAR ══ -->
<div class="sidebar">
    <div class="sidebar-header">
        <div class="logo">
            <div class="logo-icon">
                <img src="image/logo.png" alt="AB FASHIONSTORE">
            </div>
            <div class="logo-text">
                <h3>AB FASHIONSTORE</h3>
                <p>Blessings Collection</p>
            </div>
        </div>
    </div>

    <div class="cust-profile">
        <div class="cust-avatar"><?php echo strtoupper(substr($cname, 0, 1)); ?></div>
        <div class="cust-info">
            <p class="cname"><?php echo htmlspecialchars($cname); ?></p>
            <span class="role-badge">Customer</span>
        </div>
        <i class="fas fa-check-circle verified-tick"></i>
    </div>

    <div class="sidebar-nav">
        <a href="customer_dashboard.php" class="<?php echo $active_page === 'dashboard' ? 'active' : ''; ?>">
            <i class="fas fa-home"></i><span>Dashboard</span>
        </a>
        <a href="my_orders.php" class="<?php echo $active_page === 'orders' ? 'active' : ''; ?>">
            <i class="fas fa-shopping-bag"></i><span>My Orders</span>
        </a>
        <a href="track_order.php" class="<?php echo $active_page === 'track' ? 'active' : ''; ?>">
            <i class="fas fa-truck"></i><span>Track Shipment</span>
        </a>
        <a href="my_payments.php" class="<?php echo $active_page === 'payments' ? 'active' : ''; ?>">
            <i class="fas fa-credit-card"></i><span>Payments</span>
        </a>
        <a href="#">
            <i class="fas fa-wallet"></i>
            <span>Wallet</span>
            <span class="wallet-pill">TZS <?php echo number_format($wallet_balance); ?></span>
        </a>
        <a href="request_refund.php" class="<?php echo $active_page === 'refund' ? 'active' : ''; ?>">
            <i class="fas fa-undo"></i><span>Refund Requests</span>
        </a>
        <a href="give_feedback.php" class="<?php echo $active_page === 'feedback' ? 'active' : ''; ?>">
            <i class="fas fa-comment-dots"></i><span>Feedback</span>
        </a>
        <a href="profile.php" class="<?php echo $active_page === 'profile' ? 'active' : ''; ?>">
            <i class="fas fa-user-circle"></i><span>Profile</span>
        </a>
        <hr class="nav-divider">
        <a href="customer_logout.php" style="color:#f87171;">
            <i class="fas fa-sign-out-alt"></i><span>Logout</span>
        </a>
    </div>

    <div class="promo-box">
        <h6>Ship from China to Tanzania</h6>
        <p>We buy, ship, and deliver right to your door.</p>
        <button class="btn-promo">Shop Now</button>
    </div>

    <div class="wa-support">
        <div class="wa-icon"><i class="fab fa-whatsapp"></i></div>
        <div class="wa-text">
            <p>Need Help?</p>
            <span>WhatsApp Support</span>
        </div>
    </div>
</div>

<!-- ══ TOP NAVBAR ══ -->
<div class="top-navbar">
    <div class="navbar-welcome">
        <h5><?php echo htmlspecialchars($pt['title']); ?></h5>
        <p><?php echo htmlspecialchars($pt['sub']); ?></p>
    </div>
    <div class="navbar-search">
        <i class="fas fa-search"></i>
        <input type="text" placeholder="Search...">
    </div>
    <div class="navbar-icons">

        <!-- Notification Bell -->
        <div class="dropdown">
            <button class="nav-icon-btn" type="button" data-bs-toggle="dropdown"
                    aria-expanded="false" title="Notifications" style="border:none;">
                <i class="fas fa-bell"></i>
                <?php if ($_notif_count > 0): ?>
                <span class="notif-badge"><?php echo $_notif_count > 9 ? '9+' : $_notif_count; ?></span>
                <?php endif; ?>
            </button>
            <div class="dropdown-menu notif-dropdown dropdown-menu-end">
                <div class="notif-header">
                    <h6><i class="fas fa-bell me-1"></i> Notifications</h6>
                    <?php if ($_notif_count > 0): ?>
                    <span class="badge bg-danger"><?php echo $_notif_count; ?> active</span>
                    <?php else: ?>
                    <span>All caught up</span>
                    <?php endif; ?>
                </div>
                <div class="notif-list">
                <?php
                // Order notifications
                $status_icon = [
                    'Processing'  => ['ni-process','fa-clock'],
                    'Shipped'     => ['ni-truck',  'fa-truck'],
                    'In Transit'  => ['ni-ship',   'fa-ship'],
                    'Arrived TZ'  => ['ni-pin',    'fa-map-marker-alt'],
                    'Delivered'   => ['ni-done',   'fa-check-circle'],
                    'Cancelled'   => ['ni-cancel', 'fa-times-circle'],
                ];
                $status_msg = [
                    'Processing' => 'Order received, purchasing now',
                    'Shipped'    => 'Your order is on its way',
                    'In Transit' => 'Shipment in transit to Tanzania',
                    'Arrived TZ' => 'Arrived in Tanzania — clearance pending',
                    'Delivered'  => 'Successfully delivered!',
                    'Cancelled'  => 'This order was cancelled',
                ];
                foreach ($_notif_orders as $no):
                    [$ic, $fa] = $status_icon[$no['status']] ?? ['ni-process','fa-box'];
                    $msg = $status_msg[$no['status']] ?? $no['status'];
                ?>
                <a href="track_order.php?id=<?php echo urlencode($no['order_id']); ?>" class="notif-item">
                    <div class="ni <?php echo $ic; ?>"><i class="fas <?php echo $fa; ?>"></i></div>
                    <div class="notif-body">
                        <div class="nb-title"><?php echo htmlspecialchars(mb_strimwidth($no['product_name'], 0, 32, '…')); ?></div>
                        <div class="nb-sub"><?php echo $msg; ?></div>
                        <div class="nb-time"><?php echo date('d M Y', strtotime($no['date_created'])); ?></div>
                    </div>
                </a>
                <?php endforeach; ?>

                <?php foreach ($_notif_refunds as $nr):
                    if ($nr['status'] === 'Approved'):
                        $ric = 'ni-refund-ok'; $rfa = 'fa-check-circle'; $rmsg = 'Refund approved!';
                    elseif ($nr['status'] === 'Rejected'):
                        $ric = 'ni-refund-no'; $rfa = 'fa-times-circle'; $rmsg = 'Refund was rejected';
                    else:
                        $ric = 'ni-refund'; $rfa = 'fa-undo'; $rmsg = 'Refund request pending review';
                    endif;
                ?>
                <a href="request_refund.php" class="notif-item">
                    <div class="ni <?php echo $ric; ?>"><i class="fas <?php echo $rfa; ?>"></i></div>
                    <div class="notif-body">
                        <div class="nb-title"><?php echo htmlspecialchars(mb_strimwidth($nr['product_name'] ?? 'Refund Request', 0, 32, '…')); ?></div>
                        <div class="nb-sub"><?php echo $rmsg; ?></div>
                        <div class="nb-time"><?php echo date('d M Y', strtotime($nr['date_created'])); ?></div>
                    </div>
                </a>
                <?php endforeach; ?>

                <?php if (empty($_notif_orders) && empty($_notif_refunds)): ?>
                <div class="notif-empty">
                    <i class="fas fa-bell-slash fa-2x mb-2 d-block text-muted"></i>
                    No notifications yet
                </div>
                <?php endif; ?>
                </div>
                <div class="notif-footer">
                    <a href="my_orders.php">View all orders &rarr;</a>
                </div>
            </div>
        </div>

        <!-- WhatsApp -->
        <a href="https://wa.me/255" class="nav-icon-btn wa" title="WhatsApp Support" target="_blank">
            <i class="fab fa-whatsapp"></i>
        </a>
        <a href="profile.php" class="navbar-profile">
            <div class="pa"><?php echo strtoupper(substr($cname, 0, 1)); ?></div>
            <div>
                <div class="pname"><?php echo htmlspecialchars($cname); ?></div>
                <div class="pphone"><?php echo htmlspecialchars($cphone); ?></div>
            </div>
        </a>
    </div>
</div>
