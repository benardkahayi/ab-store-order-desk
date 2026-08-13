<?php
session_start();
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['admin_logged_in']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: admin_login.php"); exit();
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    if ($id > 0) {
        $conn->prepare("DELETE FROM customers WHERE id = ?")->execute([$id]);
        header("Location: customers.php?deleted=1"); exit();
    }
}

$search = trim($_GET['search'] ?? '');
if ($search !== '') {
    $stmt = $conn->prepare("SELECT * FROM customers WHERE name LIKE ? OR phone LIKE ? OR email LIKE ? ORDER BY id DESC");
    $t = "%$search%";
    $stmt->execute([$t, $t, $t]);
} else {
    $stmt = $conn->query("SELECT * FROM customers ORDER BY id DESC");
}
$customers = $stmt->fetchAll();

$total_customers  = count($customers);
$new_this_month   = $conn->query("SELECT COUNT(*) FROM customers WHERE MONTH(date_created)=MONTH(CURDATE()) AND YEAR(date_created)=YEAR(CURDATE())")->fetchColumn();
$total_orders_cnt = $conn->query("SELECT COUNT(*) FROM orders")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="sw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customers - AB FASHIONSTORE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>
<?php $active_page = 'customers'; include 'admin_layout.php'; ?>

<div class="main-content">

    <div class="page-header">
        <div>
            <h1><i class="fas fa-users me-2 text-primary"></i>Customers</h1>
            <p>Manage all registered customers</p>
        </div>
        <a href="add_customer.php" class="btn btn-primary">
            <i class="fas fa-user-plus me-1"></i> Add Customer
        </a>
    </div>

    <!-- Stats -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted mb-1" style="font-size:12px;">Total Customers</p>
                        <h2 class="fw-bold mb-0"><?php echo number_format($total_customers); ?></h2>
                    </div>
                    <div class="stat-icon bg-primary bg-opacity-10 text-primary"><i class="fas fa-users"></i></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted mb-1" style="font-size:12px;">New This Month</p>
                        <h2 class="fw-bold mb-0 text-success"><?php echo number_format($new_this_month); ?></h2>
                    </div>
                    <div class="stat-icon bg-success bg-opacity-10 text-success"><i class="fas fa-calendar-plus"></i></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted mb-1" style="font-size:12px;">Total Orders</p>
                        <h2 class="fw-bold mb-0 text-warning"><?php echo number_format($total_orders_cnt); ?></h2>
                    </div>
                    <div class="stat-icon bg-warning bg-opacity-10 text-warning"><i class="fas fa-shopping-cart"></i></div>
                </div>
            </div>
        </div>
    </div>

    <?php if (isset($_GET['deleted'])): ?>
        <div class="alert alert-danger alert-dismissible fade show mb-3">
            <i class="fas fa-trash me-2"></i> Customer deleted.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Search -->
    <div class="table-card mb-3">
        <form method="GET" class="d-flex gap-2 flex-wrap">
            <div style="position:relative;flex:1;min-width:200px;">
                <i class="fas fa-search" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#94a3b8;"></i>
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
                       placeholder="Search by name, phone or email..."
                       style="width:100%;padding:9px 14px 9px 36px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:13px;outline:none;">
            </div>
            <button type="submit" class="btn btn-primary px-4">Search</button>
            <?php if ($search): ?>
                <a href="customers.php" class="btn btn-outline-secondary">Clear</a>
                <span class="align-self-center text-muted small">Found: <?php echo count($customers); ?></span>
            <?php endif; ?>
        </form>
    </div>

    <!-- Customers Table -->
    <div class="table-card">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <h5 class="fw-bold mb-0">All Customers</h5>
            <div style="position:relative;">
                <i class="fas fa-search" style="position:absolute;left:11px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:13px;"></i>
                <input type="text" id="tableSearch" class="tbl-search" placeholder="Filter table...">
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover" id="customersTable">
                <thead><tr>
                    <th>#</th>
                    <th>Customer</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>Address</th>
                    <th>Joined</th>
                    <th>Orders</th>
                    <th>Actions</th>
                </tr></thead>
                <tbody>
                <?php if ($customers): foreach ($customers as $c): ?>
                <tr>
                    <td style="color:#94a3b8;"><?php echo $c['id']; ?></td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="cust-avatar-sm"><?php echo strtoupper(substr($c['name'], 0, 1)); ?></div>
                            <div>
                                <strong style="font-size:13.5px;"><?php echo htmlspecialchars($c['name']); ?></strong>
                                <?php if (($c['role'] ?? '') === 'admin'): ?>
                                    <span class="badge bg-danger ms-1" style="font-size:10px;">Admin</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </td>
                    <td><?php echo htmlspecialchars($c['phone']); ?></td>
                    <td style="color:#64748b;"><?php echo htmlspecialchars($c['email'] ?? '—'); ?></td>
                    <td style="color:#64748b;font-size:12.5px;"><?php echo htmlspecialchars(substr($c['address'] ?? '', 0, 30)) . (strlen($c['address'] ?? '') > 30 ? '…' : ''); ?></td>
                    <td style="color:#94a3b8;font-size:12px;"><?php echo !empty($c['date_created']) ? date('d M Y', strtotime($c['date_created'])) : '—'; ?></td>
                    <td>
                        <a href="customer_orders.php?id=<?php echo $c['id']; ?>" class="btn btn-sm btn-outline-info" style="font-size:12px;">
                            <i class="fas fa-shopping-cart me-1"></i> View
                        </a>
                    </td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="edit_customer.php?id=<?php echo $c['id']; ?>" class="btn btn-sm btn-outline-warning" title="Edit"><i class="fas fa-edit"></i></a>
                            <button onclick="delCust(<?php echo $c['id']; ?>)" class="btn btn-sm btn-outline-danger" title="Delete" <?php echo (($c['role'] ?? '') === 'admin') ? 'disabled' : ''; ?>><i class="fas fa-trash"></i></button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; else: ?>
                <tr><td colspan="8" class="text-center py-5">
                    <i class="fas fa-users fa-4x text-muted mb-3 d-block"></i>
                    <h5 class="text-muted"><?php echo $search ? 'No customers match your search.' : 'No customers yet.'; ?></h5>
                    <a href="add_customer.php" class="btn btn-primary mt-2">Add First Customer</a>
                </td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function delCust(id) {
    if (confirm('Delete this customer? All their orders will also be removed.')) {
        window.location.href = 'customers.php?delete=' + id;
    }
}
document.getElementById('tableSearch').addEventListener('keyup', function() {
    const v = this.value.toLowerCase();
    document.querySelectorAll('#customersTable tbody tr').forEach(r => {
        r.style.display = r.textContent.toLowerCase().includes(v) ? '' : 'none';
    });
});
</script>
</body>
</html>
