<?php
session_start();
require_once __DIR__ . '/db.php';

$stat_customers = 0; $stat_orders = 0; $stat_items = 0; $stat_payments = 0;
try {
    $stat_customers = (int) $conn->query("SELECT COUNT(*) FROM customers")->fetchColumn();
    $stat_orders    = (int) $conn->query("SELECT COUNT(*) FROM orders")->fetchColumn();
    $stat_items     = (int) $conn->query("SELECT COALESCE(SUM(quantity),0) FROM orders")->fetchColumn();
    $stat_payments  = (int) $conn->query("SELECT COUNT(*) FROM payments WHERE status='completed'")->fetchColumn();
} catch (Throwable $e) {}

$is_customer = isset($_SESSION['role']) && $_SESSION['role'] === 'customer';
$wa_number   = '255759431401';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<meta name="theme-color" content="#0b1b33">
<meta name="description" content="AB FASHIONSTORE imports products from China to Tanzania — sourcing, purchasing, air and sea shipping, and customs clearance.">
<title>AB FASHIONSTORE — Import from China to Tanzania</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
:root{
  --navy:#0b1b33;--navy-2:#132a4f;--navy-3:#1c3a68;
  --blue:#2563eb;--blue-2:#1d4ed8;--sky:#eaf1ff;
  --amber:#f59e0b;--amber-soft:#fff4dc;
  --ink:#0f172a;--text:#334155;--muted:#64748b;
  --line:#e2e8f0;--bg:#f6f8fc;--ok:#16a34a;--wa:#25d366;
  --radius:18px;--nav-h:72px;
  --shadow:0 20px 50px -24px rgba(11,27,51,.35);
}
*{margin:0;padding:0;box-sizing:border-box}
html{scroll-behavior:smooth;-webkit-text-size-adjust:100%;scroll-padding-top:calc(var(--nav-h) + 12px)}
body{
  font-family:'Plus Jakarta Sans',system-ui,sans-serif;color:var(--ink);background:#fff;
  overflow-x:hidden;-webkit-font-smoothing:antialiased;
  padding-left:env(safe-area-inset-left);padding-right:env(safe-area-inset-right);
}
img{max-width:100%;display:block}
a{text-decoration:none}
button{font-family:inherit}
.container{max-width:1180px}

/* ── Buttons ── */
.btn-x{
  display:inline-flex;align-items:center;justify-content:center;gap:9px;
  padding:13px 22px;border-radius:12px;font-size:14.5px;font-weight:700;
  min-height:48px;border:1px solid transparent;background:transparent;color:inherit;
  transition:all .2s;cursor:pointer;white-space:nowrap;
}
.btn-x.primary{background:var(--blue);color:#fff}
.btn-x.primary:hover{background:var(--blue-2);color:#fff;transform:translateY(-1px)}
.btn-x.amber{background:var(--amber);color:var(--navy)}
.btn-x.amber:hover{background:#fbbf24;color:var(--navy);transform:translateY(-1px)}
.btn-x.ghost-light{border-color:rgba(255,255,255,.28);color:#fff}
.btn-x.ghost-light:hover{background:rgba(255,255,255,.1);color:#fff}
.btn-x.ghost{border-color:var(--line);color:var(--ink);background:#fff}
.btn-x.ghost:hover{border-color:var(--ink);color:var(--ink)}
.btn-x.sm{padding:9px 16px;min-height:42px;font-size:13.5px;border-radius:10px}

/* ── Top bar ── */
.top-bar{background:var(--navy);color:rgba(255,255,255,.7);font-size:12.5px;padding:8px 0}
.top-bar a{color:rgba(255,255,255,.85)}
.top-bar a:hover{color:#fff}
.top-bar .tb-items{display:flex;gap:6px 22px;flex-wrap:wrap;align-items:center}
.top-bar i{color:var(--amber);margin-right:6px}
.social-icons{display:flex;gap:6px}
.social-icons a{
  width:26px;height:26px;border-radius:50%;background:rgba(255,255,255,.08);
  display:inline-flex;align-items:center;justify-content:center;font-size:11px;transition:background .2s;
}
.social-icons a i{color:#fff;margin:0}
.social-icons a:hover{background:var(--blue)}

/* ── Navbar ── */
.main-navbar{
  position:sticky;top:0;z-index:1000;background:rgba(255,255,255,.94);
  backdrop-filter:saturate(180%) blur(12px);-webkit-backdrop-filter:saturate(180%) blur(12px);
  border-bottom:1px solid var(--line);
}
.main-navbar .container{height:var(--nav-h);display:flex;align-items:center;gap:24px}
.brand{display:flex;align-items:center;gap:11px;flex-shrink:0;color:var(--ink)}
.brand-icon{width:44px;height:44px;border-radius:12px;overflow:hidden;background:#fff;border:1px solid var(--line);padding:3px;flex-shrink:0}
.brand-icon img{width:100%;height:100%;object-fit:cover;border-radius:8px}
.brand-txt h3{font-size:15.5px;font-weight:800;letter-spacing:-.01em;line-height:1.1;margin:0;white-space:nowrap;color:var(--navy)}
.brand-txt p{font-size:10.5px;color:var(--muted);margin:2px 0 0;letter-spacing:.12em;text-transform:uppercase;font-weight:600}
.nav-links{display:flex;align-items:center;gap:2px;flex:1;justify-content:center}
.nav-links a{padding:9px 14px;font-size:14px;font-weight:600;color:var(--text);border-radius:9px;transition:all .2s}
.nav-links a:hover,.nav-links a.active{color:var(--blue);background:var(--sky)}
.nav-actions{display:flex;align-items:center;gap:10px;flex-shrink:0;margin-left:auto}
.nav-toggle{
  display:none;width:44px;height:44px;border-radius:11px;border:1px solid var(--line);background:#fff;
  align-items:center;justify-content:center;cursor:pointer;color:var(--ink);font-size:17px;flex-shrink:0;
}

/* ── Drawer ── */
.drawer-backdrop{position:fixed;inset:0;background:rgba(11,27,51,.55);backdrop-filter:blur(3px);z-index:1998;opacity:0;pointer-events:none;transition:opacity .3s}
.drawer-backdrop.open{opacity:1;pointer-events:auto}
.drawer{
  position:fixed;top:0;right:0;bottom:0;width:min(320px,86vw);background:#fff;z-index:1999;
  transform:translateX(100%);transition:transform .32s cubic-bezier(.4,0,.2,1);
  display:flex;flex-direction:column;box-shadow:-20px 0 60px -20px rgba(11,27,51,.4);
  padding-top:env(safe-area-inset-top);padding-bottom:env(safe-area-inset-bottom);
}
.drawer.open{transform:translateX(0)}
.drawer-head{padding:18px 20px;border-bottom:1px solid var(--line);display:flex;align-items:center;justify-content:space-between}
.drawer-close{width:40px;height:40px;border-radius:10px;border:1px solid var(--line);background:#fff;cursor:pointer;font-size:16px;color:var(--ink)}
.drawer-body{padding:12px;flex:1;overflow-y:auto}
.drawer-body a{display:flex;align-items:center;gap:12px;padding:13px 14px;border-radius:11px;color:var(--text);font-size:15px;font-weight:600;min-height:48px}
.drawer-body a i{width:20px;text-align:center;color:var(--muted)}
.drawer-body a:hover,.drawer-body a.active{background:var(--sky);color:var(--blue)}
.drawer-body a:hover i,.drawer-body a.active i{color:var(--blue)}
.drawer-foot{padding:16px 18px 20px;border-top:1px solid var(--line);display:flex;flex-direction:column;gap:10px}
.drawer-foot .btn-x{width:100%}

/* ── Hero ── */
.hero{
  position:relative;overflow:hidden;color:#fff;
  background:
    radial-gradient(900px 480px at 88% 0%,rgba(37,99,235,.45),transparent 60%),
    radial-gradient(700px 420px at 0% 100%,rgba(245,158,11,.14),transparent 60%),
    linear-gradient(160deg,var(--navy) 0%,var(--navy-2) 100%);
  padding:clamp(48px,7vw,88px) 0 clamp(56px,7vw,96px);
}
.hero::before{
  content:'';position:absolute;inset:0;pointer-events:none;opacity:.5;
  background-image:linear-gradient(rgba(255,255,255,.04) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.04) 1px,transparent 1px);
  background-size:44px 44px;
  mask-image:radial-gradient(ellipse at 70% 30%,#000 20%,transparent 75%);
  -webkit-mask-image:radial-gradient(ellipse at 70% 30%,#000 20%,transparent 75%);
}
.hero .container{position:relative}
.hero-tag{
  display:inline-flex;align-items:center;gap:8px;padding:7px 14px 7px 8px;border-radius:100px;
  background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.14);
  font-size:13px;font-weight:600;color:rgba(255,255,255,.9);margin-bottom:22px;
}
.hero-tag .dot{background:var(--amber);color:var(--navy);border-radius:100px;padding:2px 9px;font-size:11px;font-weight:800;letter-spacing:.04em}
.hero h1{font-size:clamp(34px,5.4vw,60px);font-weight:800;line-height:1.05;letter-spacing:-.035em;margin-bottom:20px}
.hero h1 .hl{color:var(--amber)}
.hero-sub{font-size:clamp(15px,1.5vw,17px);line-height:1.7;color:rgba(255,255,255,.75);max-width:540px;margin-bottom:28px}
.hero-btns{display:flex;gap:12px;flex-wrap:wrap;margin-bottom:34px}
.hero-checks{display:flex;gap:10px 22px;flex-wrap:wrap}
.hero-check{display:flex;align-items:center;gap:8px;font-size:13.5px;font-weight:600;color:rgba(255,255,255,.85)}
.hero-check i{color:#4ade80}

.hero-visual{position:relative;max-width:470px;margin-left:auto}
.hero-photo{border-radius:24px;overflow:hidden;box-shadow:0 40px 80px -30px rgba(0,0,0,.6);border:1px solid rgba(255,255,255,.12)}
.hero-photo img{width:100%;aspect-ratio:4/4.3;object-fit:cover}
.track-card{
  position:absolute;left:-36px;right:36px;bottom:-34px;
  background:#fff;color:var(--ink);border-radius:18px;padding:18px;box-shadow:var(--shadow);
}
.track-card h6{font-size:14px;font-weight:800;margin:0 0 3px;display:flex;align-items:center;gap:8px}
.track-card h6 i{color:var(--blue)}
.track-card p{font-size:12.5px;color:var(--muted);margin:0 0 12px}
.track-form{display:flex;gap:8px}
.track-form input{
  flex:1;min-width:0;border:1.5px solid var(--line);border-radius:11px;padding:11px 14px;
  font-size:15px;font-family:inherit;outline:none;background:var(--bg);min-height:46px;
}
.track-form input:focus{border-color:var(--blue);background:#fff}
.live-pill{
  position:absolute;top:18px;right:-14px;background:#fff;color:var(--ink);border-radius:14px;
  padding:10px 14px;display:flex;align-items:center;gap:10px;box-shadow:var(--shadow);
}
.live-pill .ic{width:36px;height:36px;border-radius:10px;background:var(--amber-soft);color:#b45309;display:flex;align-items:center;justify-content:center}
.live-pill strong{display:block;font-size:17px;font-weight:800;line-height:1}
.live-pill span{font-size:11.5px;color:var(--muted);font-weight:600}

/* ── Stats strip ── */
.stats-strip{background:#fff;border-bottom:1px solid var(--line);position:relative;z-index:2}
.stats-strip .row > div{padding-top:30px;padding-bottom:30px}
.stat-box{display:flex;align-items:center;gap:14px}
.stat-box .ic{width:48px;height:48px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:19px;flex-shrink:0}
.stat-box strong{display:block;font-size:clamp(20px,2.4vw,26px);font-weight:800;letter-spacing:-.02em;line-height:1.1;color:var(--navy)}
.stat-box span{font-size:12.5px;color:var(--muted);font-weight:600}

/* ── Platforms ── */
.platforms{padding:28px 0;background:var(--bg);border-bottom:1px solid var(--line)}
.platforms .lbl{font-size:12px;font-weight:700;color:var(--muted);letter-spacing:.14em;text-transform:uppercase}
.platform-list{display:flex;gap:10px;flex-wrap:wrap}
.platform{padding:9px 16px;border-radius:100px;background:#fff;border:1px solid var(--line);font-size:13.5px;font-weight:700;color:var(--text)}

/* ── Sections ── */
.section{padding:clamp(60px,8vw,104px) 0}
.section.alt{background:var(--bg)}
.eyebrow{display:inline-flex;align-items:center;gap:8px;font-size:12.5px;font-weight:800;color:var(--blue);letter-spacing:.14em;text-transform:uppercase;margin-bottom:12px}
.eyebrow::before{content:'';width:18px;height:2px;background:currentColor;border-radius:2px}
.section-title{font-size:clamp(27px,3.6vw,42px);font-weight:800;line-height:1.12;letter-spacing:-.03em;color:var(--navy);margin-bottom:14px}
.section-sub{font-size:clamp(14.5px,1.5vw,16.5px);color:var(--muted);line-height:1.7;max-width:560px}
.section-head{margin-bottom:clamp(30px,4vw,48px)}
.section-head.center{text-align:center}
.section-head.center .section-sub{margin:0 auto}

/* Services */
.svc-card{
  height:100%;background:#fff;border:1px solid var(--line);border-radius:var(--radius);padding:26px 24px;
  transition:transform .25s,box-shadow .25s,border-color .25s;position:relative;overflow:hidden;
}
.svc-card:hover{transform:translateY(-4px);box-shadow:var(--shadow);border-color:transparent}
.svc-card .ic{width:54px;height:54px;border-radius:15px;display:flex;align-items:center;justify-content:center;font-size:22px;margin-bottom:18px}
.svc-card h5{font-size:17.5px;font-weight:800;color:var(--navy);margin-bottom:8px;letter-spacing:-.01em}
.svc-card p{font-size:14px;color:var(--muted);line-height:1.65;margin:0}
.svc-card .num{position:absolute;top:18px;right:22px;font-size:13px;font-weight:800;color:#cbd5e1}

/* Shipping options */
.ship-card{
  height:100%;border-radius:22px;padding:30px 28px;position:relative;overflow:hidden;
  border:1px solid var(--line);background:#fff;display:flex;flex-direction:column;
}
.ship-card.dark{background:linear-gradient(160deg,var(--navy),var(--navy-3));color:#fff;border-color:transparent}
.ship-card .head{display:flex;align-items:center;gap:14px;margin-bottom:22px}
.ship-card .head .ic{width:52px;height:52px;border-radius:15px;display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0}
.ship-card h4{font-size:21px;font-weight:800;margin:0;letter-spacing:-.02em}
.ship-card .tag{font-size:12px;font-weight:700;opacity:.7}
.ship-price{font-size:clamp(34px,4vw,44px);font-weight:800;letter-spacing:-.03em;line-height:1}
.ship-price small{font-size:15px;font-weight:600;opacity:.65;letter-spacing:0}
.ship-meta{display:flex;gap:8px;flex-wrap:wrap;margin:16px 0 20px}
.ship-meta span{font-size:12.5px;font-weight:700;padding:6px 12px;border-radius:100px;background:var(--sky);color:var(--blue-2)}
.ship-card.dark .ship-meta span{background:rgba(255,255,255,.1);color:#fff}
.ship-list{list-style:none;padding:0;margin:0 0 24px;display:flex;flex-direction:column;gap:10px}
.ship-list li{display:flex;gap:10px;font-size:14px;line-height:1.5}
.ship-list li i{margin-top:3px;color:var(--ok)}
.ship-card.dark .ship-list li i{color:#4ade80}
.ship-card.dark .ship-list li{color:rgba(255,255,255,.85)}
.ship-card .btn-x{margin-top:auto;align-self:flex-start}
.ship-note{font-size:13px;color:var(--muted);text-align:center;margin-top:22px}

/* Steps */
.steps{display:grid;grid-template-columns:repeat(4,1fr);gap:20px;position:relative;counter-reset:step}
.steps::before{content:'';position:absolute;top:31px;left:12%;right:12%;height:2px;background:repeating-linear-gradient(90deg,#cbd5e1 0 8px,transparent 8px 16px)}
.step{position:relative;text-align:center;padding:0 6px}
.step .circle{
  width:64px;height:64px;border-radius:50%;margin:0 auto 18px;background:#fff;border:2px solid var(--line);
  display:flex;align-items:center;justify-content:center;font-size:22px;color:var(--blue);position:relative;
  box-shadow:0 10px 24px -14px rgba(11,27,51,.4);
}
.step .circle::after{
  counter-increment:step;content:counter(step);position:absolute;top:-4px;right:-4px;
  width:24px;height:24px;border-radius:50%;background:var(--navy);color:#fff;font-size:12px;font-weight:800;
  display:flex;align-items:center;justify-content:center;border:2px solid #fff;
}
.step h6{font-size:16px;font-weight:800;color:var(--navy);margin-bottom:6px}
.step p{font-size:13.5px;color:var(--muted);line-height:1.6;margin:0}

/* AI band */
.ai-band{
  border-radius:28px;padding:clamp(28px,5vw,56px);color:#fff;position:relative;overflow:hidden;
  background:radial-gradient(600px 300px at 100% 0%,rgba(37,99,235,.55),transparent 65%),linear-gradient(150deg,var(--navy),var(--navy-2));
}
.ai-band h2{font-size:clamp(26px,3.4vw,38px);font-weight:800;letter-spacing:-.03em;line-height:1.15;margin-bottom:12px}
.ai-band p{color:rgba(255,255,255,.75);font-size:15.5px;line-height:1.7;max-width:520px}
.ai-chips{display:flex;flex-wrap:wrap;gap:10px;margin:22px 0 26px}
.ai-chip{
  border:1px solid rgba(255,255,255,.2);background:rgba(255,255,255,.07);color:#fff;
  padding:10px 15px;border-radius:12px;font-size:13.5px;font-weight:600;cursor:pointer;transition:all .2s;text-align:left;
}
.ai-chip:hover{background:rgba(255,255,255,.16);border-color:rgba(255,255,255,.4)}
.ai-preview{background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.12);border-radius:20px;padding:18px;display:flex;flex-direction:column;gap:12px}
.ai-preview .b{max-width:88%;padding:11px 14px;border-radius:15px;font-size:13.5px;line-height:1.55}
.ai-preview .b.u{align-self:flex-end;background:var(--blue);border-bottom-right-radius:5px}
.ai-preview .b.a{align-self:flex-start;background:#fff;color:var(--ink);border-bottom-left-radius:5px}
.ai-preview .who{font-size:11px;font-weight:700;opacity:.6;margin-bottom:-6px}
.lang-badges{display:flex;gap:8px;flex-wrap:wrap}
.lang-badges span{font-size:12px;font-weight:700;padding:5px 11px;border-radius:100px;background:rgba(255,255,255,.1)}

/* CTA */
.cta{
  background:var(--amber);border-radius:24px;padding:clamp(26px,4vw,44px);
  display:flex;align-items:center;justify-content:space-between;gap:20px;flex-wrap:wrap;
}
.cta h3{font-size:clamp(22px,2.8vw,30px);font-weight:800;color:var(--navy);letter-spacing:-.02em;margin:0 0 6px}
.cta p{margin:0;color:#4a3a14;font-weight:500}
.cta .btns{display:flex;gap:10px;flex-wrap:wrap}
.btn-x.navy{background:var(--navy);color:#fff}
.btn-x.navy:hover{background:var(--navy-3);color:#fff}
.btn-x.wa{background:#fff;color:#065f46}
.btn-x.wa:hover{background:#ecfdf5;color:#065f46}

/* Footer */
footer{background:var(--navy);color:rgba(255,255,255,.65);padding:clamp(48px,7vw,72px) 0 26px}
footer .brand-txt h3{color:#fff}
footer .brand-txt p{color:rgba(255,255,255,.45)}
.footer-desc{font-size:14px;line-height:1.75;margin-top:16px;color:rgba(255,255,255,.6);max-width:340px}
footer h6{color:#fff;font-size:13px;font-weight:800;margin-bottom:16px;letter-spacing:.1em;text-transform:uppercase}
footer ul{list-style:none;padding:0;margin:0}
footer ul li a{color:rgba(255,255,255,.6);font-size:14px;display:inline-block;padding:5px 0;transition:color .2s}
footer ul li a:hover{color:#fff}
.footer-contact p{font-size:14px;margin-bottom:10px;display:flex;align-items:center;gap:10px;word-break:break-word}
.footer-contact i{color:var(--amber);width:16px;flex-shrink:0}
.footer-contact a{color:rgba(255,255,255,.7)}
.footer-contact a:hover{color:#fff}
.footer-bottom{border-top:1px solid rgba(255,255,255,.08);margin-top:40px;padding-top:20px;display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;font-size:12.5px;color:rgba(255,255,255,.4)}
.footer-bottom a{color:rgba(255,255,255,.45)}

/* ── Floating buttons ── */
.wa-float,.ai-toggle{
  position:fixed;right:calc(20px + env(safe-area-inset-right));z-index:9990;
  width:56px;height:56px;border-radius:50%;border:none;cursor:pointer;
  display:flex;align-items:center;justify-content:center;transition:transform .2s;
}
.wa-float{bottom:calc(22px + env(safe-area-inset-bottom));background:var(--wa);color:#fff;font-size:26px;box-shadow:0 12px 28px -8px rgba(37,211,102,.6)}
.wa-float:hover{transform:translateY(-2px);color:#fff}
.ai-toggle{bottom:calc(90px + env(safe-area-inset-bottom));background:var(--blue);color:#fff;font-size:21px;box-shadow:0 14px 30px -8px rgba(37,99,235,.6);z-index:9998}
.ai-toggle:hover{transform:translateY(-2px)}
.ai-toggle .ai-badge{
  position:absolute;top:-3px;right:-3px;min-width:22px;height:22px;padding:0 4px;background:var(--amber);color:var(--navy);
  border-radius:100px;border:2px solid #fff;font-size:9.5px;font-weight:800;display:flex;align-items:center;justify-content:center;
}
.ai-hint{
  position:fixed;right:calc(88px + env(safe-area-inset-right));bottom:calc(98px + env(safe-area-inset-bottom));z-index:9990;
  background:#fff;color:var(--ink);font-size:13px;font-weight:600;padding:10px 14px;border-radius:14px 14px 4px 14px;
  box-shadow:var(--shadow);border:1px solid var(--line);max-width:220px;animation:pop .4s ease;
}
.ai-hint button{border:none;background:none;color:var(--muted);margin-left:6px;cursor:pointer;font-size:12px}
@keyframes pop{from{transform:translateY(8px);opacity:0}to{transform:none;opacity:1}}

/* ── AI chat panel ── */
.ai-panel{
  position:fixed;bottom:calc(158px + env(safe-area-inset-bottom));right:calc(20px + env(safe-area-inset-right));z-index:9997;
  width:min(390px,calc(100vw - 40px));height:min(600px,calc(100vh - 190px));
  background:#fff;border-radius:22px;overflow:hidden;border:1px solid var(--line);
  box-shadow:0 30px 80px -20px rgba(11,27,51,.4);display:flex;flex-direction:column;
  transition:transform .3s cubic-bezier(.34,1.4,.64,1),opacity .2s;transform-origin:bottom right;
}
.ai-panel.hidden{transform:scale(.92) translateY(10px);opacity:0;pointer-events:none;visibility:hidden}
.ai-header{background:linear-gradient(140deg,var(--navy),var(--navy-3));padding:14px 14px 14px 16px;display:flex;align-items:center;gap:11px;flex-shrink:0}
.ai-avatar{width:42px;height:42px;border-radius:13px;flex-shrink:0;background:linear-gradient(135deg,var(--blue),#60a5fa);color:#fff;display:flex;align-items:center;justify-content:center;font-size:18px}
.ai-info{min-width:0;flex:1}
.ai-info h6{color:#fff;font-size:15px;font-weight:800;margin:0}
.ai-status{color:rgba(255,255,255,.7);font-size:11.5px;display:flex;align-items:center;gap:6px;margin-top:2px}
.ai-status::before{content:'';width:7px;height:7px;border-radius:50%;background:#94a3b8}
.ai-status.online::before{background:#4ade80}
.ai-status.offline::before{background:#f87171}
.ai-hbtn{background:rgba(255,255,255,.1);border:none;width:36px;height:36px;border-radius:10px;color:#fff;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:14px;flex-shrink:0}
.ai-hbtn:hover{background:rgba(255,255,255,.22)}
.ai-lang{display:flex;background:rgba(255,255,255,.1);border-radius:10px;padding:3px;flex-shrink:0}
.ai-lang button{border:none;background:none;color:rgba(255,255,255,.75);font-size:11.5px;font-weight:800;padding:6px 9px;border-radius:8px;cursor:pointer;min-width:34px}
.ai-lang button.on{background:#fff;color:var(--navy)}

.ai-messages{flex:1;overflow-y:auto;padding:18px 14px;display:flex;flex-direction:column;gap:12px;background:var(--bg);overscroll-behavior:contain}
.msg{display:flex;gap:8px;align-items:flex-end;max-width:92%}
.msg.bot{align-self:flex-start}
.msg.user{align-self:flex-end;flex-direction:row-reverse}
.msg-icon{width:28px;height:28px;border-radius:9px;flex-shrink:0;background:var(--navy);color:#fff;display:flex;align-items:center;justify-content:center;font-size:12px}
.msg-bubble{padding:11px 14px;border-radius:16px;font-size:14px;line-height:1.55;overflow-wrap:anywhere}
.msg.bot .msg-bubble{background:#fff;color:var(--ink);border:1px solid var(--line);border-bottom-left-radius:5px}
.msg.user .msg-bubble{background:var(--blue);color:#fff;border-bottom-right-radius:5px}
.msg-time{font-size:10.5px;color:var(--muted);margin-top:4px;display:block}
.msg.user .msg-time{text-align:right}
.handoff{
  margin-top:8px;display:flex;align-items:center;gap:8px;padding:9px 12px;border-radius:12px;
  background:#ecfdf5;border:1px solid #bbf7d0;color:#065f46;font-size:13px;font-weight:700;
}
.handoff:hover{background:#d1fae5;color:#065f46}
.handoff i{font-size:17px;color:var(--wa)}
.typing{display:flex;gap:5px;padding:4px 2px}
.typing span{width:7px;height:7px;background:var(--muted);border-radius:50%;animation:bounce 1.2s infinite}
.typing span:nth-child(2){animation-delay:.2s}
.typing span:nth-child(3){animation-delay:.4s}
@keyframes bounce{0%,60%,100%{transform:translateY(0);opacity:.5}30%{transform:translateY(-5px);opacity:1}}
.ai-quick{padding:10px 12px;display:flex;gap:7px;overflow-x:auto;border-top:1px solid var(--line);background:#fff;flex-shrink:0;scrollbar-width:none}
.ai-quick::-webkit-scrollbar{display:none}
.quick-btn{padding:8px 12px;border:1px solid var(--line);border-radius:100px;font-size:12.5px;color:var(--text);background:var(--bg);cursor:pointer;white-space:nowrap;font-weight:600;min-height:36px;flex-shrink:0}
.quick-btn:hover{border-color:var(--blue);color:var(--blue);background:#fff}
.ai-input-area{padding:10px 12px;border-top:1px solid var(--line);display:flex;gap:8px;align-items:flex-end;background:#fff;flex-shrink:0}
.ai-input{
  flex:1;border:1.5px solid var(--line);border-radius:14px;padding:11px 14px;font-size:16px;line-height:1.4;
  outline:none;background:var(--bg);font-family:inherit;min-height:46px;max-height:120px;resize:none;
}
.ai-input:focus{border-color:var(--blue);background:#fff}
.ai-send{width:46px;height:46px;border-radius:13px;border:none;background:var(--blue);color:#fff;cursor:pointer;font-size:15px;flex-shrink:0}
.ai-send:hover{background:var(--blue-2)}
.ai-send:disabled{opacity:.4;cursor:not-allowed}
.ai-footer-note{text-align:center;padding:0 12px 10px;font-size:11px;color:var(--muted);background:#fff;flex-shrink:0}

/* ═════ Tablet ═════ */
@media (max-width:991px){
  .nav-links{display:none}
  .nav-toggle{display:inline-flex}
  .nav-actions .btn-x{display:none}
  .hero-visual{margin:48px auto 34px;max-width:440px}
  .track-card{left:16px;right:16px}
  .live-pill{right:12px}
  .steps{grid-template-columns:1fr 1fr;gap:32px 20px}
  .steps::before{display:none}
  .ai-preview{margin-top:26px}
}

/* ═════ Phone ═════ */
@media (max-width:640px){
  :root{--nav-h:64px}
  .top-bar{font-size:12px;text-align:center}
  .top-bar .tb-loc,.top-bar .social-icons{display:none !important}
  .top-bar .tb-items{justify-content:center;width:100%;gap:4px 14px}
  .brand-icon{width:40px;height:40px}
  .brand-txt h3{font-size:14px}

  .hero{padding:36px 0 44px}
  .hero-tag{font-size:12px}
  .hero h1{font-size:clamp(31px,9vw,40px)}
  .hero-btns{flex-direction:column}
  .hero-btns .btn-x{width:100%}
  .hero-checks{gap:8px 16px}
  .hero-check{font-size:13px}
  .hero-visual{margin:36px auto 30px}
  .hero-photo img{aspect-ratio:5/4}
  .live-pill{top:12px;right:10px;padding:8px 11px}
  .live-pill .ic{width:32px;height:32px}
  .live-pill strong{font-size:15px}
  .track-card{position:relative;left:auto;right:auto;bottom:auto;margin:-40px 12px 0;padding:16px}
  .track-form{flex-direction:column}
  .track-form .btn-x{width:100%}

  .stats-strip .row > div{padding-top:18px;padding-bottom:18px}
  .stat-box{gap:10px}
  .stat-box .ic{width:40px;height:40px;font-size:16px;border-radius:12px}
  .stat-box span{font-size:11.5px}

  .platforms .lbl{display:block;text-align:center;margin-bottom:12px}
  .platform-list{justify-content:center;gap:8px}
  .platform{font-size:12.5px;padding:7px 13px}

  .svc-card{padding:22px 20px}
  .ship-card{padding:24px 20px}
  .ship-card .btn-x{width:100%}

  .steps{grid-template-columns:1fr;gap:0}
  .step{display:grid;grid-template-columns:64px 1fr;gap:0 16px;text-align:left;padding:0 0 26px;position:relative}
  .step:not(:last-child)::before{content:'';position:absolute;left:31px;top:66px;bottom:4px;width:2px;background:repeating-linear-gradient(180deg,#cbd5e1 0 6px,transparent 6px 12px)}
  .step .circle{margin:0;grid-row:span 2}
  .step h6{align-self:end;margin:6px 0 4px}

  .ai-band{border-radius:20px}
  .ai-chips{flex-direction:column}
  .ai-chip{width:100%}
  .ai-band .btn-x{width:100%}

  .cta{flex-direction:column;align-items:stretch;text-align:center}
  .cta .btns{flex-direction:column}
  .cta .btn-x{width:100%}

  .footer-bottom{flex-direction:column;text-align:center;align-items:center}

  .wa-float,.ai-toggle{width:52px;height:52px;right:16px}
  .wa-float{font-size:24px;bottom:calc(18px + env(safe-area-inset-bottom))}
  .ai-toggle{bottom:calc(80px + env(safe-area-inset-bottom));font-size:19px}
  .ai-hint{display:none}

  /* Chat becomes a full-screen sheet */
  .ai-panel{
    inset:0;width:100%;height:100%;border-radius:0;border:none;
    padding-top:env(safe-area-inset-top);padding-bottom:env(safe-area-inset-bottom);
  }
  .ai-panel.hidden{transform:translateY(24px)}
  /* Floating buttons would cover the chat's send button */
  body.chat-open .ai-toggle,body.chat-open .wa-float{display:none}
}
@media (max-width:380px){
  .hero h1{font-size:29px}
  .brand-txt p{display:none}
  .ai-lang button{padding:6px 7px;min-width:30px}
}
@media (max-width:900px) and (orientation:landscape) and (max-height:500px){
  .ai-panel{inset:auto;bottom:10px;right:10px;width:min(420px,calc(100vw - 20px));height:calc(100vh - 20px);border-radius:16px}
}
@media (prefers-reduced-motion:reduce){
  *,*::before,*::after{animation-duration:.01ms !important;transition-duration:.01ms !important;scroll-behavior:auto !important}
}
@media print{
  .top-bar,.main-navbar,.wa-float,.ai-toggle,.ai-panel,.ai-hint,.drawer,.drawer-backdrop{display:none !important}
}
</style>
</head>
<body>

<!-- TOP BAR -->
<div class="top-bar">
  <div class="container d-flex justify-content-between align-items-center gap-2">
    <div class="tb-items">
      <span class="tb-loc"><i class="fas fa-location-dot"></i>Dar es Salaam, Tanzania</span>
      <a href="tel:+<?php echo $wa_number; ?>"><i class="fas fa-phone"></i>+255 759 431 401</a>
      <a href="mailto:benardkahayi@gmail.com"><i class="fas fa-envelope"></i>benardkahayi@gmail.com</a>
    </div>
    <div class="social-icons">
      <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
      <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
      <a href="https://wa.me/<?php echo $wa_number; ?>" target="_blank" rel="noopener" aria-label="WhatsApp"><i class="fab fa-whatsapp"></i></a>
    </div>
  </div>
</div>

<!-- NAVBAR -->
<nav class="main-navbar">
  <div class="container">
    <a href="index.php" class="brand">
      <div class="brand-icon"><img src="image/logo.png" alt="AB FASHIONSTORE"></div>
      <div class="brand-txt">
        <h3>AB FASHIONSTORE</h3>
        <p>Blessings Collection</p>
      </div>
    </a>

    <div class="nav-links">
      <a href="index.php" class="active">Home</a>
      <a href="#features">Services</a>
      <a href="#shipping">Shipping</a>
      <a href="#how">How It Works</a>
      <a href="track_order.php">Track Order</a>
      <a href="#contact">Contact</a>
    </div>

    <div class="nav-actions">
      <?php if ($is_customer): ?>
        <a href="customer_dashboard.php" class="btn-x primary sm"><i class="fas fa-house"></i> Dashboard</a>
        <a href="customer_logout.php" class="btn-x ghost sm">Logout</a>
      <?php else: ?>
        <a href="customer_login.php" class="btn-x ghost sm">Login</a>
        <a href="customer_login.php" class="btn-x primary sm">Get Started</a>
      <?php endif; ?>
      <button class="nav-toggle" onclick="openDrawer()" aria-label="Open menu" aria-expanded="false" aria-controls="mobileDrawer">
        <i class="fas fa-bars"></i>
      </button>
    </div>
  </div>
</nav>

<!-- MOBILE DRAWER -->
<div class="drawer-backdrop" id="drawerBackdrop" onclick="closeDrawer()"></div>
<aside class="drawer" id="mobileDrawer" aria-hidden="true">
  <div class="drawer-head">
    <div class="brand">
      <div class="brand-icon"><img src="image/logo.png" alt=""></div>
      <div class="brand-txt"><h3>AB FASHIONSTORE</h3><p>Blessings Collection</p></div>
    </div>
    <button class="drawer-close" onclick="closeDrawer()" aria-label="Close menu"><i class="fas fa-xmark"></i></button>
  </div>
  <div class="drawer-body">
    <a href="index.php" class="active"><i class="fas fa-house"></i> Home</a>
    <a href="#features" onclick="closeDrawer()"><i class="fas fa-box"></i> Services</a>
    <a href="#shipping" onclick="closeDrawer()"><i class="fas fa-plane"></i> Shipping Rates</a>
    <a href="#how" onclick="closeDrawer()"><i class="fas fa-list-check"></i> How It Works</a>
    <a href="track_order.php"><i class="fas fa-truck"></i> Track Order</a>
    <a href="#" onclick="closeDrawer();openChat();return false;"><i class="fas fa-robot"></i> Ask AB Store AI</a>
    <a href="#contact" onclick="closeDrawer()"><i class="fas fa-envelope"></i> Contact</a>
  </div>
  <div class="drawer-foot">
    <?php if ($is_customer): ?>
      <a href="customer_dashboard.php" class="btn-x primary"><i class="fas fa-house"></i> Dashboard</a>
      <a href="customer_logout.php" class="btn-x ghost">Logout</a>
    <?php else: ?>
      <a href="customer_login.php" class="btn-x primary">Get Started</a>
      <a href="customer_login.php" class="btn-x ghost">Login</a>
    <?php endif; ?>
  </div>
</aside>

<!-- HERO -->
<section class="hero" id="home">
  <div class="container">
    <div class="row align-items-center">
      <div class="col-lg-6">
        <div class="hero-tag"><span class="dot">NEW</span> Ask our AI assistant in English or Kiswahili</div>
        <h1>Buy from China.<br>We bring it to <span class="hl">Tanzania.</span></h1>
        <p class="hero-sub">Send us a product link from Alibaba, 1688 or any Chinese supplier. We source it, purchase it, ship it by air or sea, and clear customs — you just collect.</p>
        <div class="hero-btns">
          <a href="<?php echo $is_customer ? 'customer_dashboard.php' : 'customer_login.php'; ?>" class="btn-x amber"><i class="fas fa-arrow-right-long"></i> Start Importing</a>
          <button type="button" class="btn-x ghost-light" onclick="openChat()"><i class="fas fa-robot"></i> Ask AB Store AI</button>
        </div>
        <div class="hero-checks">
          <div class="hero-check"><i class="fas fa-circle-check"></i> Air: 3–5 days</div>
          <div class="hero-check"><i class="fas fa-circle-check"></i> Sea: customs included</div>
          <div class="hero-check"><i class="fas fa-circle-check"></i> M-Pesa, Airtel, Bank</div>
        </div>
      </div>

      <div class="col-lg-6">
        <div class="hero-visual">
          <div class="hero-photo">
            <img src="image/mzigo.jpg" alt="Packages ready for shipping from China to Tanzania">
          </div>
          <div class="live-pill">
            <div class="ic"><i class="fas fa-box-open"></i></div>
            <div><strong><?php echo number_format($stat_orders); ?></strong><span>orders handled</span></div>
          </div>
          <div class="track-card">
            <h6><i class="fas fa-location-crosshairs"></i> Track your shipment</h6>
            <p>Enter your Order ID to see where your package is.</p>
            <form class="track-form" action="track_order.php" method="get">
              <input type="text" name="id" placeholder="e.g. ORD-20261115-0042" aria-label="Order ID" required>
              <button type="submit" class="btn-x primary"><i class="fas fa-magnifying-glass"></i> Track</button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- STATS -->
<div class="stats-strip">
  <div class="container">
    <div class="row">
      <div class="col-6 col-lg-3">
        <div class="stat-box"><div class="ic" style="background:#eaf1ff;color:#2563eb"><i class="fas fa-users"></i></div>
          <div><strong><?php echo number_format($stat_customers); ?></strong><span>Customers</span></div></div>
      </div>
      <div class="col-6 col-lg-3">
        <div class="stat-box"><div class="ic" style="background:#fff4dc;color:#b45309"><i class="fas fa-box"></i></div>
          <div><strong><?php echo number_format($stat_orders); ?></strong><span>Orders placed</span></div></div>
      </div>
      <div class="col-6 col-lg-3">
        <div class="stat-box"><div class="ic" style="background:#dcfce7;color:#15803d"><i class="fas fa-cubes"></i></div>
          <div><strong><?php echo number_format($stat_items); ?></strong><span>Items imported</span></div></div>
      </div>
      <div class="col-6 col-lg-3">
        <div class="stat-box"><div class="ic" style="background:#f3e8ff;color:#7c3aed"><i class="fas fa-credit-card"></i></div>
          <div><strong><?php echo number_format($stat_payments); ?></strong><span>Payments completed</span></div></div>
      </div>
    </div>
  </div>
</div>

<!-- PLATFORMS -->
<div class="platforms">
  <div class="container d-flex align-items-center gap-4 flex-wrap flex-lg-nowrap justify-content-center">
    <span class="lbl">We buy from</span>
    <div class="platform-list">
      <span class="platform">Alibaba</span>
      <span class="platform">1688</span>
      <span class="platform">Taobao</span>
      <span class="platform">AliExpress</span>
      <span class="platform">Any Chinese supplier</span>
    </div>
  </div>
</div>

<!-- SERVICES -->
<section class="section" id="features">
  <div class="container">
    <div class="section-head center">
      <span class="eyebrow">What we do</span>
      <h2 class="section-title">One partner for the whole journey</h2>
      <p class="section-sub">From finding the right supplier in China to handing you the package in Tanzania.</p>
    </div>
    <div class="row g-3 g-lg-4">
      <div class="col-sm-6 col-lg-3">
        <div class="svc-card"><span class="num">01</span>
          <div class="ic" style="background:#eaf1ff;color:#2563eb"><i class="fas fa-magnifying-glass"></i></div>
          <h5>Product Sourcing</h5>
          <p>Send a link, photo, screenshot or description — we find the product from trusted Chinese suppliers.</p>
        </div>
      </div>
      <div class="col-sm-6 col-lg-3">
        <div class="svc-card"><span class="num">02</span>
          <div class="ic" style="background:#fff4dc;color:#b45309"><i class="fas fa-cart-shopping"></i></div>
          <h5>Purchasing</h5>
          <p>Once the product details and costs are confirmed, we pay the supplier on your behalf.</p>
        </div>
      </div>
      <div class="col-sm-6 col-lg-3">
        <div class="svc-card"><span class="num">03</span>
          <div class="ic" style="background:#dcfce7;color:#15803d"><i class="fas fa-ship"></i></div>
          <h5>Air &amp; Sea Shipping</h5>
          <p>Fast air cargo for urgent goods, or economical sea freight for larger loads.</p>
        </div>
      </div>
      <div class="col-sm-6 col-lg-3">
        <div class="svc-card"><span class="num">04</span>
          <div class="ic" style="background:#f3e8ff;color:#7c3aed"><i class="fas fa-file-shield"></i></div>
          <h5>Customs &amp; Collection</h5>
          <p>We handle clearance and you collect from our collection point in Tanzania.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- SHIPPING -->
<section class="section alt" id="shipping">
  <div class="container">
    <div class="section-head center">
      <span class="eyebrow">Shipping options</span>
      <h2 class="section-title">Clear rates. No surprises.</h2>
      <p class="section-sub">Choose speed or savings. Both options ship from China to Tanzania.</p>
    </div>
    <div class="row g-3 g-lg-4 justify-content-center">
      <div class="col-md-6 col-lg-5">
        <div class="ship-card dark">
          <div class="head">
            <div class="ic" style="background:rgba(255,255,255,.1);color:#fbbf24"><i class="fas fa-plane-departure"></i></div>
            <div><h4>Air Cargo</h4><span class="tag">Fastest option</span></div>
          </div>
          <div class="ship-price">$13 <small>/ kg</small></div>
          <div class="ship-meta"><span><i class="fas fa-clock me-1"></i> 3–5 days</span><span>Actual weight</span></div>
          <ul class="ship-list">
            <li><i class="fas fa-check"></i> Ideal for phones, electronics, fashion and urgent orders</li>
            <li><i class="fas fa-check"></i> Charged on actual weight</li>
            <li><i class="fas fa-check"></i> Special or oversized packages get a separate quote</li>
          </ul>
          <button type="button" class="btn-x amber" onclick="openChatWith('How much is air shipping for my package?')"><i class="fas fa-calculator"></i> Get an air estimate</button>
        </div>
      </div>
      <div class="col-md-6 col-lg-5">
        <div class="ship-card">
          <div class="head">
            <div class="ic" style="background:#eaf1ff;color:#2563eb"><i class="fas fa-ship"></i></div>
            <div><h4>Sea Freight</h4><span class="tag">Best value for big loads</span></div>
          </div>
          <div class="ship-price" style="color:var(--navy)">$490 <small>/ CBM</small></div>
          <div class="ship-meta"><span><i class="fas fa-clock me-1"></i> 35–40 days</span><span>Customs included</span></div>
          <ul class="ship-list">
            <li><i class="fas fa-check"></i> Great for furniture, stock and bulk orders</li>
            <li><i class="fas fa-check"></i> Customs and clearance included in the rate</li>
            <li><i class="fas fa-check"></i> Collect from our AB Store collection point</li>
          </ul>
          <button type="button" class="btn-x primary" onclick="openChatWith('How much is sea shipping per CBM?')"><i class="fas fa-calculator"></i> Get a sea estimate</button>
        </div>
      </div>
    </div>
    <p class="ship-note"><i class="fas fa-circle-info me-1"></i> Please collect within 7 days of arrival — extra charges may apply after that. Final costs are confirmed by AB Store before purchase.</p>
  </div>
</section>

<!-- HOW IT WORKS -->
<section class="section" id="how">
  <div class="container">
    <div class="section-head center">
      <span class="eyebrow">How it works</span>
      <h2 class="section-title">Four simple steps</h2>
      <p class="section-sub">Simple, fast and transparent — you can follow every step from your account.</p>
    </div>
    <div class="steps">
      <div class="step">
        <div class="circle"><i class="fas fa-link"></i></div>
        <h6>Send the product</h6>
        <p>Share a link, photo or description of what you want.</p>
      </div>
      <div class="step">
        <div class="circle"><i class="fas fa-file-invoice-dollar"></i></div>
        <h6>Confirm &amp; pay</h6>
        <p>We confirm the price and shipping, you pay by mobile money or bank.</p>
      </div>
      <div class="step">
        <div class="circle"><i class="fas fa-plane"></i></div>
        <h6>We buy &amp; ship</h6>
        <p>We purchase from the supplier and ship by air or sea.</p>
      </div>
      <div class="step">
        <div class="circle"><i class="fas fa-box-open"></i></div>
        <h6>You collect</h6>
        <p>We clear customs and notify you when it's ready to collect.</p>
      </div>
    </div>
    <div class="text-center mt-5">
      <a href="<?php echo $is_customer ? 'customer_dashboard.php' : 'customer_login.php'; ?>" class="btn-x primary"><i class="fas fa-arrow-right-long"></i> Get Started</a>
    </div>
  </div>
</section>

<!-- AI ASSISTANT -->
<section class="section pt-0" id="assistant">
  <div class="container">
    <div class="ai-band">
      <div class="row align-items-center g-4">
        <div class="col-lg-6">
          <span class="eyebrow" style="color:#93c5fd">AB Store AI</span>
          <h2>Questions? Get an answer in seconds.</h2>
          <p>Our AI assistant knows our services, shipping rates and process — and answers in English or Kiswahili, day or night. Need a person? It hands you straight to our team on WhatsApp.</p>
          <div class="ai-chips">
            <button type="button" class="ai-chip" onclick="openChatWith('Can I send a 1688 product link?')"><i class="fas fa-link me-2"></i>Can I send a 1688 link?</button>
            <button type="button" class="ai-chip" onclick="openChatWith('Usafirishaji wa meli unachukua siku ngapi?', 'sw')"><i class="fas fa-ship me-2"></i>Usafirishaji wa meli ni siku ngapi?</button>
            <button type="button" class="ai-chip" onclick="openChatWith('What happens if I collect my package late?')"><i class="fas fa-clock me-2"></i>What if I collect late?</button>
          </div>
          <button type="button" class="btn-x amber" onclick="openChat()"><i class="fas fa-robot"></i> Chat with AB Store AI</button>
        </div>
        <div class="col-lg-6">
          <div class="ai-preview" aria-hidden="true">
            <div class="lang-badges"><span>🇬🇧 English</span><span>🇹🇿 Kiswahili</span><span>24/7</span></div>
            <div class="who" style="text-align:right">You</div>
            <div class="b u">How long does air shipping take?</div>
            <div class="who">AB Store AI</div>
            <div class="b a">Air shipping takes about <strong>3–5 days</strong> and costs <strong>USD 13 per kg</strong> (actual weight). Oversized packages get a separate quote. Would you like an estimate?</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- CTA -->
<section class="section pt-0">
  <div class="container">
    <div class="cta">
      <div>
        <h3>Ready to import your first order?</h3>
        <p>Create a free account and track every shipment from China to Tanzania.</p>
      </div>
      <div class="btns">
        <a href="<?php echo $is_customer ? 'customer_dashboard.php' : 'customer_login.php'; ?>" class="btn-x navy"><i class="fas fa-user-plus"></i> <?php echo $is_customer ? 'Go to Dashboard' : 'Create Account'; ?></a>
        <a href="https://wa.me/<?php echo $wa_number; ?>" target="_blank" rel="noopener" class="btn-x wa"><i class="fab fa-whatsapp"></i> WhatsApp Us</a>
      </div>
    </div>
  </div>
</section>

<!-- FOOTER -->
<footer id="contact">
  <div class="container">
    <div class="row g-4">
      <div class="col-lg-4 col-12">
        <div class="brand">
          <div class="brand-icon"><img src="image/logo.png" alt=""></div>
          <div class="brand-txt"><h3>AB FASHIONSTORE</h3><p>Blessings Collection</p></div>
        </div>
        <p class="footer-desc">Your trusted partner for sourcing and importing quality products from China to Tanzania.</p>
      </div>
      <div class="col-6 col-lg-2">
        <h6>Quick Links</h6>
        <ul>
          <li><a href="index.php">Home</a></li>
          <li><a href="#how">How It Works</a></li>
          <li><a href="customer_login.php">My Orders</a></li>
          <li><a href="track_order.php">Track Order</a></li>
        </ul>
      </div>
      <div class="col-6 col-lg-2">
        <h6>Services</h6>
        <ul>
          <li><a href="#features">Product Sourcing</a></li>
          <li><a href="#features">Purchasing</a></li>
          <li><a href="#shipping">Air Cargo</a></li>
          <li><a href="#shipping">Sea Freight</a></li>
        </ul>
      </div>
      <div class="col-12 col-lg-4">
        <h6>Contact</h6>
        <div class="footer-contact">
          <p><i class="fas fa-location-dot"></i> Dar es Salaam, Tanzania</p>
          <p><i class="fas fa-phone"></i> <a href="tel:+<?php echo $wa_number; ?>">+255 759 431 401</a></p>
          <p><i class="fas fa-envelope"></i> <a href="mailto:benardkahayi@gmail.com">benardkahayi@gmail.com</a></p>
          <p><i class="fab fa-whatsapp"></i> <a href="https://wa.me/<?php echo $wa_number; ?>" target="_blank" rel="noopener">Chat on WhatsApp</a></p>
        </div>
      </div>
    </div>
    <div class="footer-bottom">
      <span>&copy; <?php echo date('Y'); ?> AB FASHIONSTORE — Blessings Collection. All rights reserved.</span>
      <span class="d-flex gap-3"><a href="#">Privacy</a><a href="#">Terms</a></span>
    </div>
  </div>
</footer>

<!-- FLOATING BUTTONS -->
<a href="https://wa.me/<?php echo $wa_number; ?>" class="wa-float" target="_blank" rel="noopener" aria-label="Chat on WhatsApp"><i class="fab fa-whatsapp"></i></a>
<button class="ai-toggle" onclick="toggleChat()" id="aiToggle" aria-label="Chat with AB Store AI" aria-expanded="false" aria-controls="aiPanel">
  <i class="fas fa-robot" id="aiToggleIcon"></i><span class="ai-badge">AI</span>
</button>
<div class="ai-hint" id="aiHint" hidden>👋 Need help? Ask our AI assistant<button type="button" onclick="dismissHint()" aria-label="Dismiss">✕</button></div>

<!-- AI CHAT PANEL -->
<div class="ai-panel hidden" id="aiPanel" role="dialog" aria-label="AB Store AI assistant" aria-hidden="true">
  <div class="ai-header">
    <div class="ai-avatar"><i class="fas fa-robot"></i></div>
    <div class="ai-info">
      <h6>AB Store AI</h6>
      <span class="ai-status" id="aiStatus">Connecting…</span>
    </div>
    <div class="ai-lang" role="group" aria-label="Language">
      <button type="button" data-lang="en" onclick="setLang('en', true)">EN</button>
      <button type="button" data-lang="sw" onclick="setLang('sw', true)">SW</button>
    </div>
    <button class="ai-hbtn" onclick="newChat()" aria-label="New chat" title="New chat"><i class="fas fa-rotate-right"></i></button>
    <button class="ai-hbtn" onclick="toggleChat()" aria-label="Close chat"><i class="fas fa-xmark"></i></button>
  </div>
  <div class="ai-messages" id="aiMessages" aria-live="polite"></div>
  <div class="ai-quick" id="quickReplies"></div>
  <div class="ai-input-area">
    <textarea class="ai-input" id="aiInput" rows="1" maxlength="2000" placeholder="Type your question…" aria-label="Your message"></textarea>
    <button class="ai-send" id="aiSend" onclick="sendMessage()" aria-label="Send"><i class="fas fa-paper-plane"></i></button>
  </div>
  <div class="ai-footer-note" id="aiNote">AI answers may be imperfect — confirm prices with our team.</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
/* ── Drawer ── */
function openDrawer(){
  document.getElementById('mobileDrawer').classList.add('open');
  document.getElementById('drawerBackdrop').classList.add('open');
  document.getElementById('mobileDrawer').setAttribute('aria-hidden','false');
  document.querySelector('.nav-toggle').setAttribute('aria-expanded','true');
  document.body.style.overflow='hidden';
}
function closeDrawer(){
  document.getElementById('mobileDrawer').classList.remove('open');
  document.getElementById('drawerBackdrop').classList.remove('open');
  document.getElementById('mobileDrawer').setAttribute('aria-hidden','true');
  document.querySelector('.nav-toggle').setAttribute('aria-expanded','false');
  document.body.style.overflow='';
}
document.addEventListener('keydown',e=>{ if(e.key==='Escape'){ closeDrawer(); if(chatOpen) toggleChat(); } });

/* ── AB Store AI chat ── */
const WA_NUMBER = '<?php echo $wa_number; ?>';
const T = {
  en: {
    greet: "Hello! 👋 I'm <strong>AB Store AI</strong>. Ask me about sourcing, shipping rates, payments or how to import from China.",
    placeholder: 'Type your question…',
    online: 'Online · English & Kiswahili', offline: 'Offline · chat with us on WhatsApp', connecting: 'Connecting…',
    handoff: 'Chat with our team on WhatsApp', error: 'Connection problem. Please try again, or chat with our team on WhatsApp.',
    slow: 'Too many messages — please wait a moment.', note: 'AI answers may be imperfect — confirm prices with our team.',
    waText: 'Hello AB Store, I need help with: ',
    quick: ['How does importing work?', 'Air shipping cost?', 'Sea shipping time?', 'Can I send a 1688 link?', 'Payment methods?']
  },
  sw: {
    greet: "Habari! 👋 Mimi ni <strong>AB Store AI</strong>. Niulize kuhusu kutafuta bidhaa, gharama za usafirishaji, malipo au jinsi ya kuagiza kutoka China.",
    placeholder: 'Andika swali lako…',
    online: 'Mtandaoni · Kiingereza na Kiswahili', offline: 'Hapatikani · wasiliana nasi WhatsApp', connecting: 'Inaunganisha…',
    handoff: 'Ongea na timu yetu WhatsApp', error: 'Kuna tatizo la mtandao. Jaribu tena au wasiliana na timu yetu WhatsApp.',
    slow: 'Ujumbe mwingi sana — tafadhali subiri kidogo.', note: 'Majibu ya AI yanaweza kuwa na makosa — thibitisha bei na timu yetu.',
    waText: 'Habari AB Store, naomba msaada kuhusu: ',
    quick: ['Uagizaji unafanyaje kazi?', 'Bei ya usafiri wa ndege?', 'Meli inachukua siku ngapi?', 'Naweza kutuma link ya 1688?', 'Njia za malipo?']
  }
};
const SW_HINTS = /\b(habari|mambo|nataka|naomba|naweza|je|bei|gani|ngapi|mzigo|bidhaa|usafirishaji|usafiri|meli|ndege|siku|asante|tafadhali|kwa|ya|na|ni|oda|malipo|lini|wapi|kazi|jinsi|uagizaji|kuagiza|gharama|kutuma|mnaweza|ninaweza|kuhusu|vipi|sana|nini|hii|hiyo|yangu|wangu)\b/gi;

let chatOpen = false, busy = false, lang = 'en', langChosen = false, healthChecked = false, lastQuestion = '';

function store(k, v){ try{ if(v===undefined) return localStorage.getItem(k); localStorage.setItem(k, v); }catch(e){ return null; } }
(function initLang(){ const saved = store('abai_lang'); if(saved==='en'||saved==='sw'){ lang = saved; langChosen = true; } })();

function setLang(l, manual){
  lang = l;
  if(manual){ langChosen = true; store('abai_lang', l); }
  document.querySelectorAll('.ai-lang button').forEach(b => b.classList.toggle('on', b.dataset.lang === l));
  document.getElementById('aiInput').placeholder = T[l].placeholder;
  document.getElementById('aiNote').textContent = T[l].note;
  renderQuick();
  if(healthChecked) setStatus(document.getElementById('aiStatus').dataset.state);
}
function renderQuick(){
  const q = document.getElementById('quickReplies');
  q.innerHTML = '';
  T[lang].quick.forEach(text => {
    const b = document.createElement('button');
    b.type = 'button'; b.className = 'quick-btn'; b.textContent = text;
    b.onclick = () => { document.getElementById('aiInput').value = text; sendMessage(); };
    q.appendChild(b);
  });
}
function setStatus(state){
  const el = document.getElementById('aiStatus');
  el.dataset.state = state;
  el.className = 'ai-status' + (state === 'online' ? ' online' : state === 'offline' ? ' offline' : '');
  el.textContent = T[lang][state] || T[lang].connecting;
}
async function checkHealth(){
  healthChecked = true; setStatus('connecting');
  try{
    const r = await fetch('chatbot.php?health=1', {cache:'no-store'});
    const d = await r.json();
    setStatus(d.online ? 'online' : 'offline');
  }catch(e){ setStatus('offline'); }
}

function toggleChat(){
  chatOpen = !chatOpen;
  const panel = document.getElementById('aiPanel');
  panel.classList.toggle('hidden', !chatOpen);
  panel.setAttribute('aria-hidden', String(!chatOpen));
  document.getElementById('aiToggle').setAttribute('aria-expanded', String(chatOpen));
  document.getElementById('aiToggleIcon').className = chatOpen ? 'fas fa-xmark' : 'fas fa-robot';
  document.body.classList.toggle('chat-open', chatOpen);
  dismissHint();
  if(chatOpen){
    if(!document.getElementById('aiMessages').children.length) greet();
    if(!healthChecked) checkHealth();
    if(window.innerWidth > 640) setTimeout(() => document.getElementById('aiInput').focus(), 250);
    if(window.innerWidth <= 640) document.body.style.overflow = 'hidden';
    scrollBottom();
  }else{
    document.body.style.overflow = '';
  }
}
function openChat(){ if(!chatOpen) toggleChat(); }
function openChatWith(text, l){
  if(l && !langChosen) setLang(l, false);
  openChat();
  document.getElementById('aiInput').value = text;
  sendMessage();
}
function greet(){ appendMsg('bot', T[lang].greet); }

function getTime(){ return new Date().toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'}); }
function scrollBottom(){ const el = document.getElementById('aiMessages'); el.scrollTop = el.scrollHeight; }
function escapeHtml(t){ return t.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
function formatReply(text){
  return escapeHtml(text)
    .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
    .replace(/^\s*[-*]\s+/gm, '• ')
    .replace(/\n/g, '<br>');
}
function appendMsg(role, html, opts){
  opts = opts || {};
  const wrap = document.getElementById('aiMessages');
  const div = document.createElement('div');
  div.className = 'msg ' + role;
  let extra = '';
  if(opts.handoff){
    const wa = 'https://wa.me/' + WA_NUMBER + '?text=' + encodeURIComponent(T[lang].waText + (lastQuestion || ''));
    extra = '<a class="handoff" href="' + wa + '" target="_blank" rel="noopener"><i class="fab fa-whatsapp"></i>' + T[lang].handoff + '</a>';
  }
  div.innerHTML = role === 'bot'
    ? '<div class="msg-icon"><i class="fas fa-robot"></i></div><div><div class="msg-bubble">' + html + '</div>' + extra + '<span class="msg-time">' + getTime() + '</span></div>'
    : '<div><div class="msg-bubble">' + html + '</div><span class="msg-time">' + getTime() + '</span></div>';
  wrap.appendChild(div); scrollBottom();
}
function showTyping(){
  const div = document.createElement('div');
  div.className = 'msg bot'; div.id = 'typingMsg';
  div.innerHTML = '<div class="msg-icon"><i class="fas fa-robot"></i></div><div class="msg-bubble"><div class="typing"><span></span><span></span><span></span></div></div>';
  document.getElementById('aiMessages').appendChild(div); scrollBottom();
}
function removeTyping(){ const t = document.getElementById('typingMsg'); if(t) t.remove(); }
/* Follows each message's language until the visitor picks one with the EN/SW switch. */
function autoDetect(text){
  if(langChosen) return;
  const hits = (text.match(SW_HINTS) || []).length;
  const words = text.trim().split(/\s+/).length;
  const detected = (hits >= 2 || (words <= 3 && hits >= 1)) ? 'sw' : 'en';
  if(detected !== lang) setLang(detected, false);
}

async function sendMessage(){
  if(busy) return;
  const input = document.getElementById('aiInput');
  const text = input.value.trim();
  if(!text) return;
  autoDetect(text);
  lastQuestion = text;
  document.getElementById('quickReplies').style.display = 'none';
  input.value = ''; autoGrow();
  appendMsg('user', escapeHtml(text));
  busy = true; document.getElementById('aiSend').disabled = true;
  showTyping();
  try{
    const res = await fetch('chatbot.php', {
      method: 'POST', headers: {'Content-Type':'application/json'},
      body: JSON.stringify({message: text, language: lang, language_chosen: langChosen})
    });
    const data = await res.json().catch(() => ({}));
    removeTyping();
    if(res.status === 429){ appendMsg('bot', T[lang].slow); }
    else if(!res.ok || !data.reply){ appendMsg('bot', T[lang].error, {handoff:true}); }
    else{
      // Follow the language the AI detected, unless the visitor picked one.
      if(!langChosen && (data.language === 'en' || data.language === 'sw') && data.language !== lang) setLang(data.language, false);
      appendMsg('bot', formatReply(data.reply), {handoff: !!data.human_support_required});
      if(data.offline) setStatus('offline'); else setStatus('online');
    }
  }catch(err){
    removeTyping();
    appendMsg('bot', T[lang].error, {handoff:true});
    setStatus('offline');
  }
  busy = false; document.getElementById('aiSend').disabled = false;
  if(window.innerWidth > 640) input.focus();
}
async function newChat(){
  if(busy) return;
  document.getElementById('aiMessages').innerHTML = '';
  document.getElementById('quickReplies').style.display = '';
  lastQuestion = '';
  greet();
  try{ await fetch('chatbot.php', {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({reset:true})}); }catch(e){}
}

/* Input: Enter sends, Shift+Enter adds a line; grows with content */
const aiInput = document.getElementById('aiInput');
function autoGrow(){ aiInput.style.height = 'auto'; aiInput.style.height = Math.min(aiInput.scrollHeight, 120) + 'px'; }
aiInput.addEventListener('input', autoGrow);
aiInput.addEventListener('keydown', e => { if(e.key === 'Enter' && !e.shiftKey && !e.isComposing){ e.preventDefault(); sendMessage(); } });

/* One-time hint bubble on desktop */
function dismissHint(){ const h = document.getElementById('aiHint'); if(h) h.hidden = true; store('abai_hint', '1'); }
setTimeout(() => { if(!chatOpen && store('abai_hint') !== '1' && window.innerWidth > 640) document.getElementById('aiHint').hidden = false; }, 4000);

setLang(lang, false);
</script>
</body>
</html>
