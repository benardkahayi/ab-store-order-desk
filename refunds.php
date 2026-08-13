<?php
session_start();
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['role'] !== 'admin') {
    header("Location: admin_login.php"); exit();
}

if (isset($_GET['update_refund'])) {
    $id     = (int)$_GET['update_refund'];
    $status = $_GET['status'] ?? '';
    if ($id > 0 && in_array($status, ['Pending','Approved','Rejected'])) {
        $conn->prepare("UPDATE refunds SET status=? WHERE id=?")->execute([$status, $id]);
        if ($status === 'Approved') {
            $row = $conn->prepare("SELECT order_id FROM refunds WHERE id=?");
            $row->execute([$id]);
            $r = $row->fetch();
            if ($r) {
                $conn->prepare("UPDATE payments SET status='refunded' WHERE order_id=?")->execute([$r['order_id']]);
                $conn->prepare("UPDATE orders SET status='Cancelled' WHERE order_id=?")->execute([$r['order_id']]);
            }
        }
        header("Location: refunds.php?updated=1"); exit();
    }
}

$refunds = $conn->query("
    SELECT r.*, c.name AS customer_name, c.phone AS customer_phone,
           o.product_name, o.total_price, o.status AS order_status
    FROM refunds r
    LEFT JOIN customers c ON r.customer_id = c.id
    LEFT JOIN orders    o ON r.order_id    = o.order_id
    ORDER BY r.date_created DESC
")->fetchAll();

$total    = count($refunds);
$pending  = count(array_filter($refunds, fn($r) => $r['status'] === 'Pending'));
$approved = count(array_filter($refunds, fn($r) => $r['status'] === 'Approved'));
$rejected = count(array_filter($refunds, fn($r) => $r['status'] === 'Rejected'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Refund Requests - AB FASHIONSTORE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>
<?php $active_page = 'refunds'; include 'admin_layout.php'; ?>

<div class="main-content">

    <div class="page-header">
        <div>
            <h1><i class="fas fa-undo me-2 text-warning"></i>Refund Requests</h1>
            <p>Review and manage customer refund requests</p>
        </div>
        <span class="badge bg-warning text-dark px-3 py-2" style="font-size:13px;">
            <?php echo $pending; ?> Pending
        </span>
    </div>

    <?php if (isset($_GET['updated'])): ?>
        <div class="alert alert-success alert-dismissible fade show mb-3">
            <i class="fas fa-check-circle me-2"></i> Refund status updated successfully!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Stats -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div><p class="text-muted mb-1" style="font-size:12px;">Total Requests</p><h2 class="fw-bold mb-0"><?php echo $total; ?></h2></div>
                    <div class="stat-icon bg-secondary bg-opacity-10 text-secondary"><i class="fas fa-list"></i></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div><p class="text-muted mb-1" style="font-size:12px;">Pending</p><h2 class="fw-bold mb-0 text-warning"><?php echo $pending; ?></h2></div>
                    <div class="stat-icon bg-warning bg-opacity-10 text-warning"><i class="fas fa-clock"></i></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div><p class="text-muted mb-1" style="font-size:12px;">Approved</p><h2 class="fw-bold mb-0 text-success"><?php echo $approved; ?></h2></div>
                    <div class="stat-icon bg-success bg-opacity-10 text-success"><i class="fas fa-check-circle"></i></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div><p class="text-muted mb-1" style="font-size:12px;">Rejected</p><h2 class="fw-bold mb-0 text-danger"><?php echo $rejected; ?></h2></div>
                    <div class="stat-icon bg-danger bg-opacity-10 text-danger"><i class="fas fa-times-circle"></i></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="table-card">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <h5 class="fw-bold mb-0">All Refund Requests</h5>
            <div style="position:relative;">
                <i class="fas fa-search" style="position:absolute;left:11px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:13px;"></i>
                <input type="text" id="refSearch" class="tbl-search" placeholder="Search refunds...">
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover" id="refundsTable">
                <thead><tr>
                    <th>#</th><th>Order ID</th><th>Customer</th><th>Phone</th>
                    <th>Product</th><th>Amount</th><th>Reason</th><th>Status</th><th>Date</th><th>Actions</th>
                </tr></thead>
                <tbody>
                <?php if ($refunds): $n = 1; foreach ($refunds as $r): ?>
                <tr>
                    <td style="color:#94a3b8;"><?php echo $n++; ?></td>
                    <td><strong><?php echo htmlspecialchars($r['order_id']); ?></strong></td>
                    <td><?php echo htmlspecialchars($r['customer_name'] ?? 'N/A'); ?></td>
                    <td style="color:#64748b;font-size:12.5px;"><?php echo htmlspecialchars($r['customer_phone'] ?? 'N/A'); ?></td>
                    <td style="font-size:12.5px;max-width:120px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?php echo htmlspecialchars($r['product_name'] ?? 'N/A'); ?></td>
                    <td><strong>TZS <?php echo number_format($r['total_price'] ?? 0, 0); ?></strong></td>
                    <td style="font-size:12.5px;">
                        <?php $reason = $r['reason'] ?? ''; echo htmlspecialchars(strlen($reason) > 50 ? substr($reason, 0, 50) . '…' : $reason); ?>
                        <?php if (strlen($reason) > 50): ?>
                            <a href="#" data-bs-toggle="modal" data-bs-target="#reasonModal<?php echo $r['id']; ?>" class="small text-primary">more</a>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="sbadge sb-<?php echo strtolower($r['status']); ?>">
                            <?php echo $r['status']; ?>
                        </span>
                    </td>
                    <td style="color:#94a3b8;font-size:12px;"><?php echo date('d M Y', strtotime($r['date_created'])); ?></td>
                    <td>
                        <?php if ($r['status'] === 'Pending'): ?>
                        <div class="d-flex gap-1">
                            <a href="refunds.php?update_refund=<?php echo $r['id']; ?>&status=Approved"
                               class="btn btn-sm btn-success" style="font-size:11px;padding:3px 8px;"
                               onclick="return confirm('Approve? Order will be cancelled and payment marked as refunded.')">
                                <i class="fas fa-check"></i> Approve
                            </a>
                            <a href="refunds.php?update_refund=<?php echo $r['id']; ?>&status=Rejected"
                               class="btn btn-sm btn-danger" style="font-size:11px;padding:3px 8px;"
                               onclick="return confirm('Reject this refund request?')">
                                <i class="fas fa-times"></i> Reject
                            </a>
                        </div>
                        <?php else: ?>
                            <span class="text-muted small">Resolved</span>
                        <?php endif; ?>
                    </td>
                </tr>

                <?php if (strlen($reason) > 50): ?>
                <div class="modal fade" id="reasonModal<?php echo $r['id']; ?>" tabindex="-1">
                    <div class="modal-dialog"><div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Reason — <?php echo htmlspecialchars($r['order_id']); ?></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body"><p><?php echo nl2br(htmlspecialchars($r['reason'])); ?></p></div>
                    </div></div>
                </div>
                <?php endif; ?>

                <?php endforeach; else: ?>
                <tr><td colspan="10" class="text-center py-5">
                    <i class="fas fa-undo fa-4x text-muted mb-3 d-block"></i>
                    <h5 class="text-muted">No Refund Requests</h5>
                    <p class="text-muted">No customers have submitted refund requests yet.</p>
                </td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('refSearch').addEventListener('keyup', function() {
    const v = this.value.toLowerCase();
    document.querySelectorAll('#refundsTable tbody tr:not(.modal)').forEach(r => {
        r.style.display = r.textContent.toLowerCase().includes(v) ? '' : 'none';
    });
});
</script>
</body>
</html>
