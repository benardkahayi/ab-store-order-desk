<?php
/**
 * Shared admin layout — sidebar + top navbar.
 * Set $active_page before including this file.
 * $active_page values: dashboard | orders | customers | products | payments | shipments | refunds | add_customer | add_order
 */
$active_page = $active_page ?? '';
$admin_name  = $_SESSION['admin_name'] ?? 'Admin';
$admin_init  = strtoupper(substr($admin_name, 0, 1));
?>
<style>
/* ── Reset ── */
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: 'Inter','Segoe UI',sans-serif; background: #f0f2f5; overflow-x: hidden; }

/* ── Sidebar ── */
.sidebar {
    position: fixed; left: 0; top: 0; width: 260px; height: 100%;
    background: linear-gradient(180deg,#0f1729 0%,#1a1a2e 100%);
    z-index: 1000; overflow-y: auto;
    box-shadow: 3px 0 15px rgba(0,0,0,0.15);
    scrollbar-width: none;
}
.sidebar::-webkit-scrollbar { display: none; }

.sidebar-header { padding: 22px 18px 16px; border-bottom: 1px solid rgba(255,255,255,0.08); }
.logo { display: flex; align-items: center; gap: 10px; }
.logo-icon {
    width: 42px; height: 42px; background: white; border-radius: 10px; padding: 3px;
    display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.logo-icon img { width: 36px; height: 36px; object-fit: cover; border-radius: 7px; }
.logo-text h3 { color: white; font-size: 14px; font-weight: 700; margin: 0; line-height: 1.2; }
.logo-text p  { color: rgba(255,255,255,0.5); font-size: 10px; margin: 0; }

.admin-profile {
    padding: 14px 18px 16px; border-bottom: 1px solid rgba(255,255,255,0.08);
    display: flex; align-items: center; gap: 10px;
}
.admin-avatar {
    width: 38px; height: 38px; border-radius: 50%;
    background: linear-gradient(135deg,#6366f1,#8b5cf6);
    display: flex; align-items: center; justify-content: center;
    color: white; font-weight: 700; font-size: 15px; flex-shrink: 0;
}
.admin-info .aname { color: white; font-size: 13px; font-weight: 600; margin: 0; }
.admin-info .role-badge {
    background: #10b981; color: white; font-size: 9px; font-weight: 700;
    padding: 1px 6px; border-radius: 20px; text-transform: uppercase;
}

.sidebar-nav { padding: 10px 10px; }
.nav-label {
    color: rgba(255,255,255,0.28); font-size: 9px; font-weight: 700;
    text-transform: uppercase; letter-spacing: 1.5px; padding: 8px 12px 3px; display: block;
}
.sidebar-nav a {
    color: rgba(255,255,255,0.65); padding: 10px 12px; text-decoration: none;
    display: flex; align-items: center; gap: 10px;
    border-radius: 8px; margin-bottom: 2px; transition: all 0.2s; font-size: 13.5px;
}
.sidebar-nav a:hover { background: rgba(255,255,255,0.08); color: white; }
.sidebar-nav a.active { background: rgba(99,102,241,0.25); color: white; }
.sidebar-nav a.active i { color: #818cf8; }
.sidebar-nav a i { width: 18px; font-size: 14px; }
.nav-divider { border-color: rgba(255,255,255,0.08); margin: 8px 0; }

/* ── Top Navbar ── */
.top-navbar {
    position: fixed; top: 0; left: 260px; right: 0; height: 64px;
    background: white; z-index: 999;
    display: flex; align-items: center; padding: 0 28px; gap: 16px;
    box-shadow: 0 1px 8px rgba(0,0,0,0.07);
}
.nb-title { flex: 1; }
.nb-title h5 { font-size: 16px; font-weight: 700; color: #1e293b; margin: 0; }
.nb-title p  { font-size: 12px; color: #94a3b8; margin: 0; }
.nb-search { flex: 0 0 220px; position: relative; }
.nb-search input {
    width: 100%; padding: 8px 14px 8px 36px;
    border: 1.5px solid #e2e8f0; border-radius: 20px;
    font-size: 13px; background: #f8fafc; outline: none;
}
.nb-search i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 13px; }
.nb-icons { display: flex; align-items: center; gap: 6px; }
.nb-btn {
    width: 36px; height: 36px; border-radius: 10px; border: none;
    background: #f1f5f9; color: #64748b;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer; font-size: 15px; text-decoration: none; transition: background 0.2s;
}
.nb-btn:hover { background: #e2e8f0; color: #1e293b; }
.nb-btn.wa { background: #dcfce7; color: #16a34a; }
.nb-profile {
    display: flex; align-items: center; gap: 8px;
    padding: 4px 12px 4px 4px;
    border: 1.5px solid #e2e8f0; border-radius: 24px; cursor: pointer;
}
.nb-profile .pa {
    width: 30px; height: 30px; border-radius: 50%;
    background: linear-gradient(135deg,#6366f1,#8b5cf6);
    display: flex; align-items: center; justify-content: center;
    color: white; font-weight: 700; font-size: 12px;
}
.nb-profile .pn { font-size: 12px; font-weight: 600; color: #1e293b; }

/* ── Main Content ── */
.main-content { margin-left: 260px; padding: 84px 24px 30px; }

/* ── Shared Page Components ── */
.page-header {
    background: white; border-radius: 14px; padding: 22px 26px; margin-bottom: 22px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    display: flex; justify-content: space-between; align-items: center; gap: 12px;
}
.page-header h1 { font-size: 22px; font-weight: 800; color: #0f172a; margin: 0; }
.page-header p  { font-size: 13px; color: #64748b; margin: 4px 0 0; }

.stat-card {
    background: white; border-radius: 13px; padding: 18px 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05); transition: transform 0.2s, box-shadow 0.2s;
}
.stat-card:hover { transform: translateY(-4px); box-shadow: 0 8px 20px rgba(0,0,0,0.09); }
.stat-icon {
    width: 46px; height: 46px; border-radius: 12px;
    display: flex; align-items: center; justify-content: center; font-size: 20px;
}

.table-card {
    background: white; border-radius: 14px; padding: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}
.table th {
    font-size: 11px; font-weight: 700; color: #64748b;
    background: #f8fafc; text-transform: uppercase; letter-spacing: 0.5px; padding: 12px 14px;
}
.table td { font-size: 13.5px; vertical-align: middle; padding: 12px 14px; }
.table-hover tbody tr:hover { background: #f8fafc; }

/* Status badges */
.sbadge { display: inline-block; padding: 3px 11px; border-radius: 20px; font-size: 11px; font-weight: 600; }
.sb-processing  { background: #fef9c3; color: #854d0e; }
.sb-shipped     { background: #dbeafe; color: #1d4ed8; }
.sb-intransit   { background: #ede9fe; color: #5b21b6; }
.sb-arrivedtz   { background: #fff7ed; color: #c2410c; }
.sb-delivered   { background: #dcfce7; color: #15803d; }
.sb-cancelled   { background: #fee2e2; color: #b91c1c; }
.sb-pending     { background: #fef9c3; color: #854d0e; }
.sb-completed   { background: #dcfce7; color: #15803d; }
.sb-failed      { background: #fee2e2; color: #b91c1c; }
.sb-refunded    { background: #f3e8ff; color: #6b21a8; }
.sb-active      { background: #dcfce7; color: #15803d; }
.sb-inactive    { background: #fee2e2; color: #b91c1c; }

/* Avatar circle */
.cust-avatar-sm {
    width: 36px; height: 36px; border-radius: 50%;
    background: linear-gradient(135deg,#6366f1,#8b5cf6);
    display: inline-flex; align-items: center; justify-content: center;
    color: white; font-weight: 700; font-size: 14px; flex-shrink: 0;
}

/* Search bar in table card */
.tbl-search {
    padding: 8px 14px 8px 36px; border: 1.5px solid #e2e8f0;
    border-radius: 10px; font-size: 13px; outline: none; width: 220px;
}
.tbl-search:focus { border-color: #6366f1; }

@media (max-width: 992px) {
    .sidebar { width: 70px; }
    .logo-text, .admin-info, .sidebar-nav a span, .nav-label { display: none; }
    .admin-profile { justify-content: center; }
    .top-navbar { left: 70px; }
    .main-content { margin-left: 70px; }
    .nb-title p, .nb-search { display: none; }
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

    <div class="admin-profile">
        <div class="admin-avatar"><?php echo $admin_init; ?></div>
        <div class="admin-info">
            <p class="aname"><?php echo htmlspecialchars($admin_name); ?></p>
            <span class="role-badge">Administrator</span>
        </div>
    </div>

    <div class="sidebar-nav">
        <span class="nav-label">Main Menu</span>
        <a href="admin_dashboard.php" <?php if($active_page==='dashboard') echo 'class="active"'; ?>>
            <i class="fas fa-tachometer-alt"></i><span>Dashboard</span>
        </a>
        <a href="orders.php" <?php if($active_page==='orders') echo 'class="active"'; ?>>
            <i class="fas fa-shopping-cart"></i><span>Orders</span>
        </a>
        <a href="customers.php" <?php if($active_page==='customers') echo 'class="active"'; ?>>
            <i class="fas fa-users"></i><span>Customers</span>
        </a>
        <a href="shipments.php" <?php if($active_page==='shipments') echo 'class="active"'; ?>>
            <i class="fas fa-truck"></i><span>Shipments</span>
        </a>
        <a href="products.php" <?php if($active_page==='products') echo 'class="active"'; ?>>
            <i class="fas fa-box"></i><span>Products</span>
        </a>
        <a href="payments.php" <?php if($active_page==='payments') echo 'class="active"'; ?>>
            <i class="fas fa-credit-card"></i><span>Payments</span>
        </a>
        <a href="refunds.php" <?php if($active_page==='refunds') echo 'class="active"'; ?>>
            <i class="fas fa-undo"></i><span>Refund Requests</span>
        </a>
        <hr class="nav-divider">
        <span class="nav-label">Tools</span>
        <a href="add_customer.php" <?php if($active_page==='add_customer') echo 'class="active"'; ?>>
            <i class="fas fa-user-plus"></i><span>Add Customer</span>
        </a>
        <a href="add_order.php" <?php if($active_page==='add_order') echo 'class="active"'; ?>>
            <i class="fas fa-plus-circle"></i><span>New Order</span>
        </a>
        <hr class="nav-divider">
        <a href="admin_logout.php" style="color:#f87171;">
            <i class="fas fa-sign-out-alt"></i><span>Logout</span>
        </a>
    </div>
</div>

<!-- ══ TOP NAVBAR ══ -->
<div class="top-navbar">
    <div class="nb-title">
        <h5><?php
            $titles = [
                'dashboard'    => 'Admin Dashboard',
                'orders'       => 'Orders Management',
                'customers'    => 'Customers Management',
                'shipments'    => 'Shipments',
                'products'     => 'Products Management',
                'payments'     => 'Payments Management',
                'refunds'      => 'Refund Requests',
                'add_customer' => 'Add New Customer',
                'add_order'    => 'Create New Order',
            ];
            echo $titles[$active_page] ?? 'Admin Panel';
        ?></h5>
        <p>AB FASHIONSTORE — Blessings Collection</p>
    </div>

    <div class="nb-search">
        <i class="fas fa-search"></i>
        <input type="text" placeholder="Search orders, customers...">
    </div>

    <div class="nb-icons">
        <a href="refunds.php" class="nb-btn" title="Notifications">
            <i class="fas fa-bell"></i>
        </a>
        <a href="#" class="nb-btn wa" title="WhatsApp">
            <i class="fab fa-whatsapp"></i>
        </a>
        <div class="nb-profile" onclick="window.location='admin_logout.php'" title="Logout">
            <div class="pa"><?php echo $admin_init; ?></div>
            <span class="pn"><?php echo htmlspecialchars($admin_name); ?></span>
        </div>
    </div>
</div>
