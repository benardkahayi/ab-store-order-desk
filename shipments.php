<?php
session_start();
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['role'] !== 'admin') {
    header("Location: admin_login.php"); exit;
}

if (isset($_GET['update_status'])) {
    $id     = (int)$_GET['update_status'];
    $status = $_GET['status'] ?? '';
    $valid  = ['pending','processing','in_transit','arrived_tz','delivered','cancelled'];
    if ($id > 0 && in_array($status, $valid)) {
        $conn->prepare("UPDATE shipments SET status=?, updated_at=NOW() WHERE id=?")->execute([$status, $id]);
        header("Location: shipments.php?updated=1"); exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    $check = $conn->prepare("SELECT id FROM shipments WHERE order_id = ?");
    $check->execute([$_POST['order_id']]);
    if ($check->fetch()) { header("Location: shipments.php?error=exists"); exit; }

    $conn->prepare("INSERT INTO shipments (order_id,tracking_number,courier,shipping_cost,estimated_days,notes,status) VALUES (?,?,?,?,?,?,'pending')")
         ->execute([$_POST['order_id'], $_POST['tracking_number'], $_POST['courier'],
                    (float)$_POST['shipping_cost'], (int)$_POST['estimated_days'], $_POST['notes']]);
    header("Location: shipments.php?added=1"); exit;
}

$shipments = $conn->query("
    SELECT s.*, o.order_id AS order_number, o.product_name, o.customer_name, o.phone, o.total_price
    FROM shipments s
    LEFT JOIN orders o ON s.order_id = o.order_id
    ORDER BY s.created_at DESC
")->fetchAll();

$available_orders = $conn->query("
    SELECT o.order_id, o.customer_name, o.product_name, o.total_price
    FROM orders o LEFT JOIN shipments s ON o.order_id = s.order_id
    WHERE s.id IS NULL ORDER BY o.date_created DESC
")->fetchAll();

$total_shipments = count($shipments);
$in_transit  = count(array_filter($shipments, fn($s) => $s['status'] === 'in_transit'));
$arrived_tz  = count(array_filter($shipments, fn($s) => $s['status'] === 'arrived_tz'));
$delivered   = count(array_filter($shipments, fn($s) => $s['status'] === 'delivered'));
$pending     = count(array_filter($shipments, fn($s) => $s['status'] === 'pending'));

$label = ['pending'=>'Pending','processing'=>'Processing','in_transit'=>'In Transit',
          'arrived_tz'=>'Arrived TZ','delivered'=>'Delivered','cancelled'=>'Cancelled'];
$badge = ['pending'=>'sb-pending','processing'=>'sb-shipped','in_transit'=>'sb-intransit',
          'arrived_tz'=>'sb-arrivedtz','delivered'=>'sb-delivered','cancelled'=>'sb-cancelled'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shipments - AB FASHIONSTORE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>
<?php $active_page = 'shipments'; include 'admin_layout.php'; ?>

<div class="main-content">

    <div class="page-header">
        <div>
            <h1><i class="fas fa-truck me-2 text-primary"></i>Shipments</h1>
            <p>Track and manage all order shipments</p>
        </div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
            <i class="fas fa-plus me-1"></i> Create Shipment
        </button>
    </div>

    <!-- Alerts -->
    <?php if (isset($_GET['added'])): ?>
        <div class="alert alert-success alert-dismissible fade show mb-3"><i class="fas fa-check-circle me-2"></i> Shipment created successfully! <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>
    <?php if (isset($_GET['updated'])): ?>
        <div class="alert alert-success alert-dismissible fade show mb-3"><i class="fas fa-check-circle me-2"></i> Shipment status updated. <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>
    <?php if (isset($_GET['error']) && $_GET['error'] === 'exists'): ?>
        <div class="alert alert-danger alert-dismissible fade show mb-3"><i class="fas fa-exclamation-triangle me-2"></i> A shipment for this order already exists! <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>

    <!-- Stats -->
    <div class="row g-3 mb-4">
        <div class="col-xl col-md-6">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div><p class="text-muted mb-1" style="font-size:12px;">Total Shipments</p><h2 class="fw-bold mb-0"><?php echo number_format($total_shipments); ?></h2></div>
                    <div class="stat-icon bg-primary bg-opacity-10 text-primary"><i class="fas fa-box"></i></div>
                </div>
            </div>
        </div>
        <div class="col-xl col-md-6">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div><p class="text-muted mb-1" style="font-size:12px;">Pending</p><h2 class="fw-bold mb-0 text-warning"><?php echo number_format($pending); ?></h2></div>
                    <div class="stat-icon bg-warning bg-opacity-10 text-warning"><i class="fas fa-clock"></i></div>
                </div>
            </div>
        </div>
        <div class="col-xl col-md-6">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div><p class="text-muted mb-1" style="font-size:12px;">In Transit</p><h2 class="fw-bold mb-0 text-info"><?php echo number_format($in_transit); ?></h2></div>
                    <div class="stat-icon bg-info bg-opacity-10 text-info"><i class="fas fa-shipping-fast"></i></div>
                </div>
            </div>
        </div>
        <div class="col-xl col-md-6">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div><p class="text-muted mb-1" style="font-size:12px;">Arrived TZ</p><h2 class="fw-bold mb-0" style="color:#f97316;"><?php echo number_format($arrived_tz); ?></h2></div>
                    <div class="stat-icon" style="background:#fff7ed;color:#ea580c;"><i class="fas fa-map-marker-alt"></i></div>
                </div>
            </div>
        </div>
        <div class="col-xl col-md-6">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div><p class="text-muted mb-1" style="font-size:12px;">Delivered</p><h2 class="fw-bold mb-0 text-success"><?php echo number_format($delivered); ?></h2></div>
                    <div class="stat-icon bg-success bg-opacity-10 text-success"><i class="fas fa-check-circle"></i></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="table-card">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <h5 class="fw-bold mb-0">All Shipments</h5>
            <div style="position:relative;">
                <i class="fas fa-search" style="position:absolute;left:11px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:13px;"></i>
                <input type="text" id="shipSearch" class="tbl-search" placeholder="Search shipments...">
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover" id="shipmentsTable">
                <thead><tr>
                    <th>#</th>
                    <th>Order ID</th>
                    <th>Customer</th>
                    <th>Product</th>
                    <th>Tracking #</th>
                    <th>Courier</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Update</th>
                </tr></thead>
                <tbody>
                <?php if ($shipments): $n = 1; foreach ($shipments as $s): ?>
                <tr>
                    <td style="color:#94a3b8;"><?php echo $n++; ?></td>
                    <td><strong><?php echo htmlspecialchars($s['order_number']); ?></strong></td>
                    <td><?php echo htmlspecialchars($s['customer_name'] ?? 'N/A'); ?></td>
                    <td style="font-size:12.5px;max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?php echo htmlspecialchars($s['product_name'] ?? 'N/A'); ?></td>
                    <td><code style="font-size:12px;background:#f1f5f9;padding:2px 6px;border-radius:5px;"><?php echo htmlspecialchars($s['tracking_number']); ?></code></td>
                    <td style="font-size:12.5px;"><?php echo htmlspecialchars($s['courier']); ?></td>
                    <td>
                        <span class="sbadge <?php echo $badge[$s['status']] ?? 'sb-pending'; ?>">
                            <?php echo $label[$s['status']] ?? ucfirst($s['status']); ?>
                        </span>
                    </td>
                    <td style="color:#94a3b8;font-size:12px;"><?php echo date('d M Y', strtotime($s['created_at'])); ?></td>
                    <td>
                        <select class="form-select form-select-sm" style="width:130px;font-size:12px;"
                                onchange="updShip(<?php echo $s['id']; ?>, this.value)">
                            <?php foreach ($label as $val => $lbl): ?>
                            <option value="<?php echo $val; ?>" <?php if($s['status']===$val) echo 'selected'; ?>><?php echo $lbl; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <?php endforeach; else: ?>
                <tr><td colspan="9" class="text-center py-5">
                    <i class="fas fa-truck fa-4x text-muted mb-3 d-block"></i>
                    <h5 class="text-muted">No Shipments Found</h5>
                    <button class="btn btn-primary mt-2" data-bs-toggle="modal" data-bs-target="#addModal">Create First Shipment</button>
                </td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Shipment Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-truck me-2"></i>Create New Shipment</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST"><input type="hidden" name="action" value="add">
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-600">Select Order *</label>
                        <select name="order_id" class="form-select" required>
                            <option value="">-- Select Order --</option>
                            <?php foreach ($available_orders as $o): ?>
                            <option value="<?php echo htmlspecialchars($o['order_id']); ?>">
                                <?php echo htmlspecialchars($o['order_id'] . ' — ' . $o['customer_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-600">Tracking Number</label>
                        <input type="text" name="tracking_number" class="form-control" placeholder="e.g. CN100523456TR">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-600">Courier / Shipping Company</label>
                        <input type="text" name="courier" class="form-control" placeholder="e.g. DHL, COSCO">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-600">Shipping Cost (TZS)</label>
                        <input type="number" name="shipping_cost" class="form-control" value="0" min="0" step="0.01">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-600">Estimated Days</label>
                        <input type="number" name="estimated_days" class="form-control" value="30" min="1">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-600">Notes</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Additional notes..."></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Create Shipment</button>
            </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function updShip(id, status) {
    if (confirm('Change shipment status to "' + status + '"?')) {
        window.location.href = 'shipments.php?update_status=' + id + '&status=' + status;
    }
}
document.getElementById('shipSearch').addEventListener('keyup', function() {
    const v = this.value.toLowerCase();
    document.querySelectorAll('#shipmentsTable tbody tr').forEach(r => {
        r.style.display = r.textContent.toLowerCase().includes(v) ? '' : 'none';
    });
});
</script>
</body>
</html>
