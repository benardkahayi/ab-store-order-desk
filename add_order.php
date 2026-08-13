<?php
session_start();
require './db.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['role'] !== 'admin') {
    header("Location: admin_login.php"); exit();
}

$customers = $conn->query("SELECT id, name, phone FROM customers ORDER BY name")->fetchAll();
$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_id  = (int)($_POST['customer_id']  ?? 0);
    $product_name = trim($_POST['product_name']  ?? '');
    $quantity     = (int)($_POST['quantity']      ?? 1);
    $unit_price   = (float)($_POST['unit_price']  ?? 0);
    $description  = trim($_POST['description']   ?? '');

    if ($customer_id <= 0 || empty($product_name)) {
        $error = "Chagua mteja na andika jina la bidhaa.";
    } elseif ($quantity <= 0 || $unit_price <= 0) {
        $error = "Idadi na bei lazima ziwe zaidi ya sifuri.";
    } else {
        try {
            $stmt = $conn->prepare("SELECT name, phone FROM customers WHERE id = ?");
            $stmt->execute([$customer_id]);
            $customer = $stmt->fetch();
            if (!$customer) throw new Exception("Mteja hajapatikana.");

            $order_id    = 'ORD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
            $total_price = $quantity * $unit_price;

            $conn->prepare("
                INSERT INTO orders (order_id,customer_id,customer_name,phone,product_name,quantity,unit_price,total_price,description,status)
                VALUES (?,?,?,?,?,?,?,?,?,'Processing')
            ")->execute([$order_id,$customer_id,$customer['name'],$customer['phone'],$product_name,$quantity,$unit_price,$total_price,$description]);

            $success = "Oda imehifadhiwa! Order ID: <strong>$order_id</strong>";
            $_POST = [];
        } catch (PDOException $e) {
            $error = "Hitilafu ya database: " . $e->getMessage();
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="sw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Oda Mpya - AB FASHIONSTORE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .total-box {
            background: linear-gradient(135deg,#eff6ff,#dbeafe);
            border: 2px solid #3b82f6; border-radius: 12px;
            padding: 18px; text-align: center; margin: 16px 0;
        }
        .form-card { background: white; border-radius: 14px; padding: 28px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); max-width: 860px; }
    </style>
</head>
<body>
<?php $active_page = 'add_order'; include 'admin_layout.php'; ?>

<div class="main-content">

    <div class="page-header">
        <div>
            <h1><i class="fas fa-plus-circle me-2 text-primary"></i>Create New Order</h1>
            <p>Fill in the details below to add a new customer order</p>
        </div>
        <a href="orders.php" class="btn btn-outline-secondary">
            <i class="fas fa-list me-1"></i> All Orders
        </a>
    </div>

    <div class="form-card">
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle me-2"></i> <?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-triangle me-2"></i> <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form method="POST" id="orderForm">
            <div class="row g-3">
                <div class="col-md-12">
                    <label class="form-label fw-bold">Mteja *</label>
                    <select name="customer_id" class="form-select form-select-lg" required>
                        <option value="">-- Chagua Mteja --</option>
                        <?php foreach ($customers as $c): ?>
                        <option value="<?php echo $c['id']; ?>" <?php if(($_POST['customer_id'] ?? 0) == $c['id']) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($c['name'] . ' — ' . $c['phone']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12">
                    <label class="form-label fw-bold">Jina la Bidhaa *</label>
                    <input type="text" name="product_name" class="form-control form-control-lg"
                           value="<?php echo htmlspecialchars($_POST['product_name'] ?? ''); ?>"
                           placeholder="Mfano: Men's Sneakers Nike Air — Size 42 — Black" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-bold">Idadi *</label>
                    <input type="number" name="quantity" id="qty" class="form-control form-control-lg"
                           value="<?php echo $_POST['quantity'] ?? 1; ?>" min="1" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Bei kwa Kimoja (TZS) *</label>
                    <input type="number" name="unit_price" id="price" class="form-control form-control-lg"
                           value="<?php echo $_POST['unit_price'] ?? ''; ?>" min="0" step="1" required>
                </div>
            </div>

            <div class="total-box my-3">
                <p class="text-muted mb-1" style="font-size:13px;">Jumla ya Malipo</p>
                <h3 class="fw-bold mb-0 text-primary" id="totalAmount">TZS 0</h3>
                <small class="text-muted">Idadi × Bei kwa Kimoja</small>
            </div>

            <div class="mb-4">
                <label class="form-label fw-bold">Maelezo ya Ziada</label>
                <textarea name="description" class="form-control" rows="3"
                          placeholder="Rangi, saizi, maelezo yoyote ya ziada..."><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
            </div>

            <div class="d-flex gap-3">
                <button type="submit" class="btn btn-primary btn-lg px-5">
                    <i class="fas fa-save me-2"></i> Hifadhi Oda
                </button>
                <button type="reset" class="btn btn-outline-secondary btn-lg px-4" onclick="setTimeout(calcTotal,10)">
                    <i class="fas fa-redo me-1"></i> Safisha
                </button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const qtyEl   = document.getElementById('qty');
const priceEl = document.getElementById('price');
const totalEl = document.getElementById('totalAmount');

function calcTotal() {
    const t = (parseFloat(qtyEl.value) || 0) * (parseFloat(priceEl.value) || 0);
    totalEl.textContent = 'TZS ' + t.toLocaleString('en-US', {maximumFractionDigits: 0});
}
qtyEl.addEventListener('input', calcTotal);
priceEl.addEventListener('input', calcTotal);
calcTotal();
</script>
</body>
</html>
