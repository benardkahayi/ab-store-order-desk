<?php
session_start();
require 'db.php';

if (!isset($_SESSION['customer_id'])) {
    header("Location: customer_login.php"); exit;
}

$customer_id = $_SESSION['customer_id'];
$stmt = $conn->prepare("SELECT * FROM customers WHERE id = ?");
$stmt->execute([$customer_id]);
$customer = $stmt->fetch();

if (!$customer) { session_destroy(); header("Location: customer_login.php"); exit; }

$success_message = '';
$error_message   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name         = trim($_POST['name']);
    $phone        = trim($_POST['phone']);
    $email        = trim($_POST['email']);
    $address      = trim($_POST['address']);
    $new_password = trim($_POST['new_password']);

    try {
        $query  = "UPDATE customers SET name=?, phone=?, email=?, address=?";
        $params = [$name, $phone, $email, $address];
        if (!empty($new_password)) {
            $query   .= ", password=?";
            $params[] = password_hash($new_password, PASSWORD_DEFAULT);
        }
        $query   .= " WHERE id=?";
        $params[] = $customer_id;

        $conn->prepare($query)->execute($params);

        $_SESSION['customer_name']  = $name;
        $_SESSION['customer_phone'] = $phone;

        $stmt->execute([$customer_id]);
        $customer = $stmt->fetch();

        $success_message = "Profile updated successfully!";
    } catch (PDOException $e) {
        $error_message = $e->getCode() == 23000
            ? "That phone number is already registered to another account."
            : "Error: " . $e->getMessage();
    }
}

// Order stats for the profile sidebar
$stmt2 = $conn->prepare("SELECT COUNT(*) FROM orders WHERE phone = ?");
$stmt2->execute([$customer['phone']]);
$total_orders = $stmt2->fetchColumn();

$stmt2->execute([$customer['phone']]);
$stmt3 = $conn->prepare("SELECT COUNT(*) FROM orders WHERE phone = ? AND status = 'Delivered'");
$stmt3->execute([$customer['phone']]);
$delivered = $stmt3->fetchColumn();
?>
<!DOCTYPE html>
<html lang="sw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - AB FASHIONSTORE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .profile-banner {
            background: linear-gradient(135deg,#1e3a8a,#3b82f6);
            border-radius: 14px; padding: 26px 28px; color: white;
            margin-bottom: 22px; display: flex; align-items: center; gap: 20px;
        }
        .big-avatar {
            width: 76px; height: 76px; border-radius: 50%;
            background: rgba(255,255,255,0.2);
            display: flex; align-items: center; justify-content: center;
            font-size: 34px; font-weight: 700; border: 3px solid rgba(255,255,255,0.5); flex-shrink: 0;
        }
        .info-row { display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid #f1f5f9; font-size: 13.5px; }
        .info-row:last-child { border-bottom: none; }
    </style>
</head>
<body>
<?php $active_page = 'profile'; include 'customer_layout.php'; ?>

<div class="main-content">

    <!-- Profile Banner -->
    <div class="profile-banner">
        <div class="big-avatar"><?php echo strtoupper(substr($customer['name'], 0, 1)); ?></div>
        <div>
            <h2 class="fw-bold mb-1" style="font-size:20px;"><?php echo htmlspecialchars($customer['name']); ?></h2>
            <p class="mb-1" style="opacity:.8;font-size:13px;"><i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($customer['phone']); ?></p>
            <?php if ($customer['email']): ?>
            <p class="mb-0" style="opacity:.8;font-size:13px;"><i class="fas fa-envelope me-1"></i><?php echo htmlspecialchars($customer['email']); ?></p>
            <?php endif; ?>
        </div>
        <div class="ms-auto text-end">
            <span class="badge bg-white text-success px-3 py-2">
                <i class="fas fa-check-circle me-1"></i> Active Account
            </span>
            <div style="font-size:12px;opacity:.7;margin-top:6px;">Member since <?php echo date('M Y', strtotime($customer['date_created'])); ?></div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Edit Form -->
        <div class="col-lg-8">
            <div class="form-card">
                <h5 class="fw-bold mb-4"><i class="fas fa-user-edit me-2 text-primary"></i>Edit Profile</h5>

                <?php if ($success_message): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
                <?php if ($error_message): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error_message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Full Name</label>
                            <input type="text" name="name" class="form-control"
                                   value="<?php echo htmlspecialchars($customer['name']); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Phone Number</label>
                            <input type="text" name="phone" class="form-control"
                                   value="<?php echo htmlspecialchars($customer['phone']); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Email Address</label>
                            <input type="email" name="email" class="form-control"
                                   value="<?php echo htmlspecialchars($customer['email'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">New Password <span class="text-muted fw-normal small">(leave blank to keep)</span></label>
                            <input type="password" name="new_password" class="form-control" placeholder="Enter new password">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Address</label>
                            <textarea name="address" class="form-control" rows="3"><?php echo htmlspecialchars($customer['address'] ?? ''); ?></textarea>
                        </div>
                    </div>
                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="fas fa-save me-2"></i> Save Changes
                        </button>
                        <button type="reset" class="btn btn-outline-secondary px-4">
                            <i class="fas fa-redo me-1"></i> Reset
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Account Info -->
        <div class="col-lg-4">
            <div class="form-card mb-3">
                <h6 class="fw-bold mb-3"><i class="fas fa-info-circle me-2 text-primary"></i>Account Info</h6>
                <div class="info-row"><span class="text-muted">Account ID</span><strong>#<?php echo $customer['id']; ?></strong></div>
                <div class="info-row"><span class="text-muted">Member Since</span><strong><?php echo date('d M Y', strtotime($customer['date_created'])); ?></strong></div>
                <div class="info-row"><span class="text-muted">Role</span><strong>Customer</strong></div>
                <div class="info-row"><span class="text-muted">Status</span><span class="sbadge sb-delivered">Active</span></div>
            </div>
            <div class="form-card">
                <h6 class="fw-bold mb-3"><i class="fas fa-chart-bar me-2 text-primary"></i>My Stats</h6>
                <div class="info-row"><span class="text-muted">Total Orders</span><strong class="text-primary"><?php echo $total_orders; ?></strong></div>
                <div class="info-row"><span class="text-muted">Delivered</span><strong class="text-success"><?php echo $delivered; ?></strong></div>
                <div class="info-row"><span class="text-muted">Wallet</span><strong>TZS 0</strong></div>
                <div class="mt-3 d-flex gap-2">
                    <a href="my_orders.php" class="btn btn-outline-primary btn-sm flex-fill">My Orders</a>
                    <a href="my_payments.php" class="btn btn-outline-secondary btn-sm flex-fill">Payments</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
