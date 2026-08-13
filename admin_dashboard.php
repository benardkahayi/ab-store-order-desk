<?php
session_start();
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['role'] !== 'admin') {
    header("Location: admin_login.php");
    exit;
}

// Stats
$customers      = $conn->query("SELECT COUNT(*) FROM customers")->fetchColumn();
$orders         = $conn->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$total_products = $conn->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$total_payments = $conn->query("SELECT COUNT(*) FROM payments")->fetchColumn();
$revenue        = $conn->query("SELECT COALESCE(SUM(amount),0) FROM payments WHERE status='completed'")->fetchColumn();
$pending_payments_amt = $conn->query("SELECT COALESCE(SUM(amount),0) FROM payments WHERE status='pending'")->fetchColumn();

// Orders by status
$processing = $conn->query("SELECT COUNT(*) FROM orders WHERE status='Processing'")->fetchColumn();
$shipped    = $conn->query("SELECT COUNT(*) FROM orders WHERE status='Shipped'")->fetchColumn();
$in_transit = $conn->query("SELECT COUNT(*) FROM orders WHERE status='In Transit'")->fetchColumn();
$arrived    = $conn->query("SELECT COUNT(*) FROM orders WHERE status='Arrived TZ'")->fetchColumn();
$delivered  = $conn->query("SELECT COUNT(*) FROM orders WHERE status='Delivered'")->fetchColumn();
$cancelled  = $conn->query("SELECT COUNT(*) FROM orders WHERE status='Cancelled'")->fetchColumn();

// Monthly sales for chart
$monthly_sales = [];
for ($i = 1; $i <= 12; $i++) {
    $stmt = $conn->prepare("SELECT COALESCE(SUM(amount),0) FROM payments WHERE MONTH(date_created)=? AND YEAR(date_created)=YEAR(CURDATE()) AND status='completed'");
    $stmt->execute([$i]);
    $monthly_sales[] = (float)$stmt->fetchColumn();
}

// Recent orders
$recent_orders = $conn->query("
    SELECT o.*, c.name AS customer_name
    FROM orders o
    LEFT JOIN customers c ON o.customer_id = c.id
    ORDER BY o.date_created DESC
    LIMIT 6
")->fetchAll();

// Recent refund requests for WhatsApp panel
$recent_refunds = $conn->query("
    SELECT r.*, c.name AS customer_name
    FROM refunds r
    LEFT JOIN customers c ON r.customer_id = c.id
    ORDER BY r.date_created DESC
    LIMIT 4
")->fetchAll();

$admin_name = $_SESSION['admin_name'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="sw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - AB FASHIONSTORE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', 'Segoe UI', sans-serif; background: #f0f2f5; overflow-x: hidden; }

        /* ── Sidebar ── */
        .sidebar {
            position: fixed; left: 0; top: 0;
            width: 260px; height: 100%;
            background: linear-gradient(180deg, #0f1729 0%, #1a1a2e 100%);
            z-index: 1000; overflow-y: auto;
            box-shadow: 3px 0 15px rgba(0,0,0,0.15);
            scrollbar-width: none;
        }
        .sidebar::-webkit-scrollbar { display: none; }

        .sidebar-header {
            padding: 22px 18px 16px;
            border-bottom: 1px solid rgba(255,255,255,0.08);
        }
        .logo { display: flex; align-items: center; gap: 10px; }
        .logo-icon {
            width: 42px; height: 42px;
            background: white; border-radius: 10px; padding: 3px;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }
        .logo-text h3 { color: white; font-size: 14px; font-weight: 700; margin: 0; line-height: 1.2; }
        .logo-text p  { color: rgba(255,255,255,0.5); font-size: 10px; margin: 0; }

        /* Admin profile in sidebar */
        .admin-profile {
            padding: 14px 18px 16px;
            border-bottom: 1px solid rgba(255,255,255,0.08);
            display: flex; align-items: center; gap: 10px;
        }
        .admin-avatar {
            width: 38px; height: 38px; border-radius: 50%;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            display: flex; align-items: center; justify-content: center;
            color: white; font-weight: 700; font-size: 15px; flex-shrink: 0;
        }
        .admin-info .name { color: white; font-size: 13px; font-weight: 600; margin: 0; }
        .admin-info .role-badge {
            background: #10b981; color: white;
            font-size: 9px; font-weight: 700; padding: 1px 6px;
            border-radius: 20px; letter-spacing: 0.5px; text-transform: uppercase;
        }

        .sidebar-nav { padding: 10px 10px; }
        .nav-label {
            color: rgba(255,255,255,0.3); font-size: 9px; font-weight: 700;
            text-transform: uppercase; letter-spacing: 1.5px;
            padding: 10px 12px 4px; display: block;
        }
        .sidebar-nav a {
            color: rgba(255,255,255,0.65);
            padding: 10px 12px; text-decoration: none;
            display: flex; align-items: center; gap: 10px;
            border-radius: 8px; margin-bottom: 2px; transition: all 0.2s;
            font-size: 13.5px;
        }
        .sidebar-nav a:hover { background: rgba(255,255,255,0.08); color: white; }
        .sidebar-nav a.active { background: rgba(99,102,241,0.25); color: white; }
        .sidebar-nav a.active i { color: #818cf8; }
        .sidebar-nav a i { width: 18px; font-size: 14px; }
        .nav-badge {
            margin-left: auto; background: #ef4444; color: white;
            font-size: 10px; font-weight: 700; padding: 1px 6px; border-radius: 20px;
        }
        .nav-divider { border-color: rgba(255,255,255,0.08); margin: 8px 0; }

        /* ── Top Navbar ── */
        .top-navbar {
            position: fixed; top: 0; left: 260px; right: 0; height: 64px;
            background: white; z-index: 999;
            display: flex; align-items: center;
            padding: 0 28px;
            box-shadow: 0 1px 8px rgba(0,0,0,0.07);
            gap: 16px;
        }
        .navbar-welcome { flex: 1; }
        .navbar-welcome h5 { font-size: 16px; font-weight: 700; color: #1e293b; margin: 0; }
        .navbar-welcome p  { font-size: 12px; color: #94a3b8; margin: 0; }
        .navbar-search {
            flex: 0 0 220px;
            position: relative;
        }
        .navbar-search input {
            width: 100%; padding: 8px 14px 8px 36px;
            border: 1.5px solid #e2e8f0; border-radius: 20px;
            font-size: 13px; background: #f8fafc; outline: none;
        }
        .navbar-search i {
            position: absolute; left: 12px; top: 50%; transform: translateY(-50%);
            color: #94a3b8; font-size: 13px;
        }
        .navbar-icons { display: flex; align-items: center; gap: 6px; }
        .nav-icon-btn {
            width: 36px; height: 36px; border-radius: 10px;
            border: none; background: #f1f5f9; color: #64748b;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer; font-size: 15px; position: relative;
            text-decoration: none; transition: background 0.2s;
        }
        .nav-icon-btn:hover { background: #e2e8f0; color: #1e293b; }
        .nav-icon-btn .dot {
            position: absolute; top: 5px; right: 5px;
            width: 8px; height: 8px; background: #ef4444; border-radius: 50%;
            border: 1.5px solid white;
        }
        .nav-icon-btn.whatsapp { background: #dcfce7; color: #16a34a; }
        .navbar-profile {
            display: flex; align-items: center; gap: 8px;
            padding: 4px 10px 4px 4px;
            border: 1.5px solid #e2e8f0; border-radius: 24px; cursor: pointer;
        }
        .navbar-profile .avatar {
            width: 30px; height: 30px; border-radius: 50%;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            display: flex; align-items: center; justify-content: center;
            color: white; font-weight: 700; font-size: 12px;
        }
        .navbar-profile .pname { font-size: 12px; font-weight: 600; color: #1e293b; }
        .date-pill {
            display: flex; align-items: center; gap: 6px;
            padding: 5px 12px; border: 1.5px solid #e2e8f0; border-radius: 20px;
            font-size: 12px; color: #64748b; white-space: nowrap;
        }

        /* ── Main Content ── */
        .main-content { margin-left: 260px; padding: 84px 24px 30px; }

        /* ── Stat Cards ── */
        .stat-card {
            background: white; border-radius: 14px; padding: 18px 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            transition: transform 0.25s, box-shadow 0.25s;
        }
        .stat-card:hover { transform: translateY(-4px); box-shadow: 0 8px 20px rgba(0,0,0,0.09); }
        .stat-icon {
            width: 46px; height: 46px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center; font-size: 20px;
        }
        .trend-up   { color: #10b981; font-size: 11px; }
        .trend-down { color: #ef4444; font-size: 11px; }

        /* ── Chart Cards ── */
        .chart-card {
            background: white; border-radius: 14px; padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05); height: 100%;
        }
        .chart-card h5 { font-size: 15px; font-weight: 700; color: #1e293b; }

        /* ── Orders table ── */
        .table-card { background: white; border-radius: 14px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
        .table th { font-size: 12px; font-weight: 600; color: #64748b; background: #f8fafc; text-transform: uppercase; letter-spacing: 0.5px; }
        .table td { font-size: 13.5px; vertical-align: middle; }

        /* ── Status badges ── */
        .status-badge {
            display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600;
        }
        .badge-processing { background: #fef9c3; color: #854d0e; }
        .badge-shipped    { background: #e0f2fe; color: #0369a1; }
        .badge-intransit  { background: #ede9fe; color: #5b21b6; }
        .badge-arrived    { background: #fff7ed; color: #c2410c; }
        .badge-delivered  { background: #dcfce7; color: #15803d; }
        .badge-cancelled  { background: #fee2e2; color: #b91c1c; }

        /* ── Notification card ── */
        .notif-card { background: white; border-radius: 14px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); height: 100%; }
        .notif-item { display: flex; align-items: flex-start; gap: 10px; padding: 10px 0; border-bottom: 1px solid #f1f5f9; }
        .notif-item:last-child { border-bottom: none; }
        .notif-icon {
            width: 36px; height: 36px; border-radius: 9px; flex-shrink: 0;
            display: flex; align-items: center; justify-content: center; font-size: 15px;
        }
        .notif-icon.order  { background: #eff6ff; color: #3b82f6; }
        .notif-icon.refund { background: #fff7ed; color: #f97316; }
        .notif-icon.ship   { background: #dcfce7; color: #16a34a; }

        /* Donut legend */
        .donut-legend { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 14px; }
        .donut-legend-item { display: flex; align-items: center; gap: 5px; font-size: 12px; color: #475569; }
        .legend-dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }

        @media (max-width: 992px) {
            .sidebar { width: 70px; }
            .sidebar .logo-text, .admin-info, .sidebar-nav a span, .nav-label, .nav-badge { display: none; }
            .admin-profile { justify-content: center; }
            .top-navbar { left: 70px; }
            .main-content { margin-left: 70px; }
            .navbar-welcome p, .date-pill { display: none; }
        }
    </style>
</head>
<body>

<!-- ══════════ SIDEBAR ══════════ -->
<div class="sidebar">
    <div class="sidebar-header">
        <div class="logo">
            <div class="logo-icon">
                <img src="image/logo.png" alt="AB FASHIONSTORE" style="width:36px;height:36px;object-fit:cover;border-radius:7px;">
            </div>
            <div class="logo-text">
                <h3>AB FASHIONSTORE</h3>
                <p>Blessings Collection</p>
            </div>
        </div>
    </div>

    <div class="admin-profile">
        <div class="admin-avatar"><?php echo strtoupper(substr($admin_name, 0, 1)); ?></div>
        <div class="admin-info">
            <p class="name"><?php echo htmlspecialchars($admin_name); ?></p>
            <span class="role-badge">Administrator</span>
        </div>
    </div>

    <div class="sidebar-nav">
        <span class="nav-label">Main Menu</span>
        <a href="admin_dashboard.php" class="active">
            <i class="fas fa-tachometer-alt"></i><span>Dashboard</span>
        </a>
        <a href="orders.php">
            <i class="fas fa-shopping-cart"></i><span>Orders</span>
        </a>
        <a href="customers.php">
            <i class="fas fa-users"></i><span>Customers</span>
        </a>
        <a href="shipments.php">
            <i class="fas fa-truck"></i><span>Shipments</span>
        </a>
        <a href="products.php">
            <i class="fas fa-box"></i><span>Products</span>
        </a>
        <a href="payments.php">
            <i class="fas fa-credit-card"></i><span>Payments</span>
        </a>
        <a href="refunds.php">
            <i class="fas fa-undo"></i><span>Refund Requests</span>
        </a>
        <hr class="nav-divider">
        <span class="nav-label">Tools</span>
        <a href="add_customer.php">
            <i class="fas fa-user-plus"></i><span>Add Customer</span>
        </a>
        <a href="add_order.php">
            <i class="fas fa-plus-circle"></i><span>New Order</span>
        </a>
        <hr class="nav-divider">
        <a href="admin_logout.php" style="color:#f87171;">
            <i class="fas fa-sign-out-alt"></i><span>Logout</span>
        </a>
    </div>
</div>

<!-- ══════════ TOP NAVBAR ══════════ -->
<div class="top-navbar">
    <div class="navbar-welcome">
        <h5>Welcome back, <?php echo htmlspecialchars($admin_name); ?>! 👋</h5>
        <p>Here's what's happening with your business today.</p>
    </div>

    <div class="date-pill">
        <i class="fas fa-calendar-alt"></i>
        <?php echo date('d M Y'); ?>
    </div>

    <div class="navbar-search">
        <i class="fas fa-search"></i>
        <input type="text" placeholder="Search orders, customers...">
    </div>

    <div class="navbar-icons">
        <a href="refunds.php" class="nav-icon-btn" title="Notifications">
            <i class="fas fa-bell"></i>
            <?php if (count($recent_refunds) > 0): ?><span class="dot"></span><?php endif; ?>
        </a>
        <a href="#" class="nav-icon-btn whatsapp" title="WhatsApp">
            <i class="fab fa-whatsapp"></i>
        </a>
        <div class="navbar-profile" onclick="window.location='admin_logout.php'" title="Logout">
            <div class="avatar"><?php echo strtoupper(substr($admin_name, 0, 1)); ?></div>
            <span class="pname"><?php echo htmlspecialchars($admin_name); ?></span>
        </div>
    </div>
</div>

<!-- ══════════ MAIN CONTENT ══════════ -->
<div class="main-content">

    <!-- Top 4 Stat Cards -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted mb-1" style="font-size:12px;">Total Orders</p>
                        <h2 class="fw-bold mb-1" style="font-size:28px;"><?php echo number_format($orders); ?></h2>
                        <span class="trend-up"><i class="fas fa-arrow-up me-1"></i>+18.5% from last month</span>
                    </div>
                    <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted mb-1" style="font-size:12px;">Orders In Transit</p>
                        <h2 class="fw-bold mb-1" style="font-size:28px;"><?php echo number_format($shipped + $in_transit + $arrived); ?></h2>
                        <span class="trend-up"><i class="fas fa-arrow-up me-1"></i>+12.3% from last month</span>
                    </div>
                    <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                        <i class="fas fa-truck"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted mb-1" style="font-size:12px;">Delivered Orders</p>
                        <h2 class="fw-bold mb-1" style="font-size:28px;"><?php echo number_format($delivered); ?></h2>
                        <span class="trend-up"><i class="fas fa-arrow-up me-1"></i>+22.7% from last month</span>
                    </div>
                    <div class="stat-icon bg-success bg-opacity-10 text-success">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted mb-1" style="font-size:12px;">Total Revenue</p>
                        <h2 class="fw-bold mb-1" style="font-size:22px;">TZS <?php echo number_format($revenue, 0); ?></h2>
                        <span class="trend-up"><i class="fas fa-arrow-up me-1"></i>+25.4% from last month</span>
                    </div>
                    <div class="stat-icon" style="background:#fdf2f8;color:#a855f7;">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row g-3 mb-4">
        <!-- Sales Line Chart -->
        <div class="col-lg-7">
            <div class="chart-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">Sales Overview</h5>
                    <select class="form-select form-select-sm w-auto" style="font-size:12px;">
                        <option>This Year</option>
                        <option>Last Month</option>
                        <option>This Week</option>
                    </select>
                </div>
                <canvas id="salesChart" height="200"></canvas>
            </div>
        </div>

        <!-- Orders Donut Chart -->
        <div class="col-lg-5">
            <div class="chart-card">
                <h5 class="mb-1">Orders by Status</h5>
                <p class="text-muted mb-3" style="font-size:12px;">Total: <?php echo number_format($orders); ?> orders</p>
                <div style="position:relative;max-width:220px;margin:0 auto;">
                    <canvas id="statusChart" height="220"></canvas>
                    <div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);text-align:center;pointer-events:none;">
                        <div style="font-size:22px;font-weight:800;color:#1e293b;"><?php echo number_format($orders); ?></div>
                        <div style="font-size:11px;color:#94a3b8;">Orders</div>
                    </div>
                </div>
                <div class="donut-legend mt-3">
                    <div class="donut-legend-item"><span class="legend-dot" style="background:#f59e0b;"></span>Processing (<?php echo $processing; ?>)</div>
                    <div class="donut-legend-item"><span class="legend-dot" style="background:#3b82f6;"></span>Shipped (<?php echo $shipped; ?>)</div>
                    <div class="donut-legend-item"><span class="legend-dot" style="background:#8b5cf6;"></span>In Transit (<?php echo $in_transit; ?>)</div>
                    <div class="donut-legend-item"><span class="legend-dot" style="background:#f97316;"></span>Arrived TZ (<?php echo $arrived; ?>)</div>
                    <div class="donut-legend-item"><span class="legend-dot" style="background:#10b981;"></span>Delivered (<?php echo $delivered; ?>)</div>
                    <div class="donut-legend-item"><span class="legend-dot" style="background:#ef4444;"></span>Cancelled (<?php echo $cancelled; ?>)</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Orders + Notifications -->
    <div class="row g-3 mb-4">
        <!-- Recent Orders -->
        <div class="col-lg-7">
            <div class="table-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0" style="font-size:15px;font-weight:700;">Recent Orders</h5>
                    <a href="orders.php" class="btn btn-sm btn-primary px-3" style="font-size:12px;">View All</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_orders as $o): ?>
                            <?php
                                $s = $o['status'];
                                $cls = match($s) {
                                    'Processing'  => 'badge-processing',
                                    'Shipped'     => 'badge-shipped',
                                    'In Transit'  => 'badge-intransit',
                                    'Arrived TZ'  => 'badge-arrived',
                                    'Delivered'   => 'badge-delivered',
                                    'Cancelled'   => 'badge-cancelled',
                                    default       => 'badge-processing'
                                };
                            ?>
                            <tr>
                                <td><strong style="font-size:13px;"><?php echo htmlspecialchars($o['order_id']); ?></strong></td>
                                <td style="font-size:13px;"><?php echo htmlspecialchars($o['customer_name'] ?? 'N/A'); ?></td>
                                <td style="font-size:13px;">TZS <?php echo number_format($o['total_price'], 0); ?></td>
                                <td><span class="status-badge <?php echo $cls; ?>"><?php echo $s; ?></span></td>
                                <td style="font-size:12px;color:#94a3b8;"><?php echo date('d M Y', strtotime($o['date_created'])); ?></td>
                                <td>
                                    <a href="track_order.php?id=<?php echo urlencode($o['order_id']); ?>" class="btn btn-sm btn-outline-primary" style="font-size:11px;padding:2px 8px;">
                                        <i class="fas fa-truck"></i> Track
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- WhatsApp / Notifications -->
        <div class="col-lg-5">
            <div class="notif-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0" style="font-size:15px;font-weight:700;">
                        <i class="fab fa-whatsapp me-2 text-success"></i>Recent Activity
                    </h5>
                    <a href="refunds.php" class="text-primary" style="font-size:12px;">View All</a>
                </div>

                <?php if (count($recent_refunds) > 0): ?>
                    <?php foreach ($recent_refunds as $r): ?>
                    <div class="notif-item">
                        <div class="notif-icon refund"><i class="fas fa-undo"></i></div>
                        <div style="flex:1;min-width:0;">
                            <p class="mb-0" style="font-size:12.5px;font-weight:600;color:#1e293b;">
                                New refund request from <?php echo htmlspecialchars($r['customer_name'] ?? 'Customer'); ?>
                            </p>
                            <span style="font-size:11px;color:#94a3b8;"><?php echo date('d M Y', strtotime($r['date_created'])); ?></span>
                        </div>
                        <span class="status-badge badge-<?php echo strtolower($r['status']); ?>" style="font-size:10px;"><?php echo $r['status']; ?></span>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="notif-item">
                        <div class="notif-icon order"><i class="fas fa-shopping-cart"></i></div>
                        <div>
                            <p class="mb-0" style="font-size:12.5px;font-weight:600;">New order received</p>
                            <span style="font-size:11px;color:#94a3b8;">System started</span>
                        </div>
                    </div>
                    <div class="notif-item">
                        <div class="notif-icon ship"><i class="fas fa-truck"></i></div>
                        <div>
                            <p class="mb-0" style="font-size:12.5px;font-weight:600;">Shipment tracking active</p>
                            <span style="font-size:11px;color:#94a3b8;">System active</span>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Bottom 3 Stat Cards -->
    <div class="row g-3">
        <div class="col-md-4">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted mb-1" style="font-size:12px;">Total Customers</p>
                        <h2 class="fw-bold mb-1" style="font-size:28px;"><?php echo number_format($customers); ?></h2>
                        <span class="trend-up"><i class="fas fa-arrow-up me-1"></i>+14.2% from last month</span>
                    </div>
                    <div class="stat-icon bg-danger bg-opacity-10 text-danger">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted mb-1" style="font-size:12px;">Products Ordered</p>
                        <h2 class="fw-bold mb-1" style="font-size:28px;"><?php echo number_format($total_products); ?></h2>
                        <span class="trend-up"><i class="fas fa-arrow-up me-1"></i>+20.8% from last month</span>
                    </div>
                    <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                        <i class="fas fa-boxes"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted mb-1" style="font-size:12px;">Pending Payments</p>
                        <h2 class="fw-bold mb-1" style="font-size:22px;">TZS <?php echo number_format($pending_payments_amt, 0); ?></h2>
                        <span class="text-muted" style="font-size:11px;"><?php echo number_format($total_payments); ?> payments total</span>
                    </div>
                    <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div><!-- /main-content -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// ── Sales Line Chart
new Chart(document.getElementById('salesChart').getContext('2d'), {
    type: 'line',
    data: {
        labels: ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'],
        datasets: [{
            label: 'Sales (TZS)',
            data: <?php echo json_encode($monthly_sales); ?>,
            borderColor: '#6366f1',
            backgroundColor: 'rgba(99,102,241,0.08)',
            borderWidth: 2.5,
            fill: true,
            tension: 0.45,
            pointBackgroundColor: '#6366f1',
            pointRadius: 4,
            pointHoverRadius: 6
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            y: {
                beginAtZero: true,
                grid: { color: '#f1f5f9' },
                ticks: {
                    font: { size: 11 },
                    callback: v => v >= 1000000 ? 'TZS ' + (v/1000000).toFixed(1) + 'M' : 'TZS ' + v.toLocaleString()
                }
            },
            x: { grid: { display: false }, ticks: { font: { size: 11 } } }
        }
    }
});

// ── Status Donut Chart
new Chart(document.getElementById('statusChart').getContext('2d'), {
    type: 'doughnut',
    data: {
        labels: ['Processing','Shipped','In Transit','Arrived TZ','Delivered','Cancelled'],
        datasets: [{
            data: [<?php echo "$processing,$shipped,$in_transit,$arrived,$delivered,$cancelled"; ?>],
            backgroundColor: ['#f59e0b','#3b82f6','#8b5cf6','#f97316','#10b981','#ef4444'],
            borderWidth: 2,
            borderColor: '#fff',
            hoverOffset: 6
        }]
    },
    options: {
        cutout: '68%',
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: ctx => ' ' + ctx.label + ': ' + ctx.parsed + ' orders'
                }
            }
        }
    }
});
</script>
</body>
</html>
