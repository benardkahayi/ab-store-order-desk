<?php
session_start();
require 'db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login_input = trim($_POST['login_input'] ?? '');
    $password    = trim($_POST['password']    ?? '');

    if ($login_input && $password) {
        // Find by phone
        $stmt = $conn->prepare("SELECT * FROM customers WHERE phone = ?");
        $stmt->execute([$login_input]);
        $customer = $stmt->fetch();

        // Find by order ID
        if (!$customer) {
            $os = $conn->prepare("
                SELECT c.* FROM customers c
                JOIN orders o ON c.id = o.customer_id
                WHERE o.order_id = ?
                ORDER BY o.date_created DESC LIMIT 1
            ");
            $os->execute([$login_input]);
            $customer = $os->fetch();
        }

        if ($customer) {
            $valid_password = false;

            $os2 = $conn->prepare("SELECT order_id FROM orders WHERE customer_id = ? ORDER BY date_created DESC LIMIT 1");
            $os2->execute([$customer['id']]);
            $recent_order = $os2->fetch();

            // First-time login: password = recent order ID
            if ($recent_order && $password === $recent_order['order_id']) {
                $valid_password = true;
                $conn->prepare("UPDATE customers SET password = ? WHERE id = ?")
                     ->execute([password_hash($recent_order['order_id'], PASSWORD_DEFAULT), $customer['id']]);
            } elseif (!empty($customer['password']) && password_verify($password, $customer['password'])) {
                $valid_password = true;
            }

            if ($valid_password) {
                $_SESSION['customer_id']    = $customer['id'];
                $_SESSION['customer_name']  = $customer['name'];
                $_SESSION['customer_phone'] = $customer['phone'];
                $_SESSION['role']           = 'customer';
                $_SESSION['order_id']       = $recent_order['order_id'] ?? '';
                header("Location: customer_dashboard.php"); exit;
            } else {
                $error = "Password si sahihi. Kwa mara ya kwanza, tumia Order ID yako kama password.";
            }
        } else {
            $error = "Hakuna akaunti iliyopatikana. Hakikisha namba ya simu au Order ID ni sahihi.";
        }
    } else {
        $error = "Tafadhali jaza namba ya simu/Order ID na password.";
    }
}
?>
<!DOCTYPE html>
<html lang="sw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Login - AB FASHIONSTORE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { height: 100%; font-family: 'Inter', sans-serif; }

        /* ── Outer wrapper ── */
        .page-wrap {
            min-height: 100vh;
            background: #e8e8f7;
            display: flex; align-items: center; justify-content: center;
            padding: 30px 16px;
        }

        /* ── Main card ── */
        .login-card {
            display: flex;
            width: 100%; max-width: 980px;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 30px 80px rgba(79,70,229,0.18);
        }

        /* ── LEFT PANEL ── */
        .left-panel {
            width: 52%;
            position: relative;
            background: linear-gradient(145deg,#3730a3 0%,#4f46e5 40%,#6d28d9 100%);
            padding: 36px 34px;
            display: flex; flex-direction: column;
            overflow: hidden;
            min-height: 620px;
        }
        .left-panel .bg-img {
            position: absolute; inset: 0;
            background: url('image/background.png') center bottom / cover no-repeat;
            opacity: 0.18;
        }
        .left-panel .overlay {
            position: absolute; inset: 0;
            background: linear-gradient(145deg,rgba(55,48,163,0.92) 0%,rgba(79,70,229,0.82) 50%,rgba(109,40,217,0.88) 100%);
        }
        .left-panel > * { position: relative; z-index: 1; }

        /* Brand */
        .brand { display: flex; align-items: center; gap: 12px; margin-bottom: 36px; }
        .brand-logo {
            width: 42px; height: 42px; background: rgba(255,255,255,0.15);
            border-radius: 10px; display: flex; align-items: center; justify-content: center;
            border: 1.5px solid rgba(255,255,255,0.3); padding: 3px;
        }
        .brand-logo img { width: 34px; height: 34px; object-fit: cover; border-radius: 7px; }
        .brand-text h3 { color: white; font-size: 14px; font-weight: 700; margin: 0; line-height: 1.2; }
        .brand-text p  { color: rgba(255,255,255,0.55); font-size: 10px; margin: 0; }

        /* Headline */
        .welcome-text { flex: 1; }
        .welcome-text h1 { color: white; font-size: 34px; font-weight: 800; line-height: 1.1; margin-bottom: 12px; }
        .welcome-text p  { color: rgba(255,255,255,0.75); font-size: 14px; line-height: 1.6; margin-bottom: 34px; }

        /* Feature list */
        .features { display: flex; flex-direction: column; gap: 16px; margin-bottom: 32px; }
        .feature-item { display: flex; align-items: center; gap: 14px; }
        .feature-icon {
            width: 42px; height: 42px; border-radius: 50%;
            background: rgba(255,255,255,0.12); border: 1.5px solid rgba(255,255,255,0.2);
            display: flex; align-items: center; justify-content: center;
            color: white; font-size: 16px; flex-shrink: 0;
        }
        .feature-text h6 { color: white; font-size: 13.5px; font-weight: 700; margin: 0; }
        .feature-text p  { color: rgba(255,255,255,0.65); font-size: 12px; margin: 0; }

        /* WhatsApp button */
        .wa-btn {
            display: inline-flex; align-items: center; gap: 8px;
            background: white; color: #16a34a; padding: 10px 20px;
            border-radius: 24px; font-size: 13.5px; font-weight: 600;
            border: none; cursor: pointer; text-decoration: none;
            transition: background 0.2s;
        }
        .wa-btn:hover { background: #f0fdf4; color: #15803d; }
        .wa-btn i { font-size: 18px; }

        /* ── RIGHT PANEL ── */
        .right-panel {
            width: 48%;
            background: #f5f5fb;
            display: flex; flex-direction: column; justify-content: space-between;
            padding: 36px 40px 22px;
        }

        /* Login box */
        .login-box {
            background: white; border-radius: 18px;
            padding: 32px 30px;
            box-shadow: 0 4px 20px rgba(79,70,229,0.07);
        }

        /* Avatar */
        .avatar-circle {
            width: 64px; height: 64px; border-radius: 50%;
            background: #ede9fe;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 16px;
        }
        .avatar-circle i { font-size: 30px; color: #6d28d9; }

        .login-title    { font-size: 22px; font-weight: 800; color: #1e1b4b; text-align: center; margin-bottom: 4px; }
        .login-subtitle { font-size: 13px; color: #6b7280; text-align: center; margin-bottom: 24px; }

        /* Form fields */
        .field-label { font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 6px; display: block; }
        .input-wrap  { position: relative; }
        .form-ctrl {
            width: 100%; padding: 12px 42px 12px 16px;
            border: 1.5px solid #e5e7eb; border-radius: 10px;
            font-size: 13.5px; background: #fafafa; outline: none;
            color: #1f2937; transition: border 0.2s;
        }
        .form-ctrl:focus { border-color: #6d28d9; background: white; }
        .input-icon {
            position: absolute; right: 14px; top: 50%; transform: translateY(-50%);
            color: #9ca3af; font-size: 15px; cursor: pointer;
        }

        /* Error */
        .err-box {
            background: #fef2f2; border: 1px solid #fecaca; border-radius: 10px;
            padding: 11px 14px; margin-bottom: 16px;
            display: flex; align-items: center; gap: 9px;
            color: #dc2626; font-size: 13px;
        }

        /* Info box */
        .info-box {
            background: #ede9fe; border-left: 3px solid #7c3aed;
            border-radius: 8px; padding: 10px 14px;
            margin-bottom: 18px; font-size: 12px; color: #5b21b6; line-height: 1.6;
        }

        /* Btn */
        .btn-login {
            width: 100%; padding: 13px;
            background: linear-gradient(135deg,#4f46e5,#7c3aed);
            color: white; border: none; border-radius: 10px;
            font-size: 15px; font-weight: 700; cursor: pointer;
            transition: opacity 0.2s;
        }
        .btn-login:hover { opacity: 0.9; }

        /* Remember + forgot */
        .flex-between { display: flex; justify-content: space-between; align-items: center; }
        .forgot-link { font-size: 12.5px; font-weight: 600; color: #6d28d9; text-decoration: none; }
        .forgot-link:hover { text-decoration: underline; }

        /* Footer row */
        .footer-row {
            display: flex; justify-content: space-between; align-items: center;
            padding-top: 18px; margin-top: 12px; border-top: 1px solid #e5e7eb;
        }
        .footer-row small { font-size: 11px; color: #9ca3af; }
        .track-link { font-size: 12px; font-weight: 600; color: #4f46e5; text-decoration: none; }
        .track-link:hover { text-decoration: underline; }

        /* Mobile */
        @media (max-width: 768px) {
            .login-card { flex-direction: column; border-radius: 18px; }
            .left-panel  { width: 100%; min-height: 220px; padding: 26px 24px; }
            .left-panel .welcome-text h1 { font-size: 24px; }
            .features { display: none; }
            .right-panel { width: 100%; padding: 26px 22px 18px; }
        }
    </style>
</head>
<body>
<div class="page-wrap">
<div class="login-card">

    <!-- ═══ LEFT PANEL ═══ -->
    <div class="left-panel">
        <div class="bg-img"></div>
        <div class="overlay"></div>

        <!-- Brand -->
        <div class="brand">
            <div class="brand-logo">
                <img src="image/logo.png" alt="AB FASHIONSTORE">
            </div>
            <div class="brand-text">
                <h3>AB FASHIONSTORE</h3>
                <p>Blessings Collection</p>
            </div>
        </div>

        <!-- Headline -->
        <div class="welcome-text">
            <h1>Welcome Back!</h1>
            <p>Your trusted partner for importing<br>and shipping from China to Tanzania</p>

            <!-- Features -->
            <div class="features">
                <div class="feature-item">
                    <div class="feature-icon"><i class="fas fa-truck"></i></div>
                    <div class="feature-text">
                        <h6>Track Your Orders</h6>
                        <p>Real-time tracking of your shipments</p>
                    </div>
                </div>
                <div class="feature-item">
                    <div class="feature-icon"><i class="fas fa-shield-alt"></i></div>
                    <div class="feature-text">
                        <h6>Secure Payments</h6>
                        <p>Safe and secure payment processing</p>
                    </div>
                </div>
                <div class="feature-item">
                    <div class="feature-icon"><i class="fas fa-headset"></i></div>
                    <div class="feature-text">
                        <h6>24/7 Support</h6>
                        <p>We're here to help you anytime</p>
                    </div>
                </div>
                <div class="feature-item">
                    <div class="feature-icon"><i class="fas fa-star"></i></div>
                    <div class="feature-text">
                        <h6>Quality Assurance</h6>
                        <p>Quality products, reliable service</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- WhatsApp -->
        <a href="https://wa.me/255" class="wa-btn" target="_blank">
            <i class="fab fa-whatsapp"></i> Need Help? Chat on WhatsApp
        </a>
    </div>

    <!-- ═══ RIGHT PANEL ═══ -->
    <div class="right-panel">

        <div class="login-box">
            <!-- Avatar -->
            <div class="avatar-circle">
                <i class="fas fa-user"></i>
            </div>

            <h1 class="login-title">Customer Login</h1>
            <p class="login-subtitle">Access your account to manage your orders</p>

            <!-- Error -->
            <?php if ($error): ?>
            <div class="err-box">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>

            <!-- Info hint -->
            <div class="info-box">
                <strong>Vidokezo:</strong> Ingiza namba ya simu uliyosajili.
                Kwa mara ya kwanza, tumia <strong>Order ID</strong> yako kama password.
            </div>

            <form method="POST">
                <!-- Phone / Order ID -->
                <div class="mb-3">
                    <label class="field-label">Phone Number or Order ID</label>
                    <div class="input-wrap">
                        <input type="text" name="login_input" class="form-ctrl"
                               placeholder="Enter your phone number or Order ID"
                               value="<?php echo htmlspecialchars($_POST['login_input'] ?? ''); ?>"
                               required autocomplete="username">
                        <i class="fas fa-user input-icon"></i>
                    </div>
                </div>

                <!-- Password -->
                <div class="mb-3">
                    <label class="field-label">Password</label>
                    <div class="input-wrap">
                        <input type="password" name="password" id="pwField" class="form-ctrl"
                               placeholder="Enter your password"
                               required autocomplete="current-password">
                        <i class="fas fa-eye input-icon" id="eyeToggle" onclick="togglePw()" style="cursor:pointer;"></i>
                    </div>
                </div>

                <!-- Remember + Forgot -->
                <div class="flex-between mb-4">
                    <div class="form-check mb-0">
                        <input class="form-check-input" type="checkbox" name="remember" id="rememberMe">
                        <label class="form-check-label" for="rememberMe" style="font-size:13px;">Remember Me</label>
                    </div>
                    <a href="#" class="forgot-link">Forgot Password?</a>
                </div>

                <button type="submit" class="btn-login">Login</button>
            </form>
        </div>

        <!-- Footer -->
        <div class="footer-row">
            <small>© <?php echo date('Y'); ?> AB FASHIONSTORE. All rights reserved.</small>
            <a href="track_order.php" class="track-link">Track Order Without Login &rarr;</a>
        </div>
    </div>

</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function togglePw() {
    const f = document.getElementById('pwField');
    const e = document.getElementById('eyeToggle');
    if (f.type === 'password') {
        f.type = 'text';
        e.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        f.type = 'password';
        e.classList.replace('fa-eye-slash', 'fa-eye');
    }
}
</script>
</body>
</html>
