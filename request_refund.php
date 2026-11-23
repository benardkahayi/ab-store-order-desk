<?php
session_start();
require 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'customer') {
    header("Location: customer_login.php"); exit;
}

$customer_id = $_SESSION['customer_id'];
$order_id    = $_GET['order_id'] ?? '';

$stmt = $conn->prepare("SELECT * FROM customers WHERE id = ?");
$stmt->execute([$customer_id]);
$customer = $stmt->fetch();

$order = null;
if (!empty($order_id)) {
    $os = $conn->prepare("
        SELECT o.*, p.status as payment_status
        FROM orders o
        LEFT JOIN payments p ON o.order_id = p.order_id
        WHERE o.order_id = ? AND o.customer_id = ?
    ");
    $os->execute([$order_id, $customer_id]);
    $order = $os->fetch();
}

$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id_refund = trim($_POST['order_id']);
    $reason          = trim($_POST['reason']);

    if (!empty($order_id_refund) && !empty($reason)) {
        $check = $conn->prepare("SELECT id FROM orders WHERE order_id = ? AND customer_id = ? AND status = 'Delivered'");
        $check->execute([$order_id_refund, $customer_id]);

        if ($check->fetch()) {
            $dup = $conn->prepare("SELECT id FROM refunds WHERE order_id = ? AND customer_id = ?");
            $dup->execute([$order_id_refund, $customer_id]);

            if (!$dup->fetch()) {
                $ins = $conn->prepare("INSERT INTO refunds (order_id, customer_id, reason) VALUES (?, ?, ?)");
                if ($ins->execute([$order_id_refund, $customer_id, $reason])) {
                    $success  = "Refund request submitted! We will review it within 2–5 business days.";
                    $_POST    = [];
                    $order_id = '';
                    $order    = null;
                } else {
                    $error = "An error occurred while submitting. Please try again.";
                }
            } else {
                $error = "A refund request for this order has already been submitted.";
            }
        } else {
            $error = "Order not found or not eligible. You can only request a refund for Delivered orders.";
        }
    } else {
        $error = "Please enter the Order ID and a reason for your refund request.";
    }
}
?>
<!DOCTYPE html>
<html lang="sw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Request Refund - AB FASHIONSTORE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .req-item { padding: 5px 0 5px 22px; position: relative; font-size: 13.5px; }
        .req-item::before { content: "✓"; position: absolute; left: 0; color: #16a34a; font-weight: bold; }
        .order-info-box { background: #f0fdf4; border-left: 4px solid #16a34a; border-radius: 10px; padding: 18px; }
    </style>
</head>
<body>
<?php $active_page = 'refund'; include 'customer_layout.php'; ?>

<div class="main-content">

    <div class="page-header">
        <div>
            <h1><i class="fas fa-undo me-2 text-danger"></i>Request Refund</h1>
            <p>Submit a refund request for a delivered order</p>
        </div>
        <a href="my_orders.php" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i> My Orders
        </a>
    </div>

    <?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="form-card">

                <!-- Requirements -->
                <div class="alert alert-warning border-0 mb-4" style="border-radius:10px;">
                    <h6 class="fw-bold mb-2"><i class="fas fa-clipboard-list me-2"></i>Refund Eligibility</h6>
                    <div class="req-item">Order must have status: <strong>Delivered</strong></div>
                    <div class="req-item">Payment must have been approved</div>
                    <div class="req-item">Request within 30 days of delivery</div>
                    <div class="req-item">Only one refund per order is allowed</div>
                </div>

                <?php if ($order): ?>
                <div class="order-info-box mb-4">
                    <h6 class="fw-bold mb-3">Selected Order</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Order ID:</strong> <?php echo htmlspecialchars($order['order_id']); ?></p>
                            <p class="mb-1"><strong>Product:</strong> <?php echo htmlspecialchars($order['product_name']); ?></p>
                            <p class="mb-0"><strong>Qty:</strong> <?php echo $order['quantity']; ?></p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Total:</strong> TZS <?php echo number_format($order['total_price'], 0); ?></p>
                            <p class="mb-1"><strong>Status:</strong>
                                <span class="sbadge <?php echo $order['status'] === 'Delivered' ? 'sb-delivered' : 'sb-processing'; ?>">
                                    <?php echo $order['status']; ?>
                                </span>
                            </p>
                            <p class="mb-0"><strong>Payment:</strong>
                                <span class="sbadge <?php echo in_array($order['payment_status'], ['Approved','completed']) ? 'sb-completed' : 'sb-pending'; ?>">
                                    <?php echo ucfirst($order['payment_status'] ?? 'Pending'); ?>
                                </span>
                            </p>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Order ID <span class="text-danger">*</span></label>
                        <input type="text" name="order_id" class="form-control form-control-lg"
                               value="<?php echo htmlspecialchars($order_id ?: ($_POST['order_id'] ?? '')); ?>"
                               placeholder="e.g. ORD-20240115-ABCDEF" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Refund Method</label>
                        <select class="form-select" name="refund_method">
                            <option value="M-Pesa">M-Pesa</option>
                            <option value="Airtel Money">Airtel Money</option>
                            <option value="Tigo Pesa">Tigo Pesa</option>
                            <option value="Bank Transfer">Bank Transfer</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Reason for Refund <span class="text-danger">*</span></label>
                        <textarea name="reason" class="form-control" rows="5"
                                  placeholder="Describe the issue in detail (damaged item, wrong product, etc.)..."
                                  required><?php echo htmlspecialchars($_POST['reason'] ?? ''); ?></textarea>
                        <div class="form-text">Be specific — more detail helps us process faster.</div>
                    </div>
                    <div class="mb-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="terms" required>
                            <label class="form-check-label" for="terms">I agree to the refund terms and conditions</label>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-danger btn-lg w-100">
                        <i class="fas fa-paper-plane me-2"></i> Submit Refund Request
                    </button>
                </form>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="form-card">
                <h6 class="fw-bold mb-3"><i class="fas fa-clock me-2 text-primary"></i>Processing Time</h6>
                <p class="text-muted small">Your request will be reviewed within <strong>2–5 business days</strong>.</p>
                <p class="text-muted small">We will contact you at:</p>
                <p><i class="fas fa-phone me-2 text-success"></i><strong><?php echo htmlspecialchars($customer['phone']); ?></strong></p>
                <?php if (!empty($customer['email'])): ?>
                <p><i class="fas fa-envelope me-2 text-success"></i><small><?php echo htmlspecialchars($customer['email']); ?></small></p>
                <?php endif; ?>
                <hr>
                <p class="text-muted small"><i class="fas fa-info-circle me-1"></i>Refunds are processed back to your original payment method.</p>
                <a href="my_orders.php" class="btn btn-outline-primary btn-sm w-100 mt-2">
                    <i class="fas fa-list me-1"></i> View My Orders
                </a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
