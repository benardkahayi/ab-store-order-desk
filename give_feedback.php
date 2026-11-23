<?php
session_start();
require 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'customer') {
    header("Location: customer_login.php"); exit;
}

$customer_id = $_SESSION['customer_id'];
$stmt        = $conn->prepare("SELECT * FROM customers WHERE id = ?");
$stmt->execute([$customer_id]);
$customer = $stmt->fetch();

$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating  = (int)($_POST['rating'] ?? 0);
    $message = trim($_POST['message'] ?? '');

    if ($rating >= 1 && $rating <= 5 && !empty($message)) {
        $ins = $conn->prepare("INSERT INTO feedback (customer_phone, rating, message) VALUES (?, ?, ?)");
        if ($ins->execute([$customer['phone'], $rating, $message])) {
            $success = "Asante! Thank you for your feedback!";
            $_POST   = [];
        } else {
            $error = "An error occurred while saving your feedback. Please try again.";
        }
    } else {
        $error = "Please select a star rating and write a message.";
    }
}
?>
<!DOCTYPE html>
<html lang="sw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Feedback - AB FASHIONSTORE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .star-rating { display: flex; flex-direction: row-reverse; gap: 8px; margin: 12px 0; }
        .star-rating input { display: none; }
        .star-rating label { font-size: 42px; color: #d1d5db; cursor: pointer; transition: color 0.2s; line-height: 1; }
        .star-rating input:checked ~ label,
        .star-rating label:hover,
        .star-rating label:hover ~ label { color: #f59e0b; }
    </style>
</head>
<body>
<?php $active_page = 'feedback'; include 'customer_layout.php'; ?>

<div class="main-content">

    <div class="page-header">
        <div>
            <h1><i class="fas fa-comment-dots me-2 text-primary"></i>Give Feedback</h1>
            <p>Help us improve our service — your opinion matters</p>
        </div>
        <a href="customer_dashboard.php" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i> Dashboard
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

    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="form-card">

                <!-- Customer info strip -->
                <div class="d-flex align-items-center gap-3 mb-4 p-3 rounded-3" style="background:#f8fafc;">
                    <div style="width:46px;height:46px;background:linear-gradient(135deg,#3b82f6,#6366f1);border-radius:50%;display:flex;align-items:center;justify-content:center;color:white;font-weight:700;font-size:18px;flex-shrink:0;">
                        <?php echo strtoupper(substr($customer['name'], 0, 1)); ?>
                    </div>
                    <div>
                        <div class="fw-semibold"><?php echo htmlspecialchars($customer['name']); ?></div>
                        <div class="text-muted small"><?php echo htmlspecialchars($customer['phone']); ?></div>
                    </div>
                    <span class="ms-auto badge bg-success-subtle text-success" style="font-size:11px;">Verified Customer</span>
                </div>

                <form method="POST">
                    <div class="mb-4">
                        <label class="form-label fw-semibold">How would you rate our service?</label>
                        <p class="text-muted small mb-0">1 star = Poor &nbsp;|&nbsp; 5 stars = Excellent</p>
                        <div class="star-rating">
                            <input type="radio" id="star5" name="rating" value="5" checked>
                            <label for="star5">★</label>
                            <input type="radio" id="star4" name="rating" value="4">
                            <label for="star4">★</label>
                            <input type="radio" id="star3" name="rating" value="3">
                            <label for="star3">★</label>
                            <input type="radio" id="star2" name="rating" value="2">
                            <label for="star2">★</label>
                            <input type="radio" id="star1" name="rating" value="1">
                            <label for="star1">★</label>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Your Feedback <span class="text-danger">*</span></label>
                        <textarea name="message" class="form-control" rows="6"
                                  placeholder="Share your experience with our products, delivery time, packaging, or customer support..."
                                  required><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
                        <div class="form-text">Your feedback is confidential and used only to improve our service.</div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg w-100">
                        <i class="fas fa-paper-plane me-2"></i> Submit Feedback
                    </button>
                </form>

                <hr class="my-4">
                <div class="d-flex gap-2 justify-content-center text-muted small">
                    <i class="fas fa-lock text-success mt-1"></i>
                    Your responses are private and encrypted
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
