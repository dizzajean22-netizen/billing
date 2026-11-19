<?php
/* ============================================================
   _staff_ui.php
   Shared layout for Front Desk & Billing staff pages.
   Expects the including page to define:
     $page_title  (string) — shown in <title> and top bar H1
     $page_icon   (string) — fontawesome icon, e.g. fa-file-circle-plus
     $page_sub    (string) — subtitle under the H1
     $active_nav  (string) — sidebar key: dashboard|patients|add_patient|
                             input_billing|billing_records
   ============================================================ */

if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php'); exit;
}

require_once __DIR__ . '/config.php';

/* ---------- Session / role ---------- */
$user_id   = $_SESSION['user_id']   ?? 0;
$full_name = $_SESSION['full_name'] ?? 'User';
$user_role = $_SESSION['role']      ?? 'User';
$r = strtolower(trim($user_role));

$is_frontdesk = strpos($r,'reception')    !== false
             || strpos($r,'frontdesk')    !== false
             || strpos($r,'front desk')   !== false
             || strpos($r,'front-desk')   !== false
             || strpos($r,'front office') !== false
             || in_array($r, ['receptionist','front desk receptionist','front desk reception','reception'], true);

$is_billing   = in_array($r, ['billing','cashier'], true)
             || strpos($r,'billing') !== false
             || strpos($r,'cashier') !== false;

if (!$is_frontdesk && !$is_billing) {
    header('Location: login.php?msg=access_denied'); exit;
}

/* ---------- Theme (unified teal) ---------- */
$sidebar_brand = $is_billing ? 'Billing Staff' : 'Front Desk';
$sidebar_sub   = $is_billing ? 'BILLING PORTAL' : 'RECEPTIONIST';
$accent        = '#0f766e';
$accent_light  = '#14b8a6';
$dash_url      = $is_billing ? 'billing_dashboard.php' : 'frontdesk_dashboard.php';
$initials      = strtoupper(substr($full_name, 0, 2));

/* ---------- Page defaults ---------- */
$page_title = $page_title ?? 'Dashboard';
$page_icon  = $page_icon  ?? 'fa-gauge-high';
$page_sub   = $page_sub   ?? '';
$active_nav = $active_nav ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($page_title) ?> · Hospital Billing System</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;background:#fff;display:flex;min-height:100vh;color:#1e293b}

/* ===== SIDEBAR ===== */
.sidebar{width:270px;background:#fff;color:#1e293b;display:flex;flex-direction:column;padding:25px 0 20px;position:fixed;top:0;left:0;height:100vh;z-index:200;overflow-y:auto;border-right:1px solid #e2e8f0}
.sidebar-brand{padding:0 22px 25px;display:flex;align-items:center;gap:12px}
.sidebar-brand .logo{width:38px;height:38px;border-radius:10px;background:linear-gradient(135deg,<?= $accent_light ?>,<?= $accent ?>);color:#fff;display:flex;align-items:center;justify-content:center;font-size:17px;flex-shrink:0;box-shadow:0 4px 12px rgba(15,118,110,.25)}
.sidebar-brand .brand-text{line-height:1.2}
.sidebar-brand .brand-name{font-size:15px;font-weight:800;color:#0f172a}
.sidebar-brand .brand-sub{font-size:10.5px;color:#64748b;margin-top:2px;letter-spacing:.5px;text-transform:uppercase}
.sidebar-menu{list-style:none;padding:0 14px;flex:1}
.sidebar-menu li{margin-bottom:4px}
.sidebar-menu li a{display:flex;align-items:center;gap:14px;padding:12px 15px;color:#475569;text-decoration:none;border-radius:10px;font-size:13.5px;font-weight:500;transition:all .25s ease}
.sidebar-menu li a i{width:20px;font-size:16px;text-align:center;color:#94a3b8}
.sidebar-menu li a:hover{background:#f0fdfa;color:<?= $accent ?>}
.sidebar-menu li a:hover i{color:<?= $accent ?>}
.sidebar-menu li a.active{background:<?= $accent ?>;color:#fff;box-shadow:0 4px 12px rgba(15,118,110,.2)}
.sidebar-menu li a.active i{color:#fff}
.sidebar-footer{padding:15px 14px 0;border-top:1px solid #f1f5f9;margin-top:15px}
.sidebar-footer a{display:flex;align-items:center;gap:14px;padding:12px 15px;color:#64748b;text-decoration:none;border-radius:10px;font-size:13.5px;font-weight:500;transition:all .25s ease}
.sidebar-footer a i{color:#94a3b8}
.sidebar-footer a:hover{background:#fef2f2;color:#dc2626}
.sidebar-footer a:hover i{color:#dc2626}

.burger-btn{display:none;position:fixed;top:15px;left:15px;z-index:300;width:44px;height:44px;background:<?= $accent ?>;color:#fff;border:none;border-radius:10px;font-size:18px;cursor:pointer;align-items:center;justify-content:center;box-shadow:0 4px 12px rgba(0,0,0,.15)}
.burger-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:150}
.burger-overlay.active{display:block}

/* ===== MAIN ===== */
.main{margin-left:270px;flex:1;padding:0 0 40px 0;min-height:100vh}
.top-bar{background:#fff;padding:18px 32px;border-bottom:1px solid #e2e8f0;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:25px}
.top-bar h1{font-size:22px;font-weight:700;color:#0f172a;display:flex;align-items:center;gap:12px}
.top-bar h1 .h-icon{width:40px;height:40px;border-radius:11px;background:linear-gradient(135deg,<?= $accent_light ?>,<?= $accent ?>);color:#fff;display:flex;align-items:center;justify-content:center;font-size:17px;box-shadow:0 6px 16px rgba(15,118,110,.25)}
.top-bar .sub{font-size:12.5px;color:#64748b;margin-top:4px;margin-left:52px}
.top-bar-actions{display:flex;align-items:center;gap:15px}

/* user chip */
.user-chip{display:flex;align-items:center;gap:10px;padding:6px 16px 6px 6px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:30px}
.user-chip .avatar{width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,<?= $accent_light ?>,<?= $accent ?>);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:12px;flex-shrink:0}
.user-chip .info .name{font-size:13px;font-weight:600;color:#0f172a;line-height:1.2}
.user-chip .info .role{font-size:10.5px;color:<?= $accent ?>;font-weight:600;text-transform:uppercase;margin-top:2px}

/* content wrapper */
.content{padding:0 32px}

/* ===== SHARED UI ===== */
.card{background:#fff;border-radius:14px;border:1px solid #e2e8f0;margin-bottom:22px;overflow:hidden}
.card-header{padding:18px 24px;border-bottom:1px solid #f1f5f9;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px}
.card-header h3{font-size:15px;font-weight:700;color:#0f172a;display:flex;align-items:center;gap:9px}
.card-header h3 i{color:<?= $accent ?>;font-size:15px}
.card-body{padding:22px 24px}

.btn{padding:11px 22px;border-radius:10px;font-size:13.5px;font-weight:600;text-decoration:none;display:inline-flex;align-items:center;gap:8px;border:none;cursor:pointer;transition:all .2s;font-family:inherit}
.btn-primary{background:<?= $accent ?>;color:#fff;box-shadow:0 4px 12px rgba(15,118,110,.25)}
.btn-primary:hover{background:#115e59;transform:translateY(-1px)}
.btn-light{background:#fff;color:#64748b;border:1.5px solid #e2e8f0}
.btn-light:hover{background:#f8fafc;border-color:#cbd5e1;color:#334155}

.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px 20px}
.form-grid .full{grid-column:1 / -1}
.form-group{margin-bottom:4px}
.form-group label{display:block;font-size:12px;font-weight:600;color:#475569;margin-bottom:6px;text-transform:uppercase;letter-spacing:.4px}
.form-group label .req{color:#dc2626}
.form-group input,
.form-group select,
.form-group textarea{width:100%;padding:11px 14px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:13.5px;font-family:inherit;background:#fff;color:#1e293b;transition:border-color .2s,box-shadow .2s}
.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus{outline:none;border-color:<?= $accent_light ?>;box-shadow:0 0 0 4px rgba(20,184,166,.12)}
.form-group textarea{resize:vertical;min-height:90px}
.form-group .hint{font-size:11.5px;color:#94a3b8;margin-top:6px}

.form-actions{display:flex;gap:10px;justify-content:flex-end;flex-wrap:wrap;margin-top:22px;padding-top:18px;border-top:1px solid #f1f5f9}

.alert{padding:14px 18px;border-radius:10px;font-size:13.5px;margin-bottom:18px;display:flex;align-items:center;gap:10px;font-weight:500}
.alert.success,.alert.alert-success{background:#d1fae5;color:#065f46;border:1px solid #6ee7b7}
.alert.error,.alert.alert-error{background:#fee2e2;color:#991b1b;border:1px solid #fca5a5}
.alert.info,.alert.alert-info{background:#dbeafe;color:#1e40af;border:1px solid #93c5fd}

/* tables */
.table-card{background:#fff;border-radius:14px;border:1px solid #e2e8f0;overflow:hidden}
table{width:100%;border-collapse:collapse;font-size:13px}
thead th{text-align:left;padding:12px 20px;background:#f8fafc;color:#64748b;font-weight:600;font-size:11px;text-transform:uppercase;letter-spacing:.5px;border-bottom:1px solid #f1f5f9;white-space:nowrap}
tbody td{padding:14px 20px;border-bottom:1px solid #f1f5f9;color:#334155;vertical-align:middle}
tbody tr:last-child td{border-bottom:none}
tbody tr:hover{background:#f0fdfa}

/* responsive */
@media (max-width:900px){
  .form-grid{grid-template-columns:1fr}
}
@media (max-width:768px){
  .sidebar{transform:translateX(-100%)}
  .sidebar.open{transform:translateX(0)}
  .burger-btn{display:flex}
  .main{margin-left:0}
  .top-bar,.content{padding-left:18px;padding-right:18px}
  .user-chip .info{display:none}
  .user-chip{padding:5px}
  .btn{width:100%;justify-content:center}
  .form-actions .btn{width:100%}
}
</style>
</head>
<body>

<button class="burger-btn" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
<div class="burger-overlay" id="burgerOverlay" onclick="toggleSidebar()"></div>

<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="logo"><i class="fas fa-hospital"></i></div>
        <div class="brand-text">
            <div class="brand-name"><?= htmlspecialchars($sidebar_brand) ?></div>
            <div class="brand-sub"><?= htmlspecialchars($sidebar_sub) ?></div>
        </div>
    </div>
    <ul class="sidebar-menu">
        <li><a href="<?= htmlspecialchars($dash_url) ?>" class="<?= $active_nav === 'dashboard' ? 'active' : '' ?>">
            <i class="fas fa-gauge-high"></i> <span>Dashboard</span></a></li>

        <?php if ($is_frontdesk): ?>
            <li><a href="patients.php" class="<?= $active_nav === 'patients' ? 'active' : '' ?>">
                <i class="fas fa-hospital-user"></i> <span>Patient Records</span></a></li>
            <li><a href="add_patient.php" class="<?= $active_nav === 'add_patient' ? 'active' : '' ?>">
                <i class="fas fa-user-plus"></i> <span>Register &amp; Bill</span></a></li>
        <?php endif; ?>

        <?php if ($is_billing): ?>
            <li><a href="input_billing.php" class="<?= $active_nav === 'input_billing' ? 'active' : '' ?>">
                <i class="fas fa-file-circle-plus"></i> <span>Input Billing</span></a></li>
            <li><a href="patients.php" class="<?= $active_nav === 'patients' ? 'active' : '' ?>">
                <i class="fas fa-hospital-user"></i> <span>Patient Records</span></a></li>
        <?php endif; ?>

        <li><a href="billing_records.php" class="<?= $active_nav === 'billing_records' ? 'active' : '' ?>">
            <i class="fas fa-file-invoice-dollar"></i> <span>Billing Records</span></a></li>
    </ul>
    <div class="sidebar-footer">
        <a href="logout.php"><i class="fas fa-right-from-bracket"></i> <span>Logout</span></a>
    </div>
</aside>

<main class="main">

<div class="top-bar">
    <div>
        <h1>
            <span class="h-icon"><i class="fas <?= htmlspecialchars($page_icon) ?>"></i></span>
            <?= htmlspecialchars($page_title) ?>
        </h1>
        <?php if ($page_sub !== ''): ?>
            <div class="sub"><?= htmlspecialchars($page_sub) ?></div>
        <?php endif; ?>
    </div>

    <div class="top-bar-actions">
        <div class="user-chip">
            <div class="avatar"><?= htmlspecialchars($initials) ?></div>
            <div class="info">
                <div class="name"><?= htmlspecialchars($full_name) ?></div>
                <div class="role"><?= htmlspecialchars($user_role) ?></div>
            </div>
        </div>
    </div>
</div>

<div class="content">