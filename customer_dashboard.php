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
?>
<!DOCTYPE html>
<html lang="sw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo htmlspecialchars($cname); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
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

        /* Customer profile card */
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

        /* Nav */
        .sidebar-nav { padding: 6px 10px; flex: 1; }
        .nav-label {
            color: rgba(255,255,255,0.28); font-size: 9px; font-weight: 700;
            text-transform: uppercase; letter-spacing: 1.5px;
            padding: 8px 10px 3px; display: block;
        }
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

        /* Promo box */
        .promo-box {
            margin: 10px 10px 8px;
            border-radius: 14px;
            background: linear-gradient(135deg,#1e3a8a,#1e40af);
            padding: 14px 12px;
            position: relative; overflow: hidden;
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

        /* WhatsApp footer */
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
        .navbar-search { flex: 0 0 210px; position: relative; }
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
        }
        .navbar-profile .pa {
            width: 28px; height: 28px; border-radius: 50%;
            background: linear-gradient(135deg,#3b82f6,#6366f1);
            display: flex; align-items: center; justify-content: center;
            color: white; font-weight: 700; font-size: 11px;
        }
        .navbar-profile .pname { font-size: 12px; font-weight: 600; color: #1e293b; }
        .navbar-profile .pphone { font-size: 10px; color: #94a3b8; }

        /* ── Main Content ── */
        .main-content { margin-left: 250px; padding: 78px 22px 30px; }

        /* ── Stat Cards ── */
        .stat-card {
            background: white; border-radius: 13px; padding: 16px 18px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .stat-card:hover { transform: translateY(-4px); box-shadow: 0 8px 20px rgba(0,0,0,0.09); }
        .stat-icon {
            width: 42px; height: 42px; border-radius: 11px;
            display: flex; align-items: center; justify-content: center; font-size: 18px;
        }
        .stat-link { font-size: 11px; color: #3b82f6; text-decoration: none; display: block; margin-top: 6px; }
        .stat-link:hover { text-decoration: underline; }

        /* ── Tracking Card ── */
        .tracking-card {
            background: white; border-radius: 14px; padding: 22px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05); margin-bottom: 20px;
        }
        .tracking-card h5 { font-size: 15px; font-weight: 700; color: #0f172a; }

        /* Horizontal timeline */
        .timeline-wrap { position: relative; padding: 30px 10px 10px; }
        .timeline-track {
            position: absolute; top: 51px; left: calc(10px + 4.16%); right: calc(10px + 4.16%);
            height: 3px; background: #e2e8f0; border-radius: 4px;
        }
        .timeline-fill {
            height: 100%; background: linear-gradient(90deg,#22c55e,#3b82f6);
            border-radius: 4px; transition: width 0.6s;
        }
        .timeline-steps { display: flex; justify-content: space-between; position: relative; z-index: 1; }
        .t-step { display: flex; flex-direction: column; align-items: center; gap: 8px; flex: 1; }
        .t-icon {
            width: 44px; height: 44px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 17px; transition: all 0.3s;
            background: #e2e8f0; color: #94a3b8;
            border: 3px solid white; box-shadow: 0 0 0 2px #e2e8f0;
        }
        .t-icon.done {
            background: #22c55e; color: white;
            box-shadow: 0 0 0 3px rgba(34,197,94,0.25);
        }
        .t-icon.active {
            background: #3b82f6; color: white;
            box-shadow: 0 0 0 4px rgba(59,130,246,0.25);
            animation: pulse-ring 1.5s infinite;
        }
        @keyframes pulse-ring {
            0%  { box-shadow: 0 0 0 3px rgba(59,130,246,0.35); }
            70% { box-shadow: 0 0 0 8px rgba(59,130,246,0); }
            100%{ box-shadow: 0 0 0 3px rgba(59,130,246,0.35); }
        }
        .t-label { text-align: center; }
        .t-label .name { font-size: 11px; font-weight: 600; color: #334155; display: block; line-height: 1.2; }
        .t-label .date { font-size: 10px; color: #94a3b8; display: block; margin-top: 2px; }
        .t-label .name.done  { color: #15803d; }
        .t-label .name.active{ color: #1d4ed8; }

        /* Order detail card below timeline */
        .order-detail-card {
            background: #f8fafc; border-radius: 10px; padding: 14px 16px;
            display: flex; gap: 16px; align-items: flex-start; margin-top: 18px;
        }
        .order-detail-card img {
            width: 60px; height: 60px; object-fit: cover; border-radius: 8px;
        }
        .order-img-placeholder {
            width: 60px; height: 60px; border-radius: 8px;
            background: #e2e8f0; display: flex; align-items: center; justify-content: center;
            color: #94a3b8; font-size: 22px; flex-shrink: 0;
        }
        .od-meta { flex: 1; min-width: 0; }
        .od-meta strong { font-size: 14px; color: #0f172a; display: block; }
        .od-meta small { color: #64748b; font-size: 12px; }
        .od-cols { display: flex; gap: 28px; margin-top: 10px; flex-wrap: wrap; }
        .od-col label { font-size: 10px; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; display: block; }
        .od-col span  { font-size: 13px; font-weight: 600; color: #1e293b; }

        /* ── Table card ── */
        .table-card { background: white; border-radius: 13px; padding: 18px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
        .table th { font-size: 11px; font-weight: 600; color: #64748b; background: #f8fafc; text-transform: uppercase; letter-spacing: 0.4px; }
        .table td { font-size: 13px; vertical-align: middle; }

        /* Status badges */
        .sbadge {
            display: inline-block; padding: 2px 9px; border-radius: 20px; font-size: 11px; font-weight: 600;
        }
        .sb-processing { background: #fef9c3; color: #854d0e; }
        .sb-shipped    { background: #dbeafe; color: #1d4ed8; }
        .sb-transit    { background: #ede9fe; color: #5b21b6; }
        .sb-arrived    { background: #fff7ed; color: #c2410c; }
        .sb-delivered  { background: #dcfce7; color: #15803d; }
        .sb-cancelled  { background: #fee2e2; color: #b91c1c; }
        .sb-completed  { background: #dcfce7; color: #15803d; }
        .sb-pending    { background: #fef9c3; color: #854d0e; }
        .sb-failed     { background: #fee2e2; color: #b91c1c; }
        .sb-refunded   { background: #f3e8ff; color: #6b21a8; }

        /* ── Wallet card ── */
        .wallet-card {
            background: linear-gradient(135deg,#1e3a8a,#3b82f6);
            border-radius: 13px; padding: 20px; color: white;
        }
        .wallet-card .balance { font-size: 26px; font-weight: 800; }
        .wallet-card .label   { font-size: 11px; opacity: 0.7; margin-bottom: 2px; }
        .wallet-card .avail   { font-size: 11px; opacity: 0.6; margin-top: 2px; }
        .btn-topup {
            background: rgba(255,255,255,0.15); color: white;
            border: 1.5px solid rgba(255,255,255,0.3); border-radius: 8px;
            padding: 7px 14px; font-size: 13px; font-weight: 600;
            cursor: pointer; transition: background 0.2s;
        }
        .btn-topup:hover { background: rgba(255,255,255,0.25); }
        .btn-txhist {
            background: white; color: #1e3a8a;
            border: none; border-radius: 8px;
            padding: 7px 14px; font-size: 13px; font-weight: 600;
            cursor: pointer;
        }

        /* ── Notifications ── */
        .notif-card { background: white; border-radius: 13px; padding: 18px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
        .notif-item { display: flex; align-items: flex-start; gap: 10px; padding: 9px 0; border-bottom: 1px solid #f1f5f9; }
        .notif-item:last-child { border-bottom: none; }
        .ni-icon {
            width: 34px; height: 34px; border-radius: 9px; flex-shrink: 0;
            display: flex; align-items: center; justify-content: center; font-size: 14px;
        }
        .ni-icon.truck  { background: #dbeafe; color: #2563eb; }
        .ni-icon.pay    { background: #dcfce7; color: #16a34a; }
        .ni-icon.ship   { background: #ede9fe; color: #7c3aed; }
        .ni-icon.refund { background: #fff7ed; color: #ea580c; }

        /* ── Quick Actions ── */
        .qa-card {
            background: white; border-radius: 13px; padding: 16px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05); text-align: left;
            display: flex; align-items: center; gap: 12px;
            text-decoration: none; color: inherit; transition: transform 0.2s, box-shadow 0.2s;
        }
        .qa-card:hover { transform: translateY(-3px); box-shadow: 0 6px 16px rgba(0,0,0,0.1); color: inherit; }
        .qa-icon {
            width: 40px; height: 40px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center; font-size: 17px; flex-shrink: 0;
        }
        .qa-card .qa-title { font-size: 13px; font-weight: 600; color: #1e293b; margin: 0; }
        .qa-card .qa-sub   { font-size: 11px; color: #94a3b8; margin: 0; }

        @media (max-width: 992px) {
            .sidebar { width: 64px; }
            .logo-text, .cust-info, .verified-tick, .sidebar-nav a span,
            .nav-label, .wallet-pill, .promo-box, .wa-support .wa-text { display: none; }
            .cust-profile { justify-content: center; }
            .top-navbar { left: 64px; }
            .main-content { margin-left: 64px; }
            .navbar-welcome p, .navbar-search { display: none; }
        }
    </style>
</head>
<body>

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

    <!-- Customer profile -->
    <div class="cust-profile">
        <div class="cust-avatar"><?php echo strtoupper(substr($cname, 0, 1)); ?></div>
        <div class="cust-info">
            <p class="cname"><?php echo htmlspecialchars($cname); ?></p>
            <span class="role-badge">Customer</span>
        </div>
        <i class="fas fa-check-circle verified-tick" title="Verified"></i>
    </div>

    <div class="sidebar-nav">
        <a href="customer_dashboard.php" class="active">
            <i class="fas fa-home"></i><span>Dashboard</span>
        </a>
        <a href="my_orders.php">
            <i class="fas fa-shopping-bag"></i><span>My Orders</span>
        </a>
        <a href="track_order.php">
            <i class="fas fa-truck"></i><span>Track Shipment</span>
        </a>
        <a href="my_payments.php">
            <i class="fas fa-credit-card"></i><span>Payments</span>
        </a>
        <a href="#">
            <i class="fas fa-wallet"></i>
            <span>Wallet</span>
            <span class="wallet-pill">TZS <?php echo number_format($wallet_balance); ?></span>
        </a>
        <a href="request_refund.php">
            <i class="fas fa-undo"></i><span>Refund Requests</span>
        </a>
        <a href="give_feedback.php">
            <i class="fas fa-comment-dots"></i><span>Feedback</span>
        </a>
        <a href="profile.php">
            <i class="fas fa-user-circle"></i><span>Profile</span>
        </a>
        <a href="#">
            <i class="fas fa-headset"></i><span>Support</span>
        </a>
        <hr class="nav-divider">
        <a href="customer_logout.php" style="color:#f87171;">
            <i class="fas fa-sign-out-alt"></i><span>Logout</span>
        </a>
    </div>

    <!-- Promo box -->
    <div class="promo-box">
        <h6>Ship from China to Tanzania with ease</h6>
        <p>We buy, ship, and deliver right to your door.</p>
        <button class="btn-promo">Shop Now</button>
    </div>

    <!-- WhatsApp support -->
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
        <h5>Welcome back, <?php echo htmlspecialchars($cname); ?>! 👋</h5>
        <p>Here's what's happening with your orders today.</p>
    </div>

    <div class="navbar-search">
        <i class="fas fa-search"></i>
        <input type="text" placeholder="Search orders, tracking...">
    </div>

    <div class="navbar-icons">
        <a href="#" class="nav-icon-btn" title="Notifications">
            <i class="fas fa-bell"></i>
            <span class="dot"></span>
        </a>
        <a href="#" class="nav-icon-btn wa" title="WhatsApp">
            <i class="fab fa-whatsapp"></i>
        </a>
        <div class="navbar-profile">
            <div class="pa"><?php echo strtoupper(substr($cname, 0, 1)); ?></div>
            <div>
                <div class="pname"><?php echo htmlspecialchars($cname); ?></div>
                <div class="pphone"><?php echo htmlspecialchars($customer['phone']); ?></div>
            </div>
        </div>
    </div>
</div>

<!-- ══ MAIN CONTENT ══ -->
<div class="main-content">

    <!-- 5 Stat Cards -->
    <div class="row g-3 mb-4">
        <div class="col-xl col-md-4 col-6">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted mb-1" style="font-size:11px;">My Orders</p>
                        <h2 class="fw-bold mb-0" style="font-size:26px;color:#3b82f6;"><?php echo $total_orders; ?></h2>
                    </div>
                    <div class="stat-icon" style="background:#dbeafe;color:#2563eb;"><i class="fas fa-shopping-bag"></i></div>
                </div>
                <a href="my_orders.php" class="stat-link">View all orders →</a>
            </div>
        </div>
        <div class="col-xl col-md-4 col-6">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted mb-1" style="font-size:11px;">In Transit</p>
                        <h2 class="fw-bold mb-0" style="font-size:26px;color:#f59e0b;"><?php echo $in_transit; ?></h2>
                    </div>
                    <div class="stat-icon" style="background:#fef3c7;color:#d97706;"><i class="fas fa-truck"></i></div>
                </div>
                <a href="track_order.php" class="stat-link" style="color:#f59e0b;">View shipments →</a>
            </div>
        </div>
        <div class="col-xl col-md-4 col-6">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted mb-1" style="font-size:11px;">Delivered</p>
                        <h2 class="fw-bold mb-0" style="font-size:26px;color:#22c55e;"><?php echo $delivered_count; ?></h2>
                    </div>
                    <div class="stat-icon" style="background:#dcfce7;color:#16a34a;"><i class="fas fa-check-circle"></i></div>
                </div>
                <a href="my_orders.php" class="stat-link" style="color:#22c55e;">View delivered →</a>
            </div>
        </div>
        <div class="col-xl col-md-6 col-6">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted mb-1" style="font-size:11px;">Wallet Balance</p>
                        <h2 class="fw-bold mb-0" style="font-size:18px;color:#3b82f6;">TZS <?php echo number_format($wallet_balance); ?></h2>
                    </div>
                    <div class="stat-icon" style="background:#dbeafe;color:#2563eb;"><i class="fas fa-wallet"></i></div>
                </div>
                <a href="#" class="stat-link">Top up wallet →</a>
            </div>
        </div>
        <div class="col-xl col-md-6 col-6">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted mb-1" style="font-size:11px;">Total Spent</p>
                        <h2 class="fw-bold mb-0" style="font-size:18px;color:#ef4444;">TZS <?php echo number_format($total_spent, 0); ?></h2>
                    </div>
                    <div class="stat-icon" style="background:#fee2e2;color:#dc2626;"><i class="fas fa-receipt"></i></div>
                </div>
                <a href="my_payments.php" class="stat-link" style="color:#ef4444;">View summary →</a>
            </div>
        </div>
    </div>

    <!-- Current Order Tracking -->
    <?php if ($current_order): ?>
    <div class="tracking-card mb-4">
        <div class="d-flex justify-content-between align-items-center mb-1">
            <h5 class="mb-0">Current Order Tracking</h5>
            <div class="d-flex align-items-center gap-2">
                <span style="font-size:12px;color:#64748b;">Order ID:</span>
                <span style="font-size:13px;font-weight:700;color:#3b82f6;"><?php echo htmlspecialchars($current_order['order_id']); ?></span>
                <?php
                    $s = $current_order['status'];
                    $tclass = match(true) {
                        $s === 'Delivered'                    => 'sb-delivered',
                        in_array($s, ['In Transit','Shipped'])=> 'sb-transit',
                        $s === 'Arrived TZ'                   => 'sb-arrived',
                        $s === 'Processing'                   => 'sb-processing',
                        $s === 'Cancelled'                    => 'sb-cancelled',
                        default => 'sb-processing'
                    };
                ?>
                <span class="sbadge <?php echo $tclass; ?>"><?php echo $s; ?></span>
            </div>
        </div>

        <!-- Timeline -->
        <?php
            $steps = [
                ['key'=>'ordered',   'icon'=>'fa-user-check',    'label'=>'Ordered',            'date'=> date('d M Y', strtotime($current_order['date_created']))],
                ['key'=>'paid',      'icon'=>'fa-check',         'label'=>'Paid',               'date'=> $current_order['payment_date'] ? date('d M Y', strtotime($current_order['payment_date'])) : '—'],
                ['key'=>'purchased', 'icon'=>'fa-shopping-cart', 'label'=>'Purchased in China', 'date'=>'—'],
                ['key'=>'shipped',   'icon'=>'fa-plane',         'label'=>'Shipped',            'date'=>'—'],
                ['key'=>'arrived',   'icon'=>'fa-plane-arrival', 'label'=>'Arrived in Tanzania','date'=>'—'],
                ['key'=>'delivered', 'icon'=>'fa-home',          'label'=>'Delivered',          'date'=>'—'],
            ];
            // Find active (first not done)
            $active_key = null;
            foreach ($steps as $st) {
                if (!($tracking_status[$st['key']] ?? false)) { $active_key = $st['key']; break; }
            }
        ?>
        <div class="timeline-wrap">
            <div class="timeline-track">
                <div class="timeline-fill" style="width:<?php echo $progress_pct; ?>%;"></div>
            </div>
            <div class="timeline-steps">
                <?php foreach ($steps as $st):
                    $done   = $tracking_status[$st['key']] ?? false;
                    $active = (!$done && $st['key'] === $active_key);
                    $cls    = $done ? 'done' : ($active ? 'active' : '');
                ?>
                <div class="t-step">
                    <div class="t-icon <?php echo $cls; ?>">
                        <i class="fas <?php echo $st['icon']; ?>"></i>
                    </div>
                    <div class="t-label">
                        <span class="name <?php echo $cls; ?>"><?php echo $st['label']; ?></span>
                        <span class="date"><?php echo $st['date']; ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Order detail row -->
        <div class="order-detail-card">
            <div class="order-img-placeholder">
                <i class="fas fa-box"></i>
            </div>
            <div class="od-meta">
                <strong><?php echo htmlspecialchars($current_order['product_name']); ?></strong>
                <small>Qty: <?php echo $current_order['quantity'] ?? 1; ?></small>
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
            <div class="flex-shrink-0">
                <a href="track_order.php?id=<?php echo urlencode($current_order['order_id']); ?>"
                   class="btn btn-sm btn-primary" style="font-size:12px;">
                    <i class="fas fa-eye me-1"></i> View Tracking Details
                </a>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Bottom: Tables + Right Column -->
    <div class="row g-3">

        <!-- Left: Orders table + Payment history -->
        <div class="col-lg-7">
            <!-- Recent Orders -->
            <div class="table-card mb-3">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="mb-0 fw-700" style="font-size:14px;font-weight:700;">My Recent Orders</h6>
                    <a href="my_orders.php" class="btn btn-sm btn-primary px-3" style="font-size:12px;">View All Orders</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr>
                            <th>Order ID</th><th>Product</th><th>Status</th><th>Total</th><th>Date</th><th></th>
                        </tr></thead>
                        <tbody>
                        <?php if ($recent_orders): foreach ($recent_orders as $o):
                            $os = $o['status'];
                            $bc = match(true) {
                                $os==='Delivered'                     => 'sb-delivered',
                                in_array($os,['Shipped','In Transit'])=> 'sb-transit',
                                $os==='Arrived TZ'                    => 'sb-arrived',
                                $os==='Cancelled'                     => 'sb-cancelled',
                                default                               => 'sb-processing'
                            };
                        ?>
                        <tr>
                            <td><strong style="font-size:12.5px;"><?php echo htmlspecialchars($o['order_id']); ?></strong></td>
                            <td style="font-size:12.5px;max-width:120px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?php echo htmlspecialchars($o['product_name']); ?></td>
                            <td><span class="sbadge <?php echo $bc; ?>"><?php echo $os; ?></span></td>
                            <td style="font-size:12.5px;">TZS <?php echo number_format($o['total_price'],0); ?></td>
                            <td style="font-size:11px;color:#94a3b8;"><?php echo date('d M Y', strtotime($o['date_created'])); ?></td>
                            <td>
                                <a href="track_order.php?id=<?php echo urlencode($o['order_id']); ?>" class="btn btn-sm btn-outline-primary" style="font-size:11px;padding:2px 8px;">View</a>
                            </td>
                        </tr>
                        <?php endforeach; else: ?>
                        <tr><td colspan="6" class="text-center py-4 text-muted">No orders yet</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Payment History -->
            <div class="table-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="mb-0" style="font-size:14px;font-weight:700;">Payment History</h6>
                    <a href="my_payments.php" class="btn btn-sm btn-primary px-3" style="font-size:12px;">View All Payments</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr>
                            <th>Trans ID</th><th>Order ID</th><th>Amount</th><th>Date</th><th>Status</th>
                        </tr></thead>
                        <tbody>
                        <?php if ($payments): foreach ($payments as $p):
                            $ps = $p['status'] ?? 'pending';
                            $pc = 'sb-' . $ps;
                        ?>
                        <tr>
                            <td style="font-size:12.5px;">#PAY-<?php echo str_pad($p['id'], 4, '0', STR_PAD_LEFT); ?></td>
                            <td style="font-size:12.5px;"><?php echo htmlspecialchars($p['order_id']); ?></td>
                            <td style="font-size:12.5px;">TZS <?php echo number_format($p['amount'],0); ?></td>
                            <td style="font-size:11px;color:#94a3b8;"><?php echo date('d M Y', strtotime($p['date_created'])); ?></td>
                            <td><span class="sbadge <?php echo $pc; ?>"><?php echo ucfirst($ps); ?></span></td>
                        </tr>
                        <?php endforeach; else: ?>
                        <tr><td colspan="5" class="text-center py-4 text-muted">No payments yet</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Right: Notifications + Wallet + Quick Actions -->
        <div class="col-lg-5 d-flex flex-column gap-3">

            <!-- Recent Notifications -->
            <div class="notif-card">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="mb-0" style="font-size:14px;font-weight:700;">Recent Notifications</h6>
                    <a href="#" style="font-size:12px;color:#3b82f6;">View All</a>
                </div>
                <?php if ($current_order): ?>
                <div class="notif-item">
                    <div class="ni-icon truck"><i class="fas fa-truck"></i></div>
                    <div style="flex:1;">
                        <p class="mb-0" style="font-size:12.5px;font-weight:600;">Your order <?php echo htmlspecialchars($current_order['order_id']); ?> is <?php echo strtolower($current_order['status']); ?></p>
                        <span style="font-size:11px;color:#94a3b8;"><?php echo date('d M Y', strtotime($current_order['date_created'])); ?></span>
                    </div>
                </div>
                <?php if ($tracking_status['paid'] ?? false): ?>
                <div class="notif-item">
                    <div class="ni-icon pay"><i class="fas fa-credit-card"></i></div>
                    <div style="flex:1;">
                        <p class="mb-0" style="font-size:12.5px;font-weight:600;">Payment received for <?php echo htmlspecialchars($current_order['order_id']); ?></p>
                        <span style="font-size:11px;color:#94a3b8;"><?php echo $current_order['payment_date'] ? date('d M Y', strtotime($current_order['payment_date'])) : 'Recently'; ?></span>
                    </div>
                </div>
                <?php endif; ?>
                <?php else: ?>
                <div class="notif-item">
                    <div class="ni-icon ship"><i class="fas fa-box"></i></div>
                    <div>
                        <p class="mb-0" style="font-size:12.5px;font-weight:600;">No new notifications</p>
                        <span style="font-size:11px;color:#94a3b8;">Place an order to get started</span>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Wallet Balance -->
            <div class="wallet-card">
                <p class="label">Wallet Balance</p>
                <div class="balance">TZS <?php echo number_format($wallet_balance); ?></div>
                <p class="avail">Available Balance</p>
                <div class="d-flex gap-2 mt-3">
                    <button class="btn-topup" onclick="alert('Top up feature coming soon!')">
                        <i class="fas fa-plus me-1"></i> Top Up Wallet
                    </button>
                    <button class="btn-txhist" onclick="window.location='my_payments.php'">
                        Transaction History
                    </button>
                </div>
            </div>

            <!-- Quick Actions -->
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

        </div><!-- /right col -->
    </div><!-- /row -->

</div><!-- /main-content -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
