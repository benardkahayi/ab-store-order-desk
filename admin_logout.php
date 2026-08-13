<?php
session_start();

// Check if confirmation is given
if (!isset($_GET['confirm']) || $_GET['confirm'] != 'yes') {
    // Show confirmation page
    ?>
    <!DOCTYPE html>
    <html lang="sw">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Logout - Admin Panel</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
        <style>
            body {
                background: linear-gradient(135deg, #2c3e50 0%, #1a252f 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                font-family: 'Segoe UI', sans-serif;
            }
            .logout-card {
                background: white;
                padding: 40px;
                border-radius: 15px;
                text-align: center;
                max-width: 450px;
                box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            }
            .logout-icon {
                font-size: 70px;
                color: #e74c3c;
                margin-bottom: 20px;
            }
            .btn-logout {
                background: #e74c3c;
                color: white;
                padding: 12px 30px;
                border-radius: 8px;
                border: none;
                font-weight: 600;
                transition: all 0.3s;
            }
            .btn-logout:hover {
                background: #c0392b;
                transform: translateY(-2px);
            }
            .btn-cancel {
                background: #95a5a6;
                color: white;
                padding: 12px 30px;
                border-radius: 8px;
                text-decoration: none;
                font-weight: 600;
                transition: all 0.3s;
            }
            .btn-cancel:hover {
                background: #7f8c8d;
                color: white;
            }
        </style>
    </head>
    <body>
        <div class="logout-card">
            <div class="logout-icon">
                <i class="fas fa-sign-out-alt"></i>
            </div>
            <h3>Je, una uhakika unataka kuondoka?</h3>
            <p class="text-muted my-3">Utahitaji kuingia tena kwa username na password yako kufikia panel hii.</p>
            <hr>
            <div class="d-flex justify-content-center gap-3">
                <a href="?confirm=yes" class="btn btn-logout">
                    <i class="fas fa-sign-out-alt me-2"></i> Ndiyo, Ondoka
                </a>
                <a href="admin_dashboard.php" class="btn-cancel">
                    <i class="fas fa-times me-2"></i> Ghairi
                </a>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Process logout
$_SESSION = array();

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();
header("Location: admin_login.php");
exit();
?>