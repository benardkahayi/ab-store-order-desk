<?php
session_start();
require './db.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['role'] !== 'admin') {
    header("Location: admin_login.php"); exit();
}

$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name']     ?? '');
    $phone    = trim($_POST['phone']    ?? '');
    $email    = trim($_POST['email']    ?? '');
    $address  = trim($_POST['address']  ?? '');
    $password = trim($_POST['password'] ?? '') ?: $phone;

    if (empty($name) || empty($phone)) {
        $error = "Jina na namba ya simu vinahitajika!";
    } else {
        try {
            $conn->prepare("INSERT INTO customers (name,phone,email,address,password) VALUES (:name,:phone,:email,:address,:password)")
                 ->execute([':name'=>$name, ':phone'=>$phone, ':email'=>$email,
                            ':address'=>$address, ':password'=>password_hash($password, PASSWORD_DEFAULT)]);
            $success = "Mteja ameingizwa kikamilifu!";
            $_POST = [];
        } catch (PDOException $e) {
            $error = $e->getCode() == 23000 ? "Namba ya simu tayari imesajiliwa!" : "Hitilafu: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="sw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ingiza Mteja - AB FASHIONSTORE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .form-card { background: white; border-radius: 14px; padding: 28px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); max-width: 860px; }
    </style>
</head>
<body>
<?php $active_page = 'add_customer'; include 'admin_layout.php'; ?>

<div class="main-content">

    <div class="page-header">
        <div>
            <h1><i class="fas fa-user-plus me-2 text-primary"></i>Ingiza Mteja Mpya</h1>
            <p>Add a new customer to the system</p>
        </div>
        <a href="customers.php" class="btn btn-outline-secondary">
            <i class="fas fa-list me-1"></i> Orodha ya Wateja
        </a>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show mb-3">
            <i class="fas fa-check-circle me-2"></i> <?php echo htmlspecialchars($success); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show mb-3">
            <i class="fas fa-exclamation-triangle me-2"></i> <?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="form-card">
        <form method="POST">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-bold"><i class="fas fa-user me-1 text-primary"></i> Jina Kamili *</label>
                    <input type="text" name="name" class="form-control form-control-lg"
                           value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>"
                           placeholder="Mfano: John Doe" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold"><i class="fas fa-phone me-1 text-primary"></i> Namba ya Simu *</label>
                    <input type="tel" name="phone" class="form-control form-control-lg"
                           value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>"
                           placeholder="Mfano: 0712345678" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold"><i class="fas fa-envelope me-1 text-primary"></i> Barua Pepe</label>
                    <input type="email" name="email" class="form-control form-control-lg"
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                           placeholder="Mfano: mteja@example.com">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold"><i class="fas fa-lock me-1 text-primary"></i> Password</label>
                    <input type="text" name="password" class="form-control form-control-lg"
                           value="<?php echo htmlspecialchars($_POST['password'] ?? ''); ?>"
                           placeholder="Acha tupu kutumia namba ya simu">
                    <div class="form-text">Kama utaacha tupu, namba ya simu itatumika kama password</div>
                </div>
                <div class="col-12">
                    <label class="form-label fw-bold"><i class="fas fa-map-marker-alt me-1 text-primary"></i> Anwani</label>
                    <textarea name="address" class="form-control" rows="3"
                              placeholder="Mfano: Sokoine Street, Dar es Salaam"><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
                </div>
            </div>

            <div class="d-flex gap-3 mt-4">
                <button type="submit" class="btn btn-primary btn-lg px-5">
                    <i class="fas fa-save me-2"></i> Hifadhi Mteja
                </button>
                <button type="reset" class="btn btn-outline-secondary btn-lg px-4">
                    <i class="fas fa-redo me-1"></i> Safisha Fomu
                </button>
            </div>
        </form>

        <hr class="my-4">
        <div class="alert alert-info border-0 mb-0">
            <h6 class="fw-bold"><i class="fas fa-info-circle me-2"></i>Maelezo:</h6>
            <ul class="mb-0 small">
                <li>Namba ya simu lazima iwe ya kipekee kwa kila mteja</li>
                <li>Kwa password, kama utaacha tupu, mteja atatumia namba ya simu kama password</li>
                <li>Mteja ataweza kubadilisha password baada ya kuingia kwa mara ya kwanza</li>
            </ul>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
