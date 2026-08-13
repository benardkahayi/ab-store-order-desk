<?php
session_start();
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['role'] !== 'admin') {
    header("Location: admin_login.php"); exit;
}

if (isset($_GET['update_payment'])) {
    $id     = (int)$_GET['update_payment'];
    $status = $_GET['status'] ?? '';
    if ($id > 0 && in_array($status, ['pending','completed','failed','refunded'])) {
        $conn->prepare("UPDATE payments SET status = ? WHERE id = ?")->execute([$status, $id]);
        header("Location: payments.php?updated=1"); exit;
    }
}

$payments = $conn->query("
    SELECT p.*, o.product_name, o.total_price, o.customer_name, o.phone
    FROM payments p
    LEFT JOIN orders o ON p.order_id = o.order_id
    ORDER BY p.date_created DESC
")->fetchAll();

$total_payments    = count($payments);
$total_amount      = array_sum(array_column($payments, 'amount'));
$completed_amount  = 0;
$pending_amount    = 0;
$failed_amount     = 0;
foreach ($payments as $p) {
    if ($p['status'] === 'completed') $completed_amount += $p['amount'];
    elseif ($p['status'] === 'pending') $pending_amount  += $p['amount'];
    elseif ($p['status'] === 'failed')  $failed_amount   += $p['amount'];
}
?>
<!DOCTYPE html>
<html lang="sw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payments - AB FASHIONSTORE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>
<?php $active_page = 'payments'; include 'admin_layout.php'; ?>

<div class="main-content">

    <div class="page-header">
        <div>
            <h1><i class="fas fa-credit-card me-2 text-primary"></i>Payments</h1>
            <p>Manage all customer payments</p>
        </div>
        <a href="record_payment.php" class="btn btn-primary">
            <i class="fas fa-plus me-1"></i> Record Payment
        </a>
    </div>

    <?php if (isset($_GET['updated'])): ?>
        <div class="alert alert-success alert-dismissible fade show mb-3">
            <i class="fas fa-check-circle me-2"></i> Payment status updated.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Stats -->
    <div class="row g-3 mb-4">
        <div class="col-xl col-md-6">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted mb-1" style="font-size:12px;">Total Payments</p>
                        <h2 class="fw-bold mb-0"><?php echo number_format($total_payments); ?></h2>
                    </div>
                    <div class="stat-icon bg-primary bg-opacity-10 text-primary"><i class="fas fa-credit-card"></i></div>
                </div>
            </div>
        </div>
        <div class="col-xl col-md-6">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted mb-1" style="font-size:12px;">Completed</p>
                        <h2 class="fw-bold mb-0 text-success" style="font-size:20px;">TZS <?php echo number_format($completed_amount, 0); ?></h2>
                    </div>
                    <div class="stat-icon bg-success bg-opacity-10 text-success"><i class="fas fa-check-circle"></i></div>
                </div>
            </div>
        </div>
        <div class="col-xl col-md-6">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted mb-1" style="font-size:12px;">Pending</p>
                        <h2 class="fw-bold mb-0 text-warning" style="font-size:20px;">TZS <?php echo number_format($pending_amount, 0); ?></h2>
                    </div>
                    <div class="stat-icon bg-warning bg-opacity-10 text-warning"><i class="fas fa-clock"></i></div>
                </div>
            </div>
        </div>
        <div class="col-xl col-md-6">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted mb-1" style="font-size:12px;">Total Amount</p>
                        <h2 class="fw-bold mb-0" style="font-size:20px;">TZS <?php echo number_format($total_amount, 0); ?></h2>
                    </div>
                    <div class="stat-icon" style="background:#f3e8ff;color:#7c3aed;"><i class="fas fa-chart-line"></i></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Payments Table -->
    <div class="table-card">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <h5 class="fw-bold mb-0">All Payments</h5>
            <div style="position:relative;">
                <i class="fas fa-search" style="position:absolute;left:11px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:13px;"></i>
                <input type="text" id="paySearch" class="tbl-search" placeholder="Search payments...">
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover" id="paymentsTable">
                <thead><tr>
                    <th>#</th>
                    <th>Order ID</th>
                    <th>Customer</th>
                    <th>Phone</th>
                    <th>Product</th>
                    <th>Amount</th>
                    <th>Method</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Update</th>
                </tr></thead>
                <tbody>
                <?php if ($payments): $n = 1; foreach ($payments as $p): ?>
                <tr>
                    <td style="color:#94a3b8;"><?php echo $n++; ?></td>
                    <td><strong><?php echo htmlspecialchars($p['order_id']); ?></strong></td>
                    <td><?php echo htmlspecialchars($p['customer_name'] ?? 'N/A'); ?></td>
                    <td style="color:#64748b;"><?php echo htmlspecialchars($p['phone'] ?? 'N/A'); ?></td>
                    <td style="font-size:12.5px;max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?php echo htmlspecialchars($p['product_name'] ?? 'N/A'); ?></td>
                    <td><strong>TZS <?php echo number_format($p['amount'], 0); ?></strong></td>
                    <td style="font-size:12.5px;"><?php echo htmlspecialchars($p['payment_method'] ?? 'N/A'); ?></td>
                    <td>
                        <span class="sbadge sb-<?php echo $p['status']; ?>">
                            <?php echo ucfirst($p['status']); ?>
                        </span>
                    </td>
                    <td style="color:#94a3b8;font-size:12px;"><?php echo date('d M Y', strtotime($p['date_created'])); ?></td>
                    <td>
                        <select class="form-select form-select-sm" style="width:130px;font-size:12px;"
                                onchange="updatePay(<?php echo $p['id']; ?>, this.value)">
                            <option value="pending"   <?php if($p['status']==='pending')   echo 'selected'; ?>>Pending</option>
                            <option value="completed" <?php if($p['status']==='completed') echo 'selected'; ?>>Completed</option>
                            <option value="failed"    <?php if($p['status']==='failed')    echo 'selected'; ?>>Failed</option>
                            <option value="refunded"  <?php if($p['status']==='refunded')  echo 'selected'; ?>>Refunded</option>
                        </select>
                    </td>
                </tr>
                <?php endforeach; else: ?>
                <tr><td colspan="10" class="text-center py-5">
                    <i class="fas fa-credit-card fa-4x text-muted mb-3 d-block"></i>
                    <h5 class="text-muted">No Payments Found</h5>
                    <a href="record_payment.php" class="btn btn-primary mt-2">Record First Payment</a>
                </td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function updatePay(id, status) {
    if (confirm('Change payment status to "' + status + '"?')) {
        window.location.href = 'payments.php?update_payment=' + id + '&status=' + status;
    }
}
document.getElementById('paySearch').addEventListener('keyup', function() {
    const v = this.value.toLowerCase();
    document.querySelectorAll('#paymentsTable tbody tr').forEach(r => {
        r.style.display = r.textContent.toLowerCase().includes(v) ? '' : 'none';
    });
});
</script>
</body>
</html>
