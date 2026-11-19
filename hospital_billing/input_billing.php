<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php'); exit;
}
require_once 'config.php';

$user_id   = $_SESSION['user_id'] ?? 0;
$full_name = $_SESSION['full_name'] ?? 'User';
$user_role = $_SESSION['role'] ?? 'User';
$r = strtolower(trim($user_role));

$is_frontdesk = strpos($r,'reception') !== false || strpos($r,'frontdesk') !== false || strpos($r,'front desk') !== false || strpos($r,'front-desk') !== false || strpos($r,'front office') !== false || in_array($r, ['receptionist','front desk receptionist','front desk reception','reception'], true);
$is_billing   = in_array($r, ['billing','cashier'], true) || strpos($r,'billing') !== false || strpos($r,'cashier') !== false;

$is_cashier_only  = (strpos($r,'cashier') !== false) && (strpos($r,'billing') === false);

if (!$is_frontdesk && !$is_billing) { header('Location: login.php?msg=access_denied'); exit; }

$sidebar_brand = $is_billing ? 'Billing Staff' : 'Front Desk';
$sidebar_sub   = $is_cashier_only ? 'CASHIER PORTAL' : ($is_billing ? 'BILLING PORTAL' : 'RECEPTIONIST');

$accent        = '#0f766e';
$accent_light  = '#14b8a6';
$dash_url      = $is_billing ? 'billing_dashboard.php' : 'frontdesk_dashboard.php';

/* Detect optional amount_paid column (safe on any schema) */
$has_amount_paid = false;
try {
    $col = $pdo->query("SHOW COLUMNS FROM billing_records LIKE 'amount_paid'")->fetch(PDO::FETCH_ASSOC);
    $has_amount_paid = !empty($col);
} catch (PDOException $e) {}

/* ========= INPUTS ========= */
$search          = trim($_GET['q'] ?? '');
$status_filter   = trim($_GET['status'] ?? '');
$category_filter = trim($_GET['category'] ?? '');
$page            = max(1, (int)($_GET['page'] ?? 1));
$per_page        = 50;
$offset          = ($page - 1) * $per_page;

/* ========= BUILD WHERE ========= */
$where  = " WHERE 1=1";
$params = [];

if ($is_cashier_only) {
    $where .= " AND status IN ('Pending','Processing')";
}

if ($search !== '') {
    $search_escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search);
    $where .= " AND (patient_name LIKE ? OR service_description LIKE ? OR patient_id LIKE ?)";
    $like = "%$search_escaped%";
    $params[] = $like; $params[] = $like; $params[] = $like;
}
if ($status_filter !== '') {
    $where .= " AND status = ?";
    $params[] = $status_filter;
}
if ($category_filter !== '') {
    $where .= " AND category = ?";
    $params[] = $category_filter;
}

/* ========= TOTAL COUNT (for pagination) ========= */
$total_rows = 0;
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM billing_records" . $where);
    $stmt->execute($params);
    $total_rows = (int)$stmt->fetchColumn();
} catch (PDOException $e) { $total_rows = 0; }

$total_pages = max(1, (int)ceil($total_rows / $per_page));
if ($page > $total_pages) { $page = $total_pages; $offset = ($page - 1) * $per_page; }

/* ========= FETCH RECORDS ========= */
$amount_paid_select = $has_amount_paid ? "" : ", 0 AS amount_paid";
$records = [];
try {
    $sql = "SELECT *$amount_paid_select FROM billing_records" . $where . " ORDER BY id DESC LIMIT $per_page OFFSET $offset";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $records = []; }

/* ========= AVAILABLE CATEGORIES (for filter) ========= */
$billing_categories = [];
try {
    $billing_categories = $pdo->query("
        SELECT name FROM service_categories WHERE is_active = 1
        UNION
        SELECT DISTINCT category AS name FROM billing_records
        WHERE category IS NOT NULL AND category <> ''
        ORDER BY name
    ")->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    try {
        $billing_categories = $pdo->query("
            SELECT DISTINCT category FROM billing_records
            WHERE category IS NOT NULL AND category <> ''
            ORDER BY category
        ")->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e2) {}
}

/* ========= STATS ========= */
try {
    if ($is_cashier_only) {
        $total   = (int)$pdo->query("SELECT COUNT(*) FROM billing_records WHERE status IN ('Pending','Processing')")->fetchColumn();
        $pending = (int)$pdo->query("SELECT COUNT(*) FROM billing_records WHERE status='Pending'")->fetchColumn();
        $partial = (int)$pdo->query("SELECT COUNT(*) FROM billing_records WHERE status='Processing'")->fetchColumn();
        $balance_sum = $has_amount_paid
            ? "COALESCE(SUM(total_amount - COALESCE(amount_paid, 0)), 0)"
            : "COALESCE(SUM(total_amount), 0)";
        $total_amount = (float)$pdo->query("SELECT $balance_sum FROM billing_records WHERE status IN ('Pending','Processing')")->fetchColumn();
    } else {
        $total   = (int)$pdo->query("SELECT COUNT(*) FROM billing_records")->fetchColumn();
        $pending = (int)$pdo->query("SELECT COUNT(*) FROM billing_records WHERE status='Pending'")->fetchColumn();
        $paid_c  = (int)$pdo->query("SELECT COUNT(*) FROM billing_records WHERE status='Paid'")->fetchColumn();
        $total_amount = (float)$pdo->query("SELECT COALESCE(SUM(total_amount),0) FROM billing_records")->fetchColumn();
        $partial = $paid_c;
    }
} catch (PDOException $e) { $total = $pending = $partial = 0; $total_amount = 0; }

/* ========= PAGINATION HELPERS ========= */
$qs_params = [];
if ($search !== '')          $qs_params['q']        = $search;
if ($status_filter !== '')   $qs_params['status']   = $status_filter;
if ($category_filter !== '') $qs_params['category'] = $category_filter;
if ($is_cashier_only)        $qs_params['cashier']  = 1; // not used server-side, harmless

function pg_url(array $qs_params, int $page): string {
    return '?' . http_build_query(array_merge($qs_params, ['page' => $page]));
}

$page_start = $total_rows > 0 ? $offset + 1 : 0;
$page_end   = min($offset + $per_page, $total_rows);

$initials = strtoupper(substr($full_name, 0, 2));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Billing Records · Hospital Billing System</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;background:#ffffff;display:flex;min-height:100vh;color:#1e293b}

.sidebar{width:270px;background:#fff;color:#1e293b;display:flex;flex-direction:column;padding:25px 0 20px;position:fixed;top:0;left:0;height:100vh;z-index:200;overflow-y:auto;border-right:1px solid #e2e8f0}
.sidebar-brand{padding:6px 22px 25px;text-align:center}
.sidebar-brand .brand-text{line-height:1.2}
.sidebar-brand .brand-name{font-size:15px;font-weight:800;color:#0f172a}
.sidebar-brand .brand-sub{font-size:10.5px;color:#64748b;margin-top:2px;letter-spacing:.5px;text-transform:uppercase}

.sidebar-menu{list-style:none;padding:0 14px;flex:1}
.sidebar-menu li{margin-bottom:4px}
.sidebar-menu li a{display:flex;align-items:center;gap:14px;padding:12px 15px;color:#475569;text-decoration:none;border-radius:10px;font-size:13.5px;font-weight:500;transition:all .25s ease}
.sidebar-menu li a i{width:20px;font-size:16px;text-align:center;color:#94a3b8}
.sidebar-menu li a:hover{background:#f8fafc;color:<?= $accent ?>}
.sidebar-menu li a:hover i{color:<?= $accent ?>}
.sidebar-menu li a.active{background:<?= $accent ?>;color:#fff;box-shadow:0 4px 12px rgba(0,0,0,.12)}
.sidebar-menu li a.active i{color:#fff}
.sidebar-footer{padding:15px 14px 0;border-top:1px solid #f1f5f9;margin-top:15px}
.sidebar-footer a{display:flex;align-items:center;gap:14px;padding:12px 15px;color:#64748b;text-decoration:none;border-radius:10px;font-size:13.5px;font-weight:500;transition:all .25s ease}
.sidebar-footer a i{color:#94a3b8}
.sidebar-footer a:hover{background:#fef2f2;color:#dc2626}
.sidebar-footer a:hover i{color:#dc2626}
.burger-btn{display:none;position:fixed;top:15px;left:15px;z-index:300;width:44px;height:44px;background:<?= $accent ?>;color:#fff;border:none;border-radius:10px;font-size:18px;cursor:pointer;align-items:center;justify-content:center}
.burger-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:150}
.burger-overlay.active{display:block}

.main{margin-left:270px;flex:1;padding:0 0 40px 0;min-height:100vh}
.top-bar{background:#fff;padding:18px 32px;border-bottom:1px solid #e2e8f0;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:25px}
.top-bar h1{font-size:22px;font-weight:700;color:#0f172a;display:flex;align-items:center;gap:12px}
.top-bar h1 .h-icon{width:40px;height:40px;border-radius:11px;background:linear-gradient(135deg,<?= $accent_light ?>,<?= $accent ?>);color:#fff;display:flex;align-items:center;justify-content:center;font-size:17px;box-shadow:0 6px 16px rgba(0,0,0,.15)}
.top-bar .sub{font-size:12.5px;color:#64748b;margin-top:4px;margin-left:52px}
.top-bar-actions{display:flex;align-items:center;gap:15px}

.user-chip{display:flex;align-items:center;gap:10px;padding:6px 16px 6px 6px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:30px}
.user-chip .avatar{width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,<?= $accent_light ?>,<?= $accent ?>);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:12px;flex-shrink:0}
.user-chip .info .name{font-size:13px;font-weight:600;color:#0f172a;line-height:1.2}
.user-chip .info .role{font-size:10.5px;color:<?= $accent ?>;font-weight:600;text-transform:uppercase;margin-top:2px}

.content{padding:0 32px}

/* ===== MINI STATS ===== */
.mini-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:22px}
.mini-stat{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:18px 20px;transition:all .25s ease;box-shadow:0 1px 3px rgba(0,0,0,.04);position:relative;overflow:hidden}
.mini-stat:hover{border-color:<?= $accent_light ?>;box-shadow:0 6px 16px rgba(0,0,0,.06)}
.mini-stat::before{content:'';position:absolute;top:0;left:0;width:4px;height:100%;background:<?= $accent_light ?>}
.ms-label{font-size:11.5px;color:#64748b;font-weight:600;text-transform:uppercase;letter-spacing:.4px;margin-bottom:6px}
.ms-value{font-size:24px;font-weight:800;color:#0f172a;line-height:1.1}
.ms-value.money{font-size:19px}

/* =====  FILTER BAR  ===== */
.filter-bar{
    background:#fff;
    border:1px solid #e2e8f0;
    border-radius:12px;
    padding:16px 20px;
    margin-bottom:20px;
    box-shadow:0 1px 3px rgba(0,0,0,.04);
}
.filter-bar form{
    display:flex;
    gap:10px;
    align-items:center;
    flex-wrap:nowrap;
    width:100%;
}
.filter-bar input[type="text"]{
    flex:1 1 auto;
    min-width:0;
    padding:11px 16px 11px 42px;
    border:1.5px solid #e2e8f0;
    border-radius:10px;
    font-size:13.5px;
    font-family:inherit;
    background:url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%2394a3b8' viewBox='0 0 16 16'><path d='M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z'/></svg>") no-repeat 14px center #fff;
    background-size:16px;
    color:#1e293b;
    transition:border-color .2s, box-shadow .2s;
}
.filter-bar input[type="text"]:focus{outline:none;border-color:<?= $accent_light ?>;box-shadow:0 0 0 4px rgba(20,184,166,.1)}

.filter-bar select{
    flex:0 1 180px;
    min-width:140px;
    padding:11px 38px 11px 14px;
    border:1.5px solid #e2e8f0;
    border-radius:10px;
    font-size:13.5px;
    font-weight:500;
    font-family:inherit;
    color:#1e293b;
    cursor:pointer;
    transition:border-color .2s, box-shadow .2s;
    appearance:none;
    -webkit-appearance:none;
    -moz-appearance:none;
    background:#fff url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%230f766e' viewBox='0 0 16 16'><path d='M8 11L3 6h10z'/></svg>") no-repeat right 14px center;
    background-size:12px;
    text-overflow:ellipsis;
}
.filter-bar select:hover{border-color:<?= $accent_light ?>}
.filter-bar select:focus{outline:none;border-color:<?= $accent_light ?>;box-shadow:0 0 0 4px rgba(20,184,166,.1)}
.filter-bar select option{padding:10px 14px;background:#fff;color:#1e293b;font-size:13.5px}
.filter-bar select option:checked{background:<?= $accent ?> !important;color:#fff !important;font-weight:600}
.filter-bar .btn{flex:0 0 auto;white-space:nowrap}

.btn{padding:11px 22px;border-radius:10px;font-size:13.5px;font-weight:600;text-decoration:none;display:inline-flex;align-items:center;justify-content:center;gap:8px;border:none;cursor:pointer;transition:all .2s;font-family:inherit}
.btn-primary{background:<?= $accent ?>;color:#fff;box-shadow:0 4px 12px rgba(15,118,110,.15)}
.btn-primary:hover{filter:brightness(1.1);transform:translateY(-1px)}
.btn-light{background:#fff;color:#64748b;border:1.5px solid #e2e8f0}
.btn-light:hover{background:#f8fafc;border-color:#cbd5e1;color:#334155}

.table-card{background:#fff;border-radius:14px;border:1px solid #e2e8f0;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.04)}
.table-header{padding:18px 24px;border-bottom:1px solid #f1f5f9;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px}
.table-header h3{font-size:15px;font-weight:700;color:#0f172a;display:flex;align-items:center;gap:9px}
.table-header h3 i{color:<?= $accent ?>;font-size:15px}
.table-header .count-badge{background:<?= $accent ?>;color:#fff;font-size:11px;font-weight:700;padding:3px 11px;border-radius:20px;margin-left:6px}
.table-scroll{overflow-x:auto;-webkit-overflow-scrolling:touch}
table{width:100%;border-collapse:collapse;font-size:13px;min-width:960px}
thead th{text-align:left;padding:12px 20px;background:#f8fafc;color:#64748b;font-weight:600;font-size:11px;text-transform:uppercase;letter-spacing:.5px;border-bottom:1px solid #f1f5f9;white-space:nowrap}
tbody td{padding:14px 20px;border-bottom:1px solid #f1f5f9;color:#334155;vertical-align:middle}
tbody tr:last-child td{border-bottom:none}
tbody tr:hover{background:#f8fafc}
.patient-name{font-weight:600;color:#0f172a}
.invoice-num{font-family:'Courier New',monospace;font-weight:700;color:<?= $accent ?>;font-size:12px}
.amount{font-weight:600;color:#0f172a}
.date-cell{font-size:12.5px;color:#64748b}

.paid-amount{color:#10b981;font-weight:700;font-size:12.5px}
.balance-amount{color:#dc2626;font-weight:800;font-size:13px}
.balance-amount.zero{color:#10b981}

.status-cell{display:flex;flex-direction:column;align-items:center;gap:6px;min-width:130px}
.status-badge{display:inline-flex;align-items:center;justify-content:center;gap:5px;padding:4px 11px;border-radius:20px;font-size:11px;font-weight:600;white-space:nowrap;line-height:1.2}
.status-badge.Paid{background:#d1fae5;color:#065f46}
.status-badge.Pending{background:#fef3c7;color:#92400e}
.status-badge.Processing{background:#fef3c7;color:#92400e}
.status-badge.Partial{background:#fef3c7;color:#92400e}
.status-badge i{font-size:8px;flex-shrink:0}

.pay-progress{display:flex;flex-direction:column;gap:4px;width:110px;max-width:100%}
.pay-progress .bar{height:6px;border-radius:3px;background:#f1f5f9;overflow:hidden}
.pay-progress .bar-fill{height:100%;background:linear-gradient(90deg,#10b981,#14b8a6);border-radius:3px;transition:width .3s}
.pay-progress .bar-fill.partial{background:linear-gradient(90deg,#f59e0b,#fbbf24)}
.pay-progress .txt{font-size:10.5px;font-weight:700;color:#64748b;text-align:center}

.btn-process{display:inline-flex;align-items:center;gap:6px;padding:7px 14px;background:<?= $accent ?>;color:#fff;border-radius:8px;font-size:12px;font-weight:600;text-decoration:none;transition:all .2s;white-space:nowrap;font-family:inherit;border:none;cursor:pointer}
.btn-process:hover{filter:brightness(1.1);transform:translateY(-1px);box-shadow:0 4px 10px rgba(0,0,0,.2);color:#fff}
.btn-process.paid{background:#10b981}
.btn-process.partial{background:#f59e0b}
.btn-process.partial:hover{background:#d97706}

.empty{text-align:center;padding:60px 20px;color:#94a3b8}
.empty i{font-size:44px;color:#cbd5e1;display:block;margin-bottom:14px}
.empty strong{color:#475569;display:block;margin-bottom:6px;font-size:15px}

/* =====  PAGINATION  ===== */
.pagination{display:flex;justify-content:space-between;align-items:center;padding:16px 24px;border-top:1px solid #f1f5f9;flex-wrap:wrap;gap:10px;background:#fcfdfd}
.pagination .pg-info{font-size:12.5px;color:#64748b}
.pagination .pg-info strong{color:#0f172a;font-weight:700}
.pagination .pg-links{display:flex;gap:6px;align-items:center;flex-wrap:wrap}
.pagination .pg-link{padding:7px 13px;border-radius:8px;font-size:12.5px;font-weight:600;text-decoration:none;color:#475569;background:#fff;border:1.5px solid #e2e8f0;transition:all .2s;display:inline-flex;align-items:center;gap:5px}
.pagination .pg-link:hover{border-color:<?= $accent_light ?>;color:<?= $accent ?>;background:#f0fdfa}
.pagination .pg-link.active{background:<?= $accent ?>;color:#fff;border-color:<?= $accent ?>;box-shadow:0 4px 10px rgba(15,118,110,.15)}
.pagination .pg-link.disabled{opacity:.4;cursor:not-allowed;pointer-events:none}
.pagination .pg-ellipsis{padding:7px 4px;color:#94a3b8;font-size:12.5px}

@media(max-width:1100px){
    .mini-stats{grid-template-columns:repeat(2,1fr)}
}

@media(max-width:820px){
    .filter-bar form{flex-wrap:wrap}
    .filter-bar input[type="text"]{flex:1 1 100%}
    .filter-bar select{flex:1 1 calc(50% - 5px);min-width:0}
    .filter-bar .btn{flex:1 1 calc(50% - 5px);min-width:0}
}

@media(max-width:768px){
    .sidebar{transform:translateX(-100%)}
    .sidebar.open{transform:translateX(0)}
    .burger-btn{display:flex}
    .main{margin-left:0}
    .top-bar{padding:70px 18px 18px}
    .top-bar,.content{padding-left:18px;padding-right:18px}
    .top-bar h1{font-size:18px}
    .top-bar h1 .h-icon{width:36px;height:36px;font-size:15px}
    .top-bar .sub{font-size:11.5px;margin-left:0}
    .user-chip .info{display:none}
    .user-chip{padding:5px}
    .mini-stats{grid-template-columns:1fr}

    .filter-bar{padding:14px}
    .filter-bar form{flex-direction:column;align-items:stretch;gap:10px;}
    .filter-bar input[type="text"],
    .filter-bar select,
    .filter-bar .btn{flex:1 1 auto;width:100%;min-width:0;}

    table{font-size:12px;min-width:820px}
    thead th{padding:10px 12px;font-size:10px}
    tbody td{padding:12px 12px}

    .status-cell{min-width:110px;gap:4px}
    .status-badge{padding:3px 9px;font-size:10.5px}
    .pay-progress{width:90px}
    .pay-progress .bar{height:5px}
    .pay-progress .txt{font-size:10px}
    .btn-process{padding:6px 11px;font-size:11px}

    .pagination{flex-direction:column;align-items:stretch;text-align:center}
    .pagination .pg-links{justify-content:center}
}

@media(max-width:480px){
    .content{padding:0 14px}
    .filter-bar{padding:12px}
    .filter-bar input[type="text"]{padding:11px 14px 11px 40px;font-size:13px}
    .filter-bar select{padding:11px 34px 11px 12px;font-size:13px;background-position:right 12px center}
    .filter-bar .btn{padding:11px 16px;font-size:13px}

    table{font-size:11.5px;min-width:780px}
    thead th{padding:9px 10px;font-size:9.5px;letter-spacing:.3px}
    tbody td{padding:10px 10px}
    .invoice-num{font-size:11px}
    .patient-name{font-size:12.5px}

    .status-cell{min-width:100px}
    .status-badge{padding:3px 8px;font-size:10px;gap:4px}
    .status-badge i{font-size:7px}
    .pay-progress{width:80px}
    .pay-progress .txt{font-size:9.5px}
    .btn-process{padding:5px 10px;font-size:10.5px}
}
</style>
</head>
<body>

<button class="burger-btn" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
<div class="burger-overlay" id="burgerOverlay" onclick="toggleSidebar()"></div>

<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="brand-text">
            <div class="brand-name"><?= $sidebar_brand ?></div>
            <div class="brand-sub"><?= $sidebar_sub ?></div>
        </div>
    </div>
    <ul class="sidebar-menu">
        <li><a href="<?= $dash_url ?>"><i class="fas fa-gauge-high"></i> <span>Dashboard</span></a></li>
        <?php if ($is_frontdesk): ?>
            <li><a href="patients.php"><i class="fas fa-hospital-user"></i> <span>Patient Records</span></a></li>
            <li><a href="add_patient.php"><i class="fas fa-user-plus"></i> <span>Register & Bill</span></a></li>
        <?php endif; ?>
        <?php if ($is_billing): ?>
            <li><a href="patients.php"><i class="fas fa-hospital-user"></i> <span>Patient Records</span></a></li>
        <?php endif; ?>
        <li><a href="billing_records.php" class="active"><i class="fas fa-file-invoice-dollar"></i> <span>Billing Records</span></a></li>
    </ul>
    <div class="sidebar-footer">
        <a href="logout.php"><i class="fas fa-right-from-bracket"></i> <span>Logout</span></a>
    </div>
</aside>

<main class="main">

<div class="top-bar">
    <div>
        <h1><span class="h-icon"><i class="fas fa-file-invoice-dollar"></i></span> <?= $is_cashier_only ? 'Bills to Collect' : 'Billing Records' ?></h1>
        <div class="sub">
            <?= $is_cashier_only
                ? 'Not-paid bills (Pending + Partial). I-click ang Process para mag-receive ng bayad.'
                : 'All patient billing transactions — search, filter, and process payments.' ?>
        </div>
    </div>
    <div class="top-bar-actions">
        <div class="user-chip">
            <div class="avatar"><?= $initials ?></div>
            <div class="info">
                <div class="name"><?= htmlspecialchars($full_name) ?></div>
                <div class="role"><?= htmlspecialchars($user_role) ?></div>
            </div>
        </div>
    </div>
</div>

<div class="content">

<div class="mini-stats">
    <div class="mini-stat">
        <div class="ms-label"><?= $is_cashier_only ? 'Not-Paid Bills' : 'Total Records' ?></div>
        <div class="ms-value"><?= number_format($total) ?></div>
    </div>
    <div class="mini-stat">
        <div class="ms-label">Pending</div>
        <div class="ms-value"><?= number_format($pending) ?></div>
    </div>
    <div class="mini-stat">
        <div class="ms-label"><?= $is_cashier_only ? 'Partial (may bayad na)' : 'Paid' ?></div>
        <div class="ms-value"><?= number_format($partial) ?></div>
    </div>
    <div class="mini-stat">
        <div class="ms-label"><?= $is_cashier_only ? 'Balance to Collect' : 'Total Amount' ?></div>
        <div class="ms-value money">₱ <?= number_format($total_amount, 2) ?></div>
    </div>
</div>

<div class="filter-bar">
    <form method="GET">
        <input type="text" name="q" placeholder="Search patient, service, or code..." value="<?= htmlspecialchars($search) ?>">
        <select name="status">
            <option value="">All Status</option>
            <option value="Pending" <?= $status_filter==='Pending'?'selected':'' ?>>Pending</option>
            <option value="Processing" <?= $status_filter==='Processing'?'selected':'' ?>>Partial</option>
            <?php if (!$is_cashier_only): ?>
                <option value="Paid" <?= $status_filter==='Paid'?'selected':'' ?>>Paid</option>
            <?php endif; ?>
        </select>
        <select name="category">
            <option value="">All Categories</option>
            <?php foreach ($billing_categories as $catName): ?>
                <option value="<?= htmlspecialchars($catName) ?>" <?= $category_filter === $catName ? 'selected' : '' ?>>
                    <?= htmlspecialchars($catName) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button class="btn btn-primary" type="submit"><i class="fas fa-filter"></i> Filter</button>
        <?php if ($search !== '' || $status_filter !== '' || $category_filter !== ''): ?>
            <a href="billing_records.php" class="btn btn-light"><i class="fas fa-xmark"></i> Clear</a>
        <?php endif; ?>
    </form>
</div>

<div class="table-card">
    <div class="table-header">
        <h3>
            <i class="fas fa-list"></i>
            <?= $is_cashier_only ? 'Bills to Collect' : 'Billing Transactions' ?>
            <span class="count-badge"><?= number_format($total_rows) ?></span>
        </h3>
    </div>

    <?php if (empty($records)): ?>
        <div class="empty">
            <i class="fas <?= $is_cashier_only ? 'fa-circle-check' : 'fa-file-invoice' ?>"></i>
            <strong><?= $is_cashier_only ? 'All caught up!' : 'No billing records found.' ?></strong>
            <div><?= $is_cashier_only ? 'Walang pending payments na kailangang i-process.' : 'Try adjusting your filters or search term.' ?></div>
        </div>
    <?php else: ?>
        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th>Invoice</th>
                        <th>Date</th>
                        <th>Patient</th>
                        <th>Service Description</th>
                        <th>Category</th>
                        <th style="text-align:right;">Total</th>
                        <th style="text-align:right;">Paid</th>
                        <th style="text-align:right;">Balance</th>
                        <th style="text-align:center;">Status</th>
                        <?php if ($is_billing): ?>
                            <th style="text-align:center;">Action</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($records as $rec):
                    $total_amt  = (float)$rec['total_amount'];
                    $paid_amt   = (float)($rec['amount_paid'] ?? 0);
                    $balance    = max(0, $total_amt - $paid_amt);
                    $pct        = $total_amt > 0 ? min(100, ($paid_amt / $total_amt) * 100) : 0;
                    $is_partial = ($paid_amt > 0 && $balance > 0);
                    $display_status = $is_partial ? 'Partial' : $rec['status'];
                ?>
                    <tr>
                        <td><span class="invoice-num">#<?= str_pad($rec['id'], 5, '0', STR_PAD_LEFT) ?></span></td>
                        <td><span class="date-cell"><?= date('M d, Y', strtotime($rec['date'])) ?></span></td>
                        <td>
                            <div class="patient-name"><?= htmlspecialchars($rec['patient_name']) ?></div>
                            <?php if (!empty($rec['patient_id'])): ?>
                                <div style="font-size:11px;color:#94a3b8;margin-top:2px;"><?= htmlspecialchars($rec['patient_id']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($rec['service_description']) ?></td>
                        <td><?= htmlspecialchars($rec['category']) ?></td>
                        <td style="text-align:right;"><span class="amount">₱ <?= number_format($total_amt, 2) ?></span></td>
                        <td style="text-align:right;"><span class="paid-amount">₱ <?= number_format($paid_amt, 2) ?></span></td>
                        <td style="text-align:right;">
                            <span class="balance-amount <?= $balance <= 0 ? 'zero' : '' ?>">
                                ₱ <?= number_format($balance, 2) ?>
                            </span>
                        </td>
                        <td style="text-align:center;">
                            <div class="status-cell">
                                <span class="status-badge <?= $display_status ?>">
                                    <i class="fas fa-circle"></i> <?= $display_status ?>
                                </span>
                                <?php if ($is_partial): ?>
                                    <div class="pay-progress">
                                        <div class="bar"><div class="bar-fill partial" style="width:<?= $pct ?>%"></div></div>
                                        <div class="txt"><?= round($pct) ?>% paid</div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </td>
                        <?php if ($is_billing): ?>
                            <td style="text-align:center;">
                                <?php if ($balance <= 0): ?>
                                    <a href="process_payment.php?id=<?= (int)$rec['id'] ?>" class="btn-process paid" title="View payment details">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                <?php elseif ($is_partial): ?>
                                    <a href="process_payment.php?id=<?= (int)$rec['id'] ?>" class="btn-process partial" title="Continue payment">
                                        <i class="fas fa-circle-half-stroke"></i> Continue
                                    </a>
                                <?php else: ?>
                                    <a href="process_payment.php?id=<?= (int)$rec['id'] ?>" class="btn-process" title="Process payment">
                                        <i class="fas fa-money-bill-wave"></i> Process
                                    </a>
                                <?php endif; ?>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <div class="pg-info">
                Showing <strong><?= number_format($page_start) ?></strong>–<strong><?= number_format($page_end) ?></strong>
                of <strong><?= number_format($total_rows) ?></strong> records
            </div>
            <div class="pg-links">
                <a href="<?= pg_url($qs_params, $page - 1) ?>" class="pg-link <?= $page <= 1 ? 'disabled' : '' ?>">
                    <i class="fas fa-chevron-left"></i> Prev
                </a>

                <?php
                $start_page = max(1, $page - 2);
                $end_page   = min($total_pages, $page + 2);

                if ($start_page > 1) {
                    echo '<a href="' . htmlspecialchars(pg_url($qs_params, 1)) . '" class="pg-link">1</a>';
                    if ($start_page > 2) echo '<span class="pg-ellipsis">…</span>';
                }

                for ($p = $start_page; $p <= $end_page; $p++) {
                    $cls = $p === $page ? 'pg-link active' : 'pg-link';
                    echo '<a href="' . htmlspecialchars(pg_url($qs_params, $p)) . '" class="' . $cls . '">' . $p . '</a>';
                }

                if ($end_page < $total_pages) {
                    if ($end_page < $total_pages - 1) echo '<span class="pg-ellipsis">…</span>';
                    echo '<a href="' . htmlspecialchars(pg_url($qs_params, $total_pages)) . '" class="pg-link">' . $total_pages . '</a>';
                }
                ?>

                <a href="<?= pg_url($qs_params, $page + 1) ?>" class="pg-link <?= $page >= $total_pages ? 'disabled' : '' ?>">
                    Next <i class="fas fa-chevron-right"></i>
                </a>
            </div>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

</div>
</main>

<script>
function toggleSidebar(){document.getElementById('sidebar').classList.toggle('open');document.getElementById('burgerOverlay').classList.toggle('active');}
</script>
</body>
</html>