<?php
session_start();
require_once __DIR__ .'/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username && $password) {
        $stmt = $conn->prepare("SELECT * FROM admins WHERE username = ?");
        $stmt->execute([$username]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id']        = $admin['id'];
            $_SESSION['admin_name']      = $admin['name'];
            $_SESSION['role']            = 'admin';

            header("Location: admin_dashboard.php");
            exit;
        } else {
            $error = "Jina la mtumiaji au password si sahihi.";
        }
    } else {
        $error = "Tafadhali jaza jina la mtumiaji na password.";
    }
}
?>
<!DOCTYPE html>
<html lang="sw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - AB FASHIONSTORE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', 'Segoe UI', sans-serif;
            min-height: 100vh;
            display: flex;
        }

        /* ── Left Panel ── */
        .left-panel {
            flex: 0 0 55%;
            position: relative;
            background: url('image/bg.jpg') center center / cover no-repeat;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 40px;
            overflow: hidden;
        }

        /* Dark overlay */
        .left-panel::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(160deg,
                rgba(10,15,40,0.88) 0%,
                rgba(15,23,64,0.80) 50%,
                rgba(5,10,30,0.92) 100%);
        }

        .left-panel > * { position: relative; z-index: 1; }

        /* Brand */
        .brand { display: flex; align-items: center; gap: 14px; }
        .brand-logo {
            width: 52px; height: 52px;
            background: white; border-radius: 12px; padding: 4px;
            display: flex; align-items: center; justify-content: center;
        }
        .brand-logo img { width: 44px; height: 44px; object-fit: cover; border-radius: 8px; }
        .brand-text h2 { color: white; font-size: 20px; font-weight: 800; margin: 0; letter-spacing: 0.3px; }
        .brand-text p  { color: rgba(255,255,255,0.55); font-size: 11px; margin: 0; }

        /* Centre tagline */
        .left-center {
            text-align: center;
            padding: 0 20px;
        }
        .left-center .main-img {
            width: 240px; max-width: 100%;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.4);
            margin-bottom: 28px;
            object-fit: cover;
        }
        .left-center h1 {
            color: white; font-size: 32px; font-weight: 800;
            line-height: 1.2; margin-bottom: 10px;
        }
        .left-center h1 span { color: #818cf8; }
        .left-center p {
            color: rgba(255,255,255,0.65); font-size: 15px; margin-bottom: 0;
        }

        /* Trust badges */
        .trust-badges {
            display: flex; justify-content: center; gap: 28px;
            flex-wrap: wrap;
        }
        .trust-badge {
            display: flex; flex-direction: column; align-items: center; gap: 6px;
            color: rgba(255,255,255,0.75);
        }
        .trust-badge .icon-wrap {
            width: 42px; height: 42px; border-radius: 12px;
            border: 1.5px solid rgba(255,255,255,0.2);
            background: rgba(255,255,255,0.07);
            display: flex; align-items: center; justify-content: center;
            font-size: 17px;
        }
        .trust-badge span { font-size: 11px; font-weight: 500; }

        /* ── Right Panel ── */
        .right-panel {
            flex: 0 0 45%;
            background: #f8fafc;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 36px;
        }

        .login-box { width: 100%; max-width: 380px; }

        .login-box h3 {
            font-size: 26px; font-weight: 800; color: #0f172a;
            margin-bottom: 6px;
        }
        .login-box .subtitle {
            color: #64748b; font-size: 14px; margin-bottom: 28px;
        }

        .form-label { font-weight: 600; font-size: 13px; color: #334155; margin-bottom: 6px; }

        .input-wrap { position: relative; }
        .input-wrap .input-icon {
            position: absolute; left: 14px; top: 50%; transform: translateY(-50%);
            color: #94a3b8; font-size: 15px; pointer-events: none;
        }
        .input-wrap input {
            width: 100%; height: 48px;
            padding: 0 14px 0 42px;
            border: 1.5px solid #e2e8f0; border-radius: 12px;
            font-size: 14px; background: white; outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .input-wrap input:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99,102,241,0.12);
        }
        .input-wrap .toggle-pw {
            position: absolute; right: 14px; top: 50%; transform: translateY(-50%);
            color: #94a3b8; cursor: pointer; font-size: 15px; background: none; border: none;
        }
        .input-wrap .toggle-pw:hover { color: #6366f1; }

        .btn-login {
            width: 100%; height: 50px;
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            color: white; border: none; border-radius: 12px;
            font-size: 15px; font-weight: 700; cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            display: flex; align-items: center; justify-content: center; gap: 8px;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(79,70,229,0.35);
        }

        .divider {
            display: flex; align-items: center; gap: 10px;
            color: #cbd5e1; font-size: 12px; margin: 18px 0;
        }
        .divider::before, .divider::after {
            content: ''; flex: 1; height: 1px; background: #e2e8f0;
        }

        .btn-home {
            width: 100%; height: 46px;
            background: white; color: #475569;
            border: 1.5px solid #e2e8f0; border-radius: 12px;
            font-size: 14px; font-weight: 600; cursor: pointer;
            display: flex; align-items: center; justify-content: center; gap: 8px;
            text-decoration: none; transition: border-color 0.2s, background 0.2s;
        }
        .btn-home:hover { border-color: #6366f1; color: #6366f1; background: #f5f3ff; }

        .alert-error {
            background: #fef2f2; border: 1px solid #fecaca;
            border-radius: 10px; padding: 12px 14px;
            color: #dc2626; font-size: 13px;
            display: flex; align-items: center; gap: 10px;
            margin-bottom: 20px;
        }

        .remember-row {
            display: flex; justify-content: space-between; align-items: center;
            margin: 10px 0 18px;
        }
        .remember-row label { font-size: 13px; color: #475569; cursor: pointer; }
        .remember-row a { font-size: 13px; color: #6366f1; text-decoration: none; font-weight: 500; }
        .remember-row a:hover { text-decoration: underline; }

        .footer-note {
            margin-top: 24px; text-align: center;
            font-size: 12px; color: #94a3b8;
        }
        .footer-note i { color: #6366f1; }

        /* Mobile: stack vertically */
        @media (max-width: 768px) {
            body { flex-direction: column; }
            .left-panel { flex: none; min-height: 220px; padding: 28px 24px; }
            .left-center { display: none; }
            .right-panel { flex: 1; padding: 30px 20px; }
        }
    </style>
</head>
<body>

    <!-- ══ LEFT PANEL ══ -->
    <div class="left-panel">

        <!-- Brand at top -->
        <div class="brand">
            <div class="brand-logo">
                <img src="image/logo.png" alt="AB FASHIONSTORE">
            </div>
            <div class="brand-text">
                <h2>AB FASHIONSTORE</h2>
                <p>Blessings Collection</p>
            </div>
        </div>

        <!-- Centre content -->
        <div class="left-center">
            <img src="image/mzigo.jpg" alt="Delivery" class="main-img">
            <h1>Import. Ship.<br><span>Deliver.</span> You Smile 😊</h1>
            <p>Karibu kwenye mfumo wa usimamizi<br>wa biashara yako.</p>
        </div>

        <!-- Trust badges at bottom -->
        <div class="trust-badges">
            <div class="trust-badge">
                <div class="icon-wrap"><i class="fas fa-shield-alt"></i></div>
                <span>Secure</span>
            </div>
            <div class="trust-badge">
                <div class="icon-wrap"><i class="fas fa-bolt"></i></div>
                <span>Fast</span>
            </div>
            <div class="trust-badge">
                <div class="icon-wrap"><i class="fas fa-star"></i></div>
                <span>Reliable</span>
            </div>
            <div class="trust-badge">
                <div class="icon-wrap"><i class="fas fa-lock"></i></div>
                <span>Trusted</span>
            </div>
        </div>
    </div>

    <!-- ══ RIGHT PANEL ══ -->
    <div class="right-panel">
        <div class="login-box">

            <h3>Admin Login</h3>
            <p class="subtitle">Ingiza taarifa zako kuendelea</p>

            <?php if($error): ?>
            <div class="alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Jina la Mtumiaji</label>
                    <div class="input-wrap">
                        <i class="fas fa-user input-icon"></i>
                        <input type="text" name="username"
                               placeholder="Ingiza jina la mtumiaji"
                               value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                               required autocomplete="username">
                    </div>
                </div>

                <div class="mb-1">
                    <label class="form-label">Password</label>
                    <div class="input-wrap">
                        <i class="fas fa-key input-icon"></i>
                        <input type="password" name="password" id="pwField"
                               placeholder="Ingiza password"
                               required autocomplete="current-password">
                        <button type="button" class="toggle-pw" onclick="togglePw()" title="Show/hide password">
                            <i class="fas fa-eye" id="pwEye"></i>
                        </button>
                    </div>
                </div>

                <div class="remember-row">
                    <label>
                        <input type="checkbox" name="remember" class="me-1"> Remember me
                    </label>
                    <a href="#">Forgot password?</a>
                </div>

                <button type="submit" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
            </form>

            <div class="divider">or</div>

            <a href="index.php" class="btn-home">
                <i class="fas fa-home"></i> Rudi kwenye Homepage
            </a>

            <div class="footer-note">
                <i class="fas fa-shield-alt"></i>
                Restricted access — authorized personnel only.
            </div>
        </div>
    </div>

    <script>
        function togglePw() {
            const f = document.getElementById('pwField');
            const e = document.getElementById('pwEye');
            if (f.type === 'password') {
                f.type = 'text';
                e.className = 'fas fa-eye-slash';
            } else {
                f.type = 'password';
                e.className = 'fas fa-eye';
            }
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
