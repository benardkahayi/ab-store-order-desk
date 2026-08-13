<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AB FASHIONSTORE — Import from China to Tanzania</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; color: #1e1b4b; overflow-x: hidden; }

        /* ── TOP BAR ── */
        .top-bar {
            background: linear-gradient(135deg,#3730a3,#4f46e5);
            padding: 8px 0; font-size: 12.5px; color: rgba(255,255,255,0.9);
        }
        .top-bar a { color: rgba(255,255,255,0.85); text-decoration: none; transition: color 0.2s; }
        .top-bar a:hover { color: white; }
        .top-bar .sep { margin: 0 12px; opacity: 0.35; }
        .social-icons a {
            width: 26px; height: 26px; border-radius: 6px;
            background: rgba(255,255,255,0.15);
            display: inline-flex; align-items: center; justify-content: center;
            color: white; font-size: 12px; margin-left: 6px; text-decoration: none;
            transition: background 0.2s;
        }
        .social-icons a:hover { background: rgba(255,255,255,0.3); }

        /* ── NAVBAR ── */
        .main-navbar {
            background: white; padding: 0 0;
            box-shadow: 0 2px 12px rgba(79,70,229,0.1);
            position: sticky; top: 0; z-index: 1000;
        }
        .main-navbar .container { height: 70px; display: flex; align-items: center; gap: 28px; }
        .brand { display: flex; align-items: center; gap: 10px; text-decoration: none; flex-shrink: 0; }
        .brand-icon {
            width: 44px; height: 44px; border-radius: 11px;
            background: linear-gradient(135deg,#4f46e5,#7c3aed);
            display: flex; align-items: center; justify-content: center;
            padding: 3px;
        }
        .brand-icon img { width: 38px; height: 38px; object-fit: cover; border-radius: 8px; }
        .brand-txt h3 { font-size: 14px; font-weight: 800; color: #1e1b4b; margin: 0; line-height: 1.1; }
        .brand-txt p  { font-size: 10px; color: #6d28d9; margin: 0; font-weight: 500; }
        .nav-links { display: flex; align-items: center; gap: 2px; flex: 1; }
        .nav-links a {
            padding: 8px 13px; font-size: 13.5px; font-weight: 500;
            color: #4b5563; text-decoration: none; border-radius: 8px;
            transition: all 0.2s; white-space: nowrap;
        }
        .nav-links a:hover, .nav-links a.active { color: #4f46e5; background: #ede9fe; }
        .nav-actions { display: flex; align-items: center; gap: 8px; flex-shrink: 0; }
        .btn-login {
            padding: 8px 20px; border: 2px solid #4f46e5; color: #4f46e5;
            border-radius: 9px; font-size: 13.5px; font-weight: 600;
            text-decoration: none; transition: all 0.2s;
        }
        .btn-login:hover { background: #ede9fe; }
        .btn-register {
            padding: 8px 20px; background: linear-gradient(135deg,#4f46e5,#7c3aed);
            color: white; border-radius: 9px; font-size: 13.5px; font-weight: 600;
            text-decoration: none; transition: opacity 0.2s;
        }
        .btn-register:hover { opacity: 0.9; color: white; }
        .btn-dashboard {
            padding: 8px 18px; background: #4f46e5; color: white;
            border-radius: 9px; font-size: 13px; font-weight: 600;
            text-decoration: none; display: flex; align-items: center; gap: 6px;
        }
        .btn-dashboard:hover { background: #4338ca; color: white; }

        /* ── HERO ── */
        .hero {
            background: linear-gradient(135deg,#eef2ff 0%,#f5f3ff 40%,#ede9fe 100%);
            padding: 72px 0 0; position: relative; overflow: hidden;
            min-height: 560px;
        }
        .hero::before {
            content: ''; position: absolute; top: -60px; right: -60px;
            width: 500px; height: 500px; border-radius: 50%;
            background: radial-gradient(circle,rgba(109,40,217,0.08),transparent 70%);
        }
        .hero-tag {
            display: inline-flex; align-items: center; gap: 7px;
            background: white; border: 1.5px solid #c4b5fd;
            border-radius: 24px; padding: 6px 14px;
            font-size: 13px; font-weight: 600; color: #5b21b6; margin-bottom: 22px;
        }
        .hero h1 {
            font-size: 44px; font-weight: 800; line-height: 1.12;
            color: #1e1b4b; margin-bottom: 18px;
        }
        .hero h1 span { color: #4f46e5; }
        .hero-sub { font-size: 15px; color: #4b5563; line-height: 1.7; margin-bottom: 22px; max-width: 480px; }
        .hero-checks { display: flex; gap: 20px; flex-wrap: wrap; margin-bottom: 32px; }
        .hero-check { display: flex; align-items: center; gap: 7px; font-size: 13.5px; font-weight: 600; color: #374151; }
        .hero-check i { color: #4f46e5; font-size: 16px; }
        .hero-btns { display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 36px; }
        .btn-start {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 13px 26px; background: linear-gradient(135deg,#4f46e5,#7c3aed);
            color: white; border-radius: 10px; font-size: 14px; font-weight: 700;
            text-decoration: none; transition: opacity 0.2s; border: none;
        }
        .btn-start:hover { opacity: 0.9; color: white; }
        .btn-track {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 13px 24px; background: white; color: #1e1b4b;
            border: 1.5px solid #d1d5db; border-radius: 10px; font-size: 14px; font-weight: 600;
            text-decoration: none; transition: border-color 0.2s;
        }
        .btn-track:hover { border-color: #4f46e5; color: #4f46e5; }
        .social-proof { display: flex; align-items: center; gap: 12px; }
        .avatars { display: flex; }
        .avatars span {
            width: 34px; height: 34px; border-radius: 50%;
            border: 2.5px solid white; display: inline-flex; align-items: center;
            justify-content: center; font-size: 13px; font-weight: 700; color: white;
            margin-left: -8px;
        }
        .avatars span:first-child { margin-left: 0; }
        .proof-text p { font-size: 13px; font-weight: 600; color: #374151; margin: 0; }
        .proof-text .stars { color: #f59e0b; font-size: 13px; }
        .hero-img-col { position: relative; display: flex; align-items: flex-end; justify-content: center; }
        .hero-img-col img { width: 100%; max-width: 560px; object-fit: contain; border-radius: 16px 16px 0 0; display: block; }

        /* ── FEATURE CARDS ── */
        .features { padding: 54px 0 40px; background: white; }
        .feat-card {
            background: white; border: 1.5px solid #e5e7eb; border-radius: 16px;
            padding: 26px 22px; transition: all 0.25s; height: 100%;
        }
        .feat-card:hover { border-color: #c4b5fd; box-shadow: 0 8px 28px rgba(79,70,229,0.1); transform: translateY(-4px); }
        .feat-icon {
            width: 52px; height: 52px; border-radius: 13px;
            background: #ede9fe; display: flex; align-items: center;
            justify-content: center; font-size: 22px; color: #6d28d9; margin-bottom: 16px;
        }
        .feat-card h5 { font-size: 15px; font-weight: 700; color: #1e1b4b; margin-bottom: 8px; }
        .feat-card p  { font-size: 13px; color: #6b7280; line-height: 1.6; margin-bottom: 14px; }
        .feat-link { font-size: 13px; font-weight: 600; color: #4f46e5; text-decoration: none; }
        .feat-link:hover { text-decoration: underline; }

        /* ── HOW IT WORKS ── */
        .how-section { padding: 60px 0; background: #f8f7ff; }
        .section-label {
            font-size: 11px; font-weight: 700; letter-spacing: 2px;
            color: #7c3aed; text-transform: uppercase; margin-bottom: 10px;
        }
        .section-title { font-size: 28px; font-weight: 800; color: #1e1b4b; margin-bottom: 12px; line-height: 1.25; }
        .section-sub    { font-size: 14px; color: #6b7280; line-height: 1.7; }
        .steps-row { display: flex; gap: 0; position: relative; }
        .steps-row::before {
            content: ''; position: absolute; top: 28px; left: 30px; right: 30px;
            height: 2px; background: linear-gradient(90deg,#4f46e5,#7c3aed);
            z-index: 0; border-radius: 2px;
        }
        .step-item { flex: 1; text-align: center; position: relative; z-index: 1; }
        .step-num {
            width: 56px; height: 56px; border-radius: 50%;
            background: linear-gradient(135deg,#4f46e5,#7c3aed);
            color: white; font-weight: 800; font-size: 16px;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 16px; border: 4px solid white;
            box-shadow: 0 4px 14px rgba(79,70,229,0.3);
        }
        .step-icon-box {
            width: 52px; height: 52px; border-radius: 13px;
            background: #ede9fe; display: flex; align-items: center;
            justify-content: center; margin: 0 auto 12px; font-size: 22px; color: #6d28d9;
        }
        .step-item h6 { font-size: 14px; font-weight: 700; color: #1e1b4b; margin-bottom: 6px; }
        .step-item p  { font-size: 12px; color: #6b7280; line-height: 1.5; padding: 0 10px; }

        /* Stats */
        .stats-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .stat-item {
            background: white; border-radius: 12px; padding: 18px 16px;
            display: flex; align-items: center; gap: 12px;
            box-shadow: 0 2px 8px rgba(79,70,229,0.06);
        }
        .stat-icon-box {
            width: 42px; height: 42px; border-radius: 11px;
            display: flex; align-items: center; justify-content: center; font-size: 18px; flex-shrink: 0;
        }
        .stat-item .sval { font-size: 22px; font-weight: 800; color: #1e1b4b; margin: 0; line-height: 1; }
        .stat-item .slbl { font-size: 11.5px; color: #6b7280; margin: 2px 0 0; }

        /* ── FOOTER ── */
        footer {
            background: #0f172a; color: rgba(255,255,255,0.75);
            padding: 50px 0 24px;
        }
        .footer-brand h3 { color: white; font-size: 16px; font-weight: 800; margin-bottom: 4px; }
        .footer-brand p  { font-size: 12px; color: rgba(255,255,255,0.5); }
        .footer-desc { font-size: 13px; line-height: 1.7; margin-top: 14px; }
        footer h6 { color: white; font-size: 13px; font-weight: 700; margin-bottom: 14px; }
        footer ul { list-style: none; padding: 0; }
        footer ul li { margin-bottom: 8px; }
        footer ul li a { color: rgba(255,255,255,0.65); text-decoration: none; font-size: 13px; transition: color 0.2s; }
        footer ul li a:hover { color: white; }
        .footer-contact p { font-size: 13px; margin-bottom: 8px; display: flex; align-items: center; gap: 8px; }
        .footer-contact i { color: #818cf8; width: 16px; }
        .footer-divider { border-color: rgba(255,255,255,0.08); margin: 30px 0 16px; }
        .copyright { font-size: 12px; color: rgba(255,255,255,0.4); }

        /* ── WhatsApp Float ── */
        .wa-float {
            position: fixed; bottom: 28px; right: 24px; z-index: 9999;
            display: flex; align-items: center; gap: 0;
            border-radius: 50px; overflow: hidden;
            box-shadow: 0 6px 24px rgba(22,163,74,0.35);
            text-decoration: none; animation: wa-pop 0.5s ease;
        }
        .wa-float .wa-icon-side {
            background: #22c55e; width: 48px; height: 48px;
            display: flex; align-items: center; justify-content: center;
            font-size: 22px; color: white; flex-shrink: 0;
        }
        .wa-float .wa-text-side {
            background: white; padding: 0 16px;
            display: flex; flex-direction: column; justify-content: center;
            height: 48px;
        }
        .wa-float .wa-text-side strong { font-size: 12.5px; font-weight: 700; color: #065f46; display: block; line-height: 1.2; }
        .wa-float .wa-text-side span   { font-size: 10.5px; color: #6b7280; }
        @keyframes wa-pop {
            from { transform: scale(0.8); opacity: 0; }
            to   { transform: scale(1);   opacity: 1; }
        }

        /* ── Responsive ── */
        @media (max-width: 991px) {
            .nav-links { display: none; }
            .hero h1 { font-size: 30px; }
            .hero-img-col { display: none; }
            .steps-row::before { display: none; }
            .steps-row { flex-direction: column; gap: 20px; }
            .stats-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 768px) {
            .top-bar .d-flex { flex-direction: column; gap: 4px; text-align: center; }
            .hero { padding: 48px 0 0; }
            .hero h1 { font-size: 26px; }
        }
    </style>
</head>
<body>

<!-- ══ TOP BAR ══ -->
<div class="top-bar">
    <div class="container d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="d-flex align-items-center gap-1 flex-wrap">
            <i class="fas fa-map-marker-alt"></i> Dar es Salaam, Tanzania
            <span class="sep">|</span>
            <i class="fas fa-phone"></i>
            <a href="tel:+255759431401">+255 759 431 401</a>
            <span class="sep">|</span>
            <i class="fas fa-envelope"></i>
            <a href="mailto:benardkahayi@gmail.com">benardkahayi@gmail.com</a>
        </div>
        <div class="d-flex align-items-center gap-1">
            <span style="font-size:11.5px;opacity:.7;">Follow us:</span>
            <div class="social-icons">
                <a href="#"><i class="fab fa-facebook-f"></i></a>
                <a href="#"><i class="fab fa-instagram"></i></a>
                <a href="https://wa.me/255759431401" target="_blank"><i class="fab fa-whatsapp"></i></a>
            </div>
        </div>
    </div>
</div>

<!-- ══ NAVBAR ══ -->
<nav class="main-navbar">
    <div class="container">
        <!-- Brand -->
        <a href="index.php" class="brand">
            <div class="brand-icon">
                <img src="image/logo.png" alt="AB FASHIONSTORE">
            </div>
            <div class="brand-txt">
                <h3>AB FASHIONSTORE</h3>
                <p>Blessings Collection</p>
            </div>
        </a>

        <!-- Nav Links -->
        <div class="nav-links">
            <a href="index.php" class="active">Home</a>
            <a href="#about">About Us</a>
            <a href="#how">How It Works</a>
            <a href="#services">Services</a>
            <a href="#features">Products</a>
            <a href="track_order.php">Track Order</a>
            <a href="#contact">Contact</a>
        </div>

        <!-- Actions -->
        <div class="nav-actions">
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'customer'): ?>
                <a href="customer_dashboard.php" class="btn-dashboard">
                    <i class="fas fa-home"></i> Dashboard
                </a>
                <a href="customer_logout.php" class="btn-login">Logout</a>
            <?php else: ?>
                <a href="customer_login.php" class="btn-login">Login</a>
                <a href="customer_login.php" class="btn-register">Register</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<!-- ══ HERO ══ -->
<section class="hero" id="home">
    <div class="container">
        <div class="row align-items-end">
            <div class="col-lg-6 pb-5">
                <div class="hero-tag">
                    <i class="fas fa-rocket" style="color:#7c3aed;"></i>
                    Your Trusted Import &amp; Shipping Partner
                </div>
                <h1>Import from <span>China,</span><br>We Handle <span>the Rest</span></h1>
                <p class="hero-sub">We help you import quality products from China and deliver them safely to your doorstep in Tanzania.</p>

                <div class="hero-checks">
                    <div class="hero-check"><i class="fas fa-check-circle"></i> Best Prices</div>
                    <div class="hero-check"><i class="fas fa-check-circle"></i> Fast Shipping</div>
                    <div class="hero-check"><i class="fas fa-check-circle"></i> 100% Secure</div>
                </div>

                <div class="hero-btns">
                    <a href="customer_login.php" class="btn-start">
                        <i class="fas fa-box"></i> Start Importing Now
                    </a>
                    <a href="track_order.php" class="btn-track">
                        <i class="fas fa-truck"></i> Track Your Order
                    </a>
                </div>

                <div class="social-proof">
                    <div class="avatars">
                        <span style="background:#4f46e5;">J</span>
                        <span style="background:#7c3aed;">A</span>
                        <span style="background:#ec4899;">M</span>
                        <span style="background:#f59e0b;">S</span>
                    </div>
                    <div class="proof-text">
                        <p>500+ Happy Customers</p>
                        <div class="stars">★★★★★</div>
                    </div>
                </div>
            </div>

            <!-- Hero Image -->
            <div class="col-lg-6 hero-img-col">
                <img src="image/background.png" alt="Import from China to Tanzania">
            </div>
        </div>
    </div>
</section>

<!-- ══ FEATURE CARDS ══ -->
<section class="features" id="features">
    <div class="container">
        <div class="row g-4">
            <div class="col-md-6 col-lg-3">
                <div class="feat-card">
                    <div class="feat-icon"><i class="fas fa-shopping-cart"></i></div>
                    <h5>Buy From Any Store in China</h5>
                    <p>We help you buy from Taobao, 1688, Alibaba &amp; more.</p>
                    <a href="customer_login.php" class="feat-link">Learn More &rarr;</a>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="feat-card">
                    <div class="feat-icon"><i class="fas fa-warehouse"></i></div>
                    <h5>Consolidation</h5>
                    <p>We receive, inspect and combine your items safely.</p>
                    <a href="#how" class="feat-link">Learn More &rarr;</a>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="feat-card">
                    <div class="feat-icon"><i class="fas fa-shipping-fast"></i></div>
                    <h5>Fast Shipping</h5>
                    <p>We ship by air or sea at very affordable rates.</p>
                    <a href="track_order.php" class="feat-link">Learn More &rarr;</a>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="feat-card">
                    <div class="feat-icon"><i class="fas fa-shield-alt"></i></div>
                    <h5>Safe Delivery</h5>
                    <p>Your goods are delivered safely to your location.</p>
                    <a href="#contact" class="feat-link">Learn More &rarr;</a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ══ HOW IT WORKS ══ -->
<section class="how-section" id="how">
    <div class="container">
        <div class="row g-5 align-items-center">
            <!-- Left text -->
            <div class="col-lg-4">
                <p class="section-label">HOW IT WORKS</p>
                <h2 class="section-title">Simple Steps to Get Your Products from China</h2>
                <p class="section-sub">We made the process simple, fast and transparent for you.</p>
                <a href="customer_login.php" class="btn-start mt-4 d-inline-flex">
                    <i class="fas fa-rocket"></i> Get Started
                </a>
            </div>

            <!-- Steps -->
            <div class="col-lg-8">
                <div class="steps-row mb-5">
                    <div class="step-item">
                        <div class="step-num">1</div>
                        <div class="step-icon-box"><i class="fas fa-shopping-basket"></i></div>
                        <h6>You Order</h6>
                        <p>Shop from any store in China and send us the links.</p>
                    </div>
                    <div class="step-item">
                        <div class="step-num">2</div>
                        <div class="step-icon-box"><i class="fas fa-warehouse"></i></div>
                        <h6>We Receive</h6>
                        <p>We receive your items at our warehouse in China.</p>
                    </div>
                    <div class="step-item">
                        <div class="step-num">3</div>
                        <div class="step-icon-box"><i class="fas fa-box-open"></i></div>
                        <h6>We Ship</h6>
                        <p>We package and ship your items to Tanzania.</p>
                    </div>
                    <div class="step-item">
                        <div class="step-num">4</div>
                        <div class="step-icon-box"><i class="fas fa-truck"></i></div>
                        <h6>We Deliver</h6>
                        <p>We clear customs and deliver to your doorstep.</p>
                    </div>
                </div>

                <!-- Stats -->
                <div class="stats-grid">
                    <div class="stat-item">
                        <div class="stat-icon-box" style="background:#ede9fe;color:#6d28d9;"><i class="fas fa-users"></i></div>
                        <div><p class="sval">500+</p><p class="slbl">Happy Customers</p></div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-icon-box" style="background:#dcfce7;color:#16a34a;"><i class="fas fa-box"></i></div>
                        <div><p class="sval">1000+</p><p class="slbl">Orders Delivered</p></div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-icon-box" style="background:#fef3c7;color:#d97706;"><i class="fas fa-star"></i></div>
                        <div><p class="sval">5+</p><p class="slbl">Years Experience</p></div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-icon-box" style="background:#dbeafe;color:#2563eb;"><i class="fas fa-smile"></i></div>
                        <div><p class="sval">98%</p><p class="slbl">Customer Satisfaction</p></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ══ FOOTER ══ -->
<footer id="contact">
    <div class="container">
        <div class="row g-4">
            <!-- Brand -->
            <div class="col-lg-4">
                <div class="footer-brand d-flex align-items-center gap-2 mb-2">
                    <div style="width:38px;height:38px;background:linear-gradient(135deg,#4f46e5,#7c3aed);border-radius:9px;display:flex;align-items:center;justify-content:center;padding:3px;flex-shrink:0;">
                        <img src="image/logo.png" style="width:32px;height:32px;object-fit:cover;border-radius:6px;" alt="logo">
                    </div>
                    <div>
                        <h3>AB FASHIONSTORE</h3>
                        <p>Blessings Collection</p>
                    </div>
                </div>
                <p class="footer-desc">Your trusted partner for importing quality products from China and delivering them safely to your doorstep in Tanzania.</p>
            </div>

            <!-- Quick Links -->
            <div class="col-lg-2 col-6">
                <h6>Quick Links</h6>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="#how">How It Works</a></li>
                    <li><a href="customer_login.php">My Orders</a></li>
                    <li><a href="track_order.php">Track Order</a></li>
                </ul>
            </div>

            <!-- Services -->
            <div class="col-lg-2 col-6">
                <h6>Services</h6>
                <ul>
                    <li><a href="#">Product Sourcing</a></li>
                    <li><a href="#">Consolidation</a></li>
                    <li><a href="#">Air Shipping</a></li>
                    <li><a href="#">Sea Shipping</a></li>
                </ul>
            </div>

            <!-- Contact -->
            <div class="col-lg-4">
                <h6>Contact Us</h6>
                <div class="footer-contact">
                    <p><i class="fas fa-map-marker-alt"></i> Dar es Salaam, Tanzania</p>
                    <p><i class="fas fa-phone"></i> <a href="tel:+255759431401" style="color:rgba(255,255,255,0.65);text-decoration:none;">+255 759 431 401</a></p>
                    <p><i class="fas fa-envelope"></i> <a href="mailto:benardkahayi@gmail.com" style="color:rgba(255,255,255,0.65);text-decoration:none;">benardkahayi@gmail.com</a></p>
                    <p><i class="fab fa-whatsapp"></i> <a href="https://wa.me/255759431401" style="color:rgba(255,255,255,0.65);text-decoration:none;">WhatsApp Us</a></p>
                </div>
            </div>
        </div>

        <hr class="footer-divider">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <p class="copyright mb-0">&copy; <?php echo date('Y'); ?> AB FASHIONSTORE — Blessings Collection. All rights reserved.</p>
            <div class="d-flex gap-3">
                <a href="#" style="color:rgba(255,255,255,0.4);font-size:12px;text-decoration:none;">Privacy Policy</a>
                <a href="#" style="color:rgba(255,255,255,0.4);font-size:12px;text-decoration:none;">Terms of Service</a>
            </div>
        </div>
    </div>
</footer>

<!-- ══ WHATSAPP FLOAT ══ -->
<a href="https://wa.me/255759431401" class="wa-float" target="_blank">
    <div class="wa-icon-side"><i class="fab fa-whatsapp"></i></div>
    <div class="wa-text-side">
        <strong>Chat on WhatsApp</strong>
        <span>We're online!</span>
    </div>
</a>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Smooth scroll
document.querySelectorAll('a[href^="#"]').forEach(a => {
    a.addEventListener('click', e => {
        const target = document.querySelector(a.getAttribute('href'));
        if (target) { e.preventDefault(); target.scrollIntoView({ behavior: 'smooth' }); }
    });
});
// Navbar shadow on scroll
window.addEventListener('scroll', () => {
    document.querySelector('.main-navbar').style.boxShadow =
        window.scrollY > 10 ? '0 4px 20px rgba(79,70,229,0.12)' : '0 2px 12px rgba(79,70,229,0.1)';
});
</script>

<!-- ══ AI CHATBOT WIDGET ══ -->
<style>
    /* Toggle button */
    .ai-toggle {
        position: fixed; bottom: 90px; right: 24px; z-index: 9998;
        width: 56px; height: 56px; border-radius: 50%;
        background: linear-gradient(135deg,#4f46e5,#7c3aed);
        border: none; cursor: pointer; display: flex; align-items: center; justify-content: center;
        box-shadow: 0 6px 24px rgba(79,70,229,0.4);
        transition: transform 0.3s, box-shadow 0.3s;
    }
    .ai-toggle:hover { transform: scale(1.08); box-shadow: 0 8px 30px rgba(79,70,229,0.5); }
    .ai-toggle i { color: white; font-size: 22px; }
    .ai-toggle .ai-badge {
        position: absolute; top: -2px; right: -2px;
        width: 18px; height: 18px; background: #22c55e; border-radius: 50%;
        border: 2.5px solid white; font-size: 9px; color: white; font-weight: 700;
        display: flex; align-items: center; justify-content: center;
    }

    /* Chat panel */
    .ai-panel {
        position: fixed; bottom: 158px; right: 24px; z-index: 9997;
        width: 360px; background: white; border-radius: 20px;
        box-shadow: 0 20px 60px rgba(79,70,229,0.18);
        display: flex; flex-direction: column; overflow: hidden;
        transition: all 0.35s cubic-bezier(0.34,1.56,0.64,1);
        transform-origin: bottom right;
    }
    .ai-panel.hidden {
        transform: scale(0.85); opacity: 0; pointer-events: none;
    }

    /* Panel header */
    .ai-header {
        background: linear-gradient(135deg,#4f46e5,#7c3aed);
        padding: 14px 18px; display: flex; align-items: center; gap: 12px;
    }
    .ai-avatar {
        width: 42px; height: 42px; border-radius: 50%;
        background: rgba(255,255,255,0.2); border: 2px solid rgba(255,255,255,0.4);
        display: flex; align-items: center; justify-content: center;
        font-size: 20px; flex-shrink: 0;
    }
    .ai-info h6 { color: white; font-size: 14px; font-weight: 700; margin: 0; }
    .ai-info span { color: rgba(255,255,255,0.8); font-size: 11.5px; display: flex; align-items: center; gap: 5px; }
    .ai-info span::before {
        content: ''; width: 7px; height: 7px; background: #22c55e;
        border-radius: 50%; display: inline-block;
    }
    .ai-close {
        margin-left: auto; background: rgba(255,255,255,0.15); border: none;
        width: 28px; height: 28px; border-radius: 8px; color: white;
        cursor: pointer; display: flex; align-items: center; justify-content: center;
        font-size: 14px; transition: background 0.2s;
    }
    .ai-close:hover { background: rgba(255,255,255,0.25); }

    /* Messages area */
    .ai-messages {
        height: 320px; overflow-y: auto; padding: 16px;
        display: flex; flex-direction: column; gap: 12px;
        scroll-behavior: smooth;
    }
    .ai-messages::-webkit-scrollbar { width: 4px; }
    .ai-messages::-webkit-scrollbar-track { background: transparent; }
    .ai-messages::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 4px; }

    /* Message bubbles */
    .msg { display: flex; gap: 8px; align-items: flex-end; max-width: 92%; }
    .msg.bot { align-self: flex-start; }
    .msg.user { align-self: flex-end; flex-direction: row-reverse; }
    .msg-icon {
        width: 28px; height: 28px; border-radius: 50%; flex-shrink: 0;
        background: linear-gradient(135deg,#4f46e5,#7c3aed);
        display: flex; align-items: center; justify-content: center;
        font-size: 13px; color: white;
    }
    .msg-bubble {
        padding: 10px 13px; border-radius: 16px;
        font-size: 13px; line-height: 1.5;
    }
    .msg.bot  .msg-bubble { background: #f1f5f9; color: #1e293b; border-bottom-left-radius: 4px; }
    .msg.user .msg-bubble { background: linear-gradient(135deg,#4f46e5,#7c3aed); color: white; border-bottom-right-radius: 4px; }
    .msg-time { font-size: 10px; color: #94a3b8; margin-top: 2px; display: block; }

    /* Typing indicator */
    .typing-indicator { display: flex; gap: 5px; align-items: center; padding: 10px 14px; }
    .typing-indicator span {
        width: 8px; height: 8px; background: #94a3b8; border-radius: 50%;
        animation: bounce-dot 1.2s infinite;
    }
    .typing-indicator span:nth-child(2) { animation-delay: 0.2s; }
    .typing-indicator span:nth-child(3) { animation-delay: 0.4s; }
    @keyframes bounce-dot {
        0%,60%,100% { transform: translateY(0); }
        30%          { transform: translateY(-6px); }
    }
    .typing-bubble { background: #f1f5f9; border-radius: 16px; border-bottom-left-radius: 4px; }

    /* Quick replies */
    .ai-quick { padding: 8px 14px; display: flex; gap: 6px; flex-wrap: wrap; border-top: 1px solid #f1f5f9; }
    .quick-btn {
        padding: 5px 11px; border: 1.5px solid #c4b5fd; border-radius: 20px;
        font-size: 11.5px; color: #5b21b6; background: #faf5ff; cursor: pointer;
        transition: all 0.2s; white-space: nowrap;
    }
    .quick-btn:hover { background: #ede9fe; border-color: #7c3aed; }

    /* Input area */
    .ai-input-area {
        padding: 12px 14px; border-top: 1px solid #e2e8f0;
        display: flex; gap: 8px; align-items: center;
    }
    .ai-input {
        flex: 1; border: 1.5px solid #e2e8f0; border-radius: 20px;
        padding: 9px 14px; font-size: 13px; outline: none;
        background: #f8fafc; transition: border 0.2s;
    }
    .ai-input:focus { border-color: #7c3aed; background: white; }
    .ai-send {
        width: 36px; height: 36px; border-radius: 50%; border: none;
        background: linear-gradient(135deg,#4f46e5,#7c3aed);
        color: white; cursor: pointer; display: flex; align-items: center;
        justify-content: center; font-size: 14px; transition: opacity 0.2s; flex-shrink: 0;
    }
    .ai-send:hover { opacity: 0.88; }
    .ai-send:disabled { opacity: 0.5; cursor: not-allowed; }

    /* Footer note */
    .ai-footer-note {
        text-align: center; padding: 6px 0 10px;
        font-size: 10.5px; color: #94a3b8;
    }

    @media (max-width: 480px) {
        .ai-panel { width: calc(100vw - 20px); right: 10px; }
        .ai-toggle { right: 14px; bottom: 80px; }
    }
</style>

<!-- Toggle button -->
<button class="ai-toggle" onclick="toggleChat()" id="aiToggle" title="Chat with AI Assistant">
    <i class="fas fa-robot" id="aiToggleIcon"></i>
    <span class="ai-badge">AI</span>
</button>

<!-- Chat panel -->
<div class="ai-panel hidden" id="aiPanel">
    <div class="ai-header">
        <div class="ai-avatar">🤖</div>
        <div class="ai-info">
            <h6>Amina — AI Assistant</h6>
            <span>Online · Replies instantly</span>
        </div>
        <button class="ai-close" onclick="toggleChat()"><i class="fas fa-times"></i></button>
    </div>

    <div class="ai-messages" id="aiMessages">
        <!-- Welcome message -->
        <div class="msg bot">
            <div class="msg-icon"><i class="fas fa-robot"></i></div>
            <div>
                <div class="msg-bubble">
                    Habari! 👋 I'm <strong>Amina</strong>, your AI assistant at AB FASHIONSTORE. How can I help you today?
                </div>
                <span class="msg-time">Just now</span>
            </div>
        </div>
    </div>

    <!-- Quick reply chips -->
    <div class="ai-quick" id="quickReplies">
        <button class="quick-btn" onclick="sendQuick('How does shipping work?')">📦 How it works</button>
        <button class="quick-btn" onclick="sendQuick('How long does delivery take?')">⏱ Delivery time</button>
        <button class="quick-btn" onclick="sendQuick('How do I track my order?')">🔍 Track order</button>
        <button class="quick-btn" onclick="sendQuick('What payment methods do you accept?')">💳 Payments</button>
    </div>

    <div class="ai-input-area">
        <input type="text" class="ai-input" id="aiInput"
               placeholder="Type your question..." maxlength="500"
               onkeydown="if(event.key==='Enter')sendMessage()">
        <button class="ai-send" id="aiSend" onclick="sendMessage()">
            <i class="fas fa-paper-plane"></i>
        </button>
    </div>
    <div class="ai-footer-note">Powered by Claude AI</div>
</div>

<script>
let chatOpen   = false;
let isTyping   = false;
let history    = [];

function toggleChat() {
    chatOpen = !chatOpen;
    const panel  = document.getElementById('aiPanel');
    const icon   = document.getElementById('aiToggleIcon');
    panel.classList.toggle('hidden', !chatOpen);
    icon.className = chatOpen ? 'fas fa-times' : 'fas fa-robot';
    if (chatOpen) {
        setTimeout(() => document.getElementById('aiInput').focus(), 300);
        scrollBottom();
    }
}

function getTime() {
    return new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}

function scrollBottom() {
    const el = document.getElementById('aiMessages');
    el.scrollTop = el.scrollHeight;
}

function appendMsg(role, text) {
    const el   = document.getElementById('aiMessages');
    const div  = document.createElement('div');
    div.className = `msg ${role}`;
    div.innerHTML = role === 'bot'
        ? `<div class="msg-icon"><i class="fas fa-robot"></i></div>
           <div><div class="msg-bubble">${text}</div><span class="msg-time">${getTime()}</span></div>`
        : `<div><div class="msg-bubble">${text}</div><span class="msg-time" style="text-align:right;">${getTime()}</span></div>`;
    el.appendChild(div);
    scrollBottom();
    return div;
}

function showTyping() {
    const el  = document.getElementById('aiMessages');
    const div = document.createElement('div');
    div.className = 'msg bot'; div.id = 'typingMsg';
    div.innerHTML = `<div class="msg-icon"><i class="fas fa-robot"></i></div>
                     <div class="msg-bubble typing-bubble">
                       <div class="typing-indicator">
                         <span></span><span></span><span></span>
                       </div>
                     </div>`;
    el.appendChild(div); scrollBottom();
}

function removeTyping() {
    const t = document.getElementById('typingMsg');
    if (t) t.remove();
}

function hideQuickReplies() {
    const q = document.getElementById('quickReplies');
    if (q) q.style.display = 'none';
}

async function sendMessage() {
    if (isTyping) return;
    const input = document.getElementById('aiInput');
    const text  = input.value.trim();
    if (!text) return;

    hideQuickReplies();
    input.value = '';
    appendMsg('user', escapeHtml(text));
    history.push({ role: 'user', content: text });

    isTyping = true;
    document.getElementById('aiSend').disabled = true;
    showTyping();

    try {
        const res = await fetch('chatbot.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ message: text, history: history.slice(-10) })
        });
        const data = await res.json();
        removeTyping();

        if (data.error) {
            appendMsg('bot', '⚠️ Sorry, something went wrong. Please <a href="https://wa.me/255759431401" target="_blank" style="color:#4f46e5;">contact us on WhatsApp</a>.');
        } else {
            const reply = data.reply;
            appendMsg('bot', formatReply(reply));
            history.push({ role: 'assistant', content: reply });
        }
    } catch (err) {
        removeTyping();
        appendMsg('bot', '⚠️ Connection error. Please check your internet or <a href="https://wa.me/255759431401" target="_blank" style="color:#4f46e5;">chat on WhatsApp</a>.');
    }

    isTyping = false;
    document.getElementById('aiSend').disabled = false;
    input.focus();
}

function sendQuick(text) {
    document.getElementById('aiInput').value = text;
    sendMessage();
}

function escapeHtml(t) {
    return t.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

function formatReply(text) {
    // Convert markdown-like bold and newlines
    return text
        .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
        .replace(/\n/g, '<br>');
}
</script>
</body>
</html>
