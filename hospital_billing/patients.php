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
$is_cashier_only  = (strpos($r, 'cashier') !== false) && (strpos($r, 'billing') === false);
$is_billing_staff = $is_billing && !$is_cashier_only;

if (!$is_frontdesk && !$is_billing) { header('Location: login.php?msg=access_denied'); exit; }

$sidebar_brand = $is_billing ? 'Billing Staff' : 'Front Desk';
$sidebar_sub   = $is_billing ? 'Billing Portal' : 'Receptionist';
$accent        = '#0f766e';
$accent_light  = '#14b8a6';
$dash_url      = $is_billing ? 'billing_dashboard.php' : 'frontdesk_dashboard.php';

/* Who can REGISTER new patients? */
$can_register = $is_frontdesk;
$can_add_txn  = $is_frontdesk || $is_billing_staff;

$search   = trim($_GET['q'] ?? '');
$sort     = trim($_GET['sort'] ?? 'id_desc');

$sql = "SELECT 
            MIN(p.id) AS id,
            MAX(p.id) AS max_id,
            MIN(p.patient_code) AS patient_code,
            p.full_name,
            MAX(p.contact_number) AS contact_number,
            MAX(p.notes) AS notes,
            COUNT(b.id) AS bill_count,
            COALESCE(SUM(b.total_amount), 0) AS total_billed
        FROM patients p
        LEFT JOIN billing_records b ON b.patient_db_id = p.id
        WHERE 1=1";
$params = [];

$sql .= " GROUP BY p.full_name";

switch ($sort) {
    case 'name_asc':   $sql .= " ORDER BY p.full_name ASC"; break;
    case 'name_desc':  $sql .= " ORDER BY p.full_name DESC"; break;
    case 'bills_desc': $sql .= " ORDER BY bill_count DESC"; break;
    case 'total_desc': $sql .= " ORDER BY total_billed DESC"; break;
    default:           $sql .= " ORDER BY max_id DESC";
}
$sql .= " LIMIT 300";

try { 
    $stmt = $pdo->prepare($sql); 
    $stmt->execute($params); 
    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC); 
} catch (PDOException $e) { 
    $patients = []; 
}

/* Flash message */
$flash = $_SESSION['flash_patient'] ?? '';
unset($_SESSION['flash_patient']);

$initials = strtoupper(substr($full_name, 0, 2));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Patient Records</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
*{margin:0;padding:0;box-sizing:border-box}

body{
  font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;
  display:flex;min-height:100vh;color:#1e293b;
  position:relative;overflow-x:hidden;
  background:
    radial-gradient(circle at 15% 20%, rgba(20,184,166,.12) 0%, transparent 45%),
    radial-gradient(circle at 85% 80%, rgba(15,118,110,.14) 0%, transparent 50%),
    radial-gradient(circle at 50% 50%, rgba(20,184,166,.05) 0%, transparent 60%),
    linear-gradient(135deg, #f0fdfa 0%, #e6fbf7 40%, #ecfdf5 100%);
  background-attachment:fixed;
}
body::before{
  content:'';position:fixed;top:-15%;right:-12%;
  width:520px;height:520px;
  background:radial-gradient(circle, rgba(20,184,166,.18) 0%, transparent 70%);
  border-radius:50%;pointer-events:none;filter:blur(10px);z-index:0;
}
body::after{
  content:'';position:fixed;bottom:-20%;left:-12%;
  width:620px;height:620px;
  background:radial-gradient(circle, rgba(15,118,110,.15) 0%, transparent 70%);
  border-radius:50%;pointer-events:none;filter:blur(10px);z-index:0;
}

.sidebar{width:270px;background:#fff;color:#1e293b;display:flex;flex-direction:column;padding:25px 0 20px;position:fixed;top:0;left:0;height:100vh;z-index:200;overflow-y:auto;border-right:1px solid #e2e8f0}
.sidebar-brand{padding:6px 22px 25px;display:flex;justify-content:center;}
.brand-text{display:flex;align-items:center;gap:12px;}
.brand-icon-box{display:inline-flex;align-items:center;justify-content:center;width:36px;height:36px;background:<?= $accent ?>;color:#fff;border-radius:10px;font-size:18px;box-shadow:0 2px 8px rgba(15,118,110,.25);flex-shrink:0;}
.brand-text-inner{text-align:left;display:flex;flex-direction:column;justify-content:center;}
.sidebar-brand .brand-name{font-size:16px;font-weight:800;color:#0f172a;line-height:1.2;}
.sidebar-brand .brand-sub{font-size:10.5px;color:#64748b;margin-top:2px;letter-spacing:.5px;text-transform:uppercase;line-height:1.2;}

.sidebar-menu{list-style:none;padding:0 14px;flex:1}
.sidebar-menu li{margin-bottom:4px}
.sidebar-menu li a{display:flex;align-items:center;gap:14px;padding:12px 15px;color:#475569;text-decoration:none;border-radius:10px;font-size:13.5px;font-weight:500;transition:all .25s ease}
.sidebar-menu li a i{width:20px;font-size:16px;text-align:center;color:#94a3b8}
.sidebar-menu li a:hover{background:#f0fdfa;color:<?= $accent ?>}
.sidebar-menu li a:hover i{color:<?= $accent ?>}
.sidebar-menu li a.active{background:<?= $accent ?>;color:#fff;box-shadow:0 4px 12px rgba(0,0,0,.15)}
.sidebar-menu li a.active i{color:#fff}
.sidebar-footer{padding:15px 14px 0;border-top:1px solid #f1f5f9;margin-top:15px}
.sidebar-footer a{display:flex;align-items:center;gap:14px;padding:12px 15px;color:#64748b;text-decoration:none;border-radius:10px;font-size:13.5px;font-weight:500;transition:all .25s ease}
.sidebar-footer a i{color:#94a3b8}
.sidebar-footer a:hover{background:#fef2f2;color:#dc2626}
.sidebar-footer a:hover i{color:#dc2626}
.burger-btn{display:none;position:fixed;top:15px;left:15px;z-index:300;width:44px;height:44px;background:<?= $accent ?>;color:#fff;border:none;border-radius:10px;font-size:18px;cursor:pointer;align-items:center;justify-content:center}
.burger-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:150}
.burger-overlay.active{display:block}

.main{margin-left:270px;flex:1;padding:0 0 40px 0;min-height:100vh;position:relative;z-index:1}
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

.search-panel{background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:18px 20px;margin-bottom:20px;box-shadow:0 1px 3px rgba(0,0,0,.04)}
.search-row{display:grid;grid-template-columns:1fr;gap:12px;margin-bottom:12px}
.search-input-wrap{position:relative}
.search-input-wrap i.search-ico{position:absolute;left:16px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:14px;pointer-events:none}
.search-input-wrap input{width:100%;padding:12px 44px 12px 44px;border:1.5px solid #e2e8f0;border-radius:11px;font-size:13.5px;font-family:inherit;background:#fff;color:#1e293b;transition:all .2s}
.search-input-wrap input:focus{outline:none;border-color:<?= $accent_light ?>;box-shadow:0 0 0 4px rgba(20,184,166,.1)}
.search-input-wrap .clear-x{position:absolute;right:14px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:15px;cursor:pointer;padding:4px;border-radius:50%;transition:all .2s;text-decoration:none;display:none;background:none;border:none}
.search-input-wrap .clear-x:hover{color:#dc2626;background:#fef2f2}
.search-input-wrap.has-value .clear-x{display:flex;align-items:center;justify-content:center}
.search-input-wrap .spinner{position:absolute;right:44px;top:50%;transform:translateY(-50%);color:<?= $accent ?>;font-size:13px;display:none}
.search-input-wrap.loading .spinner{display:block}
.search-input-wrap.loading .clear-x{display:none}

.filter-row{display:grid;grid-template-columns:1fr;gap:10px;align-items:center}
.filter-group{position:relative;display:flex;align-items:center}
.filter-group select{width:100%;padding:12px 32px 12px 14px;border:1.5px solid #e2e8f0;border-radius:11px;font-size:13px;font-family:inherit;background:#fff;color:#1e293b;appearance:none;background-image:url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%2394a3b8' viewBox='0 0 16 16'><path d='M8 11L3 6h10z'/></svg>");background-repeat:no-repeat;background-position:right 14px center;cursor:pointer}
.filter-group select:focus{outline:none;border-color:<?= $accent_light ?>;box-shadow:0 0 0 4px rgba(20,184,166,.1)}

.table-card{background:#fff;border-radius:14px;border:1px solid #e2e8f0;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.04)}
.table-header{padding:18px 24px;border-bottom:1px solid #f1f5f9;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px}
.table-header h3{font-size:15px;font-weight:700;color:#0f172a;display:flex;align-items:center;gap:9px}
.table-header h3 i{color:<?= $accent ?>;font-size:15px}
.table-header .count-badge{background:<?= $accent ?>;color:#fff;font-size:11px;font-weight:700;padding:3px 11px;border-radius:20px;transition:background .2s}
.table-header .count-badge.no-results{background:#dc2626}
table{width:100%;border-collapse:collapse;font-size:13px}
thead th{text-align:left;padding:12px 20px;background:#f8fafc;color:#64748b;font-weight:600;font-size:11px;text-transform:uppercase;letter-spacing:.5px;border-bottom:1px solid #f1f5f9}
tbody td{padding:14px 20px;border-bottom:1px solid #f1f5f9;color:#334155;vertical-align:middle;transition:background .1s}
tbody tr:last-child td{border-bottom:none}
tbody tr:hover{background:#f8fafc}
tbody tr.hidden{display:none}
.empty{text-align:center;padding:60px 20px;color:#94a3b8}
.empty i{font-size:44px;color:#cbd5e1;display:block;margin-bottom:14px}
.empty strong{color:#475569;display:block;margin-bottom:6px;font-size:15px}
.pill{display:inline-block;padding:4px 11px;border-radius:20px;font-size:11.5px;font-weight:700;background:#ccfbf1;color:#115e59;font-family:'Courier New',monospace;letter-spacing:.3px}
.patient-name{font-weight:600;color:#0f172a}
.patient-name mark{background:#fef3c7;color:#0f172a;padding:0 3px;border-radius:3px;font-weight:800}
.notes-tag{font-size:11px;color:#7a7a9d;margin-top:3px;display:flex;align-items:center;gap:4px}
.notes-tag i{font-size:10px;color:#f59e0b}
.amount{font-weight:600;color:#0f172a}
.bills-count{display:inline-flex;align-items:center;justify-content:center;background:#f1f5f9;color:#475569;font-size:11.5px;font-weight:700;min-width:28px;height:24px;padding:0 8px;border-radius:12px}
.action-links{display:flex;gap:6px;flex-wrap:wrap;justify-content:flex-end}
.action-link{font-size:12.5px;font-weight:600;color:<?= $accent ?>;text-decoration:none;display:inline-flex;align-items:center;gap:5px;padding:7px 12px;border-radius:8px;transition:all .2s;white-space:nowrap;border:1.5px solid transparent}
.action-link:hover{background:#f0fdfa}
.action-link.primary{background:#10b981;color:#fff;box-shadow:0 4px 12px rgba(16,185,129,.25)}
.action-link.primary:hover{background:#059669;color:#fff;transform:translateY(-1px);box-shadow:0 6px 16px rgba(16,185,129,.35)}
.action-link.add{background:#ecfdf5;color:#047857;border:1.5px solid #a7f3d0}
.action-link.add:hover{background:#d1fae5;color:#065f46}

/* NEW: Edit Button Style */
.action-link.edit{background:#fffbeb;color:#d97706;border:1.5px solid #fde68a}
.action-link.edit:hover{background:#fef3c7;color:#b45309;border-color:#fcd34d}

.no-results-row{text-align:center;padding:60px 20px;color:#94a3b8;display:none}
.no-results-row.show{display:table-cell}
.no-results-row i{font-size:44px;color:#cbd5e1;display:block;margin-bottom:14px}
.no-results-row strong{color:#475569;display:block;margin-bottom:6px;font-size:15px}

.toast-container { position: fixed; top: 24px; right: 24px; z-index: 9999; display: flex; flex-direction: column; gap: 12px; max-width: 380px; width: calc(100% - 48px); pointer-events: none; }
.toast { background: #fff; border-radius: 14px; box-shadow: 0 12px 40px rgba(15,23,42,.18); border: 1px solid #e2e8f0; padding: 16px 18px; display: flex; gap: 12px; align-items: flex-start; pointer-events: auto; animation: toastIn .4s cubic-bezier(.2,.9,.3,1.1); position: relative; overflow: hidden; }
.toast::before { content: ''; position: absolute; top: 0; left: 0; width: 4px; height: 100%; background: var(--toast-accent, #14b8a6); }
.toast.success::before { background: #10b981; }
.toast.error::before { background: #dc3545; }
.toast.closing { animation: toastOut .3s ease forwards; }
.toast-icon { width: 38px; height: 38px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 16px; flex-shrink: 0; color: #fff; }
.toast.success .toast-icon { background: #10b981; }
.toast.error .toast-icon { background: #dc3545; }
.toast-content { flex: 1; min-width: 0; }
.toast-title { font-size: 13.5px; font-weight: 700; color: #0f172a; margin-bottom: 4px; }
.toast-message { font-size: 12.5px; color: #475569; line-height: 1.5; word-wrap: break-word; }
.toast-close { background: none; border: none; color: #94a3b8; cursor: pointer; font-size: 14px; padding: 4px; border-radius: 6px; transition: all .2s; flex-shrink: 0; }
.toast-close:hover { color: #dc2626; background: #fef2f2; }
@keyframes toastIn { from { opacity: 0; transform: translateX(120%); } to { opacity: 1; transform: translateX(0); } }
@keyframes toastOut { from { opacity: 1; transform: translateX(0); } to { opacity: 0; transform: translateX(120%); } }

@media (max-width: 900px) {
  .filter-row { grid-template-columns: 1fr; }
}
@media (max-width: 768px) {
  .sidebar { transform: translateX(-100%); }
  .sidebar.open { transform: translateX(0); }
  .burger-btn { display: flex; }
  .main { margin-left: 0; }
  .top-bar, .content { padding-left: 18px; padding-right: 18px; }
  .user-chip .info { display: none; }
  .user-chip { padding: 5px; }
  .search-panel { padding: 14px; }
  .filter-row { grid-template-columns: 1fr; }
  .table-card { overflow-x: auto; }
  table { font-size: 12px; min-width: 780px; }
  thead th, tbody td { padding: 11px 14px; }
  .top-bar h1 { font-size: 18px; }
  .top-bar .sub { font-size: 11.5px; margin-left: 0; }
  .top-bar h1 .h-icon { width: 36px; height: 36px; font-size: 15px; }
  .toast-container { top: 16px; right: 16px; left: 16px; width: auto; max-width: none; }
  .action-link { font-size: 11.5px; padding: 6px 10px; }
}
@media (max-width: 480px) {
  .content { padding: 0 16px; }
  .search-input-wrap input { padding: 11px 40px 11px 40px; font-size: 13px; }
  .search-input-wrap i.search-ico { left: 14px; font-size: 13px; }
  .filter-group select { padding: 11px 30px 11px 12px; font-size: 12.5px; }
  .action-link { font-size: 11px; padding: 5px 9px; gap: 4px; }
  .action-link i { font-size: 10px; }
}
@media(max-width:520px){body::before,body::after{opacity:.6}}
</style>
</head>
<body>

<button class="burger-btn" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
<div class="burger-overlay" id="burgerOverlay" onclick="toggleSidebar()"></div>

<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="brand-text">
            <span class="brand-icon-box"><i class="fas fa-heart-pulse"></i></span>
            <div class="brand-text-inner">
                <div class="brand-name">Hospital Billing</div>
                <div class="brand-sub"><?= $sidebar_sub ?></div>
            </div>
        </div>
    </div>
    <ul class="sidebar-menu">
        <li><a href="<?= $dash_url ?>"><i class="fas fa-gauge-high"></i> <span>Dashboard</span></a></li>
        <li><a href="patients.php" class="active"><i class="fas fa-hospital-user"></i> <span>Patient Records</span></a></li>
        <?php if ($can_register): ?>
            <li><a href="add_patient.php"><i class="fas fa-user-plus"></i> <span>Register &amp; Bill</span></a></li>
        <?php endif; ?>
        <li><a href="billing_records.php"><i class="fas fa-file-invoice-dollar"></i> <span>Billing Records</span></a></li>
        <li><a href="transaction_history.php"><i class="fas fa-clock-rotate-left"></i> <span>Transaction History</span></a></li>
    </ul>
    <div class="sidebar-footer">
        <a href="logout.php"><i class="fas fa-right-from-bracket"></i> <span>Logout</span></a>
    </div>
</aside>

<main class="main">

<div class="top-bar">
    <div>
        <h1><span class="h-icon"><i class="fas fa-hospital-user"></i></span> Patient Records</h1>
        <div class="sub">Master list of all registered patients. Each patient can have multiple bills under the same profile.</div>
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

<div class="search-panel">
    <div class="search-row">
        <div class="search-input-wrap <?= $search !== '' ? 'has-value' : '' ?>" id="searchWrap">
            <i class="fas fa-search search-ico"></i>
            <input type="text" id="searchInput"
                   placeholder="Type name, patient code, or contact number…"
                   value="<?= htmlspecialchars($search) ?>"
                   autocomplete="off"
                   autofocus>
            <i class="fas fa-circle-notch fa-spin spinner"></i>
            <button type="button" class="clear-x" id="clearBtn" title="Clear search">
                <i class="fas fa-circle-xmark"></i>
            </button>
        </div>
    </div>

    <div class="filter-row">
        <div class="filter-group">
            <select id="sortFilter">
                <option value="id_desc"     <?= $sort==='id_desc'?'selected':'' ?>>Newest First</option>
                <option value="name_asc"    <?= $sort==='name_asc'?'selected':'' ?>>Name (A–Z)</option>
                <option value="name_desc"   <?= $sort==='name_desc'?'selected':'' ?>>Name (Z–A)</option>
                <option value="bills_desc"  <?= $sort==='bills_desc'?'selected':'' ?>>Most Bills</option>
                <option value="total_desc"  <?= $sort==='total_desc'?'selected':'' ?>>Highest Billed</option>
            </select>
        </div>
    </div>
</div>

<div class="table-card">
    <div class="table-header">
        <h3><i class="fas fa-list"></i> All Patients <span class="count-badge" id="countBadge"><?= count($patients) ?></span></h3>
    </div>

    <div style="overflow-x:auto;">
        <table id="patientTable">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Patient Name</th>
                    <th>Contact</th>
                    <th style="text-align:center;">Bills</th>
                    <th style="text-align:right;">Total Billed</th>
                    <th style="text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody id="tableBody">
            <?php foreach ($patients as $p): ?>
                <tr data-search="<?= htmlspecialchars(strtolower($p['full_name'] . ' ' . ($p['patient_code'] ?? '') . ' ' . ($p['contact_number'] ?? ''))) ?>"
                    data-name="<?= htmlspecialchars($p['full_name']) ?>"
                    data-code="<?= htmlspecialchars($p['patient_code'] ?? '') ?>"
                    data-phone="<?= htmlspecialchars($p['contact_number'] ?? '') ?>">
                    <td><span class="pill"><?= htmlspecialchars($p['patient_code'] ?? '—') ?></span></td>
                    <td>
                        <div class="patient-name" data-name="<?= htmlspecialchars($p['full_name']) ?>"><?= htmlspecialchars($p['full_name']) ?></div>
                        <?php if (!empty($p['notes'])): ?>
                            <div class="notes-tag"><i class="fas fa-note-sticky"></i> <?= htmlspecialchars(mb_strimwidth($p['notes'], 0, 60, '…')) ?></div>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($p['contact_number'] ?? '—') ?></td>
                    <td style="text-align:center;"><span class="bills-count"><?= (int)$p['bill_count'] ?></span></td>
                    <td style="text-align:right;"><span class="amount">₱ <?= number_format((float)$p['total_billed'], 2) ?></span></td>
                    <td>
                        <div class="action-links">
                            <?php if ($can_add_txn): ?>
                                <a class="action-link add"
                                   href="add_transaction.php?patient_id=<?= (int)$p['id'] ?>"
                                   title="Add new billing transaction for this patient">
                                    <i class="fas fa-plus"></i> Add Bill
                                </a>
                            <?php endif; ?>

                            <!-- PINALITAN: History -> Edit -->
                            <a class="action-link edit"
                               href="edit_patient.php?id=<?= (int)$p['id'] ?>"
                               title="Edit patient information">
                                <i class="fas fa-pen-to-square"></i> Edit
                            </a>

                            <a class="action-link primary" href="billing_records.php?q=<?= urlencode($p['full_name']) ?>" title="View billing records">
                                <i class="fas fa-eye"></i> View
                            </a>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>

            <tr id="noResultsRow" class="no-results-row">
                <td colspan="6">
                    <i class="fas fa-magnifying-glass"></i>
                    <strong>No matching patients found.</strong>
                    <div>Try a different name, code, or number.</div>
                </td>
            </tr>
            </tbody>
        </table>

        <?php if (empty($patients)): ?>
            <div class="empty" id="initialEmpty">
                <i class="fas fa-hospital-user"></i>
                <strong>No patients yet.</strong>
                <div>There are currently no patients registered in the system.</div>
            </div>
        <?php endif; ?>
    </div>
</div>

</div>
</main>

<div class="toast-container" id="toastContainer"></div>

<script>
function toggleSidebar(){document.getElementById('sidebar').classList.toggle('open');document.getElementById('burgerOverlay').classList.toggle('active');}

function showToast(title, message, type, duration = 5000) {
    const container = document.getElementById('toastContainer');
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
    toast.innerHTML = `
        <div class="toast-icon"><i class="fas ${icon}"></i></div>
        <div class="toast-content">
            <div class="toast-title">${title}</div>
            <div class="toast-message">${message}</div>
        </div>
        <button class="toast-close" onclick="dismissToast(this)"><i class="fas fa-xmark"></i></button>
    `;
    container.appendChild(toast);
    setTimeout(() => dismissToast(toast.querySelector('.toast-close')), duration);
}

function dismissToast(btn) {
    const toast = btn.closest('.toast');
    if (!toast || toast.classList.contains('closing')) return;
    toast.classList.add('closing');
    setTimeout(() => toast.remove(), 300);
}

<?php if ($flash): ?>
document.addEventListener('DOMContentLoaded', function() {
    showToast('Success', <?= json_encode($flash) ?>, 'success', 7000);
});
<?php endif; ?>

const input        = document.getElementById('searchInput');
const clearBtn     = document.getElementById('clearBtn');
const searchWrap   = document.getElementById('searchWrap');
const tbody        = document.getElementById('tableBody');
const countBadge   = document.getElementById('countBadge');
const noResultsRow = document.getElementById('noResultsRow');
const initialEmpty = document.getElementById('initialEmpty');

const allRows = Array.from(tbody.querySelectorAll('tr:not(#noResultsRow)'));

function debounce(fn, delay) {
    let t;
    return function(...args) {
        clearTimeout(t);
        t = setTimeout(() => fn.apply(this, args), delay);
    };
}

function highlightMatch(row, query) {
    const nameEl = row.querySelector('.patient-name');
    if (!nameEl) return;
    const original = nameEl.dataset.name || nameEl.textContent;
    if (!query) { nameEl.textContent = original; return; }
    const idx = original.toLowerCase().indexOf(query.toLowerCase());
    if (idx === -1) { nameEl.textContent = original; return; }
    const before = original.slice(0, idx);
    const match  = original.slice(idx, idx + query.length);
    const after  = original.slice(idx + query.length);
    nameEl.innerHTML = escapeHtml(before) + '<mark>' + escapeHtml(match) + '</mark>' + escapeHtml(after);
}

function escapeHtml(s) {
    return String(s || '')
        .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
        .replace(/"/g,'&quot;').replace(/'/g,'&#39;');
}

function runLiveSearch() {
    const q = input.value.trim().toLowerCase();
    let visibleCount = 0;
    searchWrap.classList.add('loading');

    allRows.forEach(row => {
        const haystack  = (row.dataset.search || '').toLowerCase();
        const nameField = (row.dataset.name   || '').toLowerCase();
        const codeField = (row.dataset.code   || '').toLowerCase();
        const phoneField= (row.dataset.phone  || '').toLowerCase();

        const matches = q === '' ||
            haystack.includes(q) ||
            nameField.includes(q) ||
            codeField.includes(q) ||
            phoneField.includes(q);

        if (matches) {
            row.classList.remove('hidden');
            highlightMatch(row, q);
            visibleCount++;
        } else {
            row.classList.add('hidden');
            highlightMatch(row, '');
        }
    });

    noResultsRow.classList.toggle('show', visibleCount === 0 && allRows.length > 0);
    countBadge.textContent = visibleCount;
    countBadge.classList.toggle('no-results', visibleCount === 0);
    if (initialEmpty && visibleCount > 0) initialEmpty.style.display = 'none';

    setTimeout(() => searchWrap.classList.remove('loading'), 120);
    searchWrap.classList.toggle('has-value', input.value.trim() !== '');
}

const debouncedSearch = debounce(runLiveSearch, 100);
input.addEventListener('input', debouncedSearch);
input.addEventListener('keydown', e => { if (e.key === 'Enter') e.preventDefault(); });
clearBtn.addEventListener('click', () => { input.value = ''; runLiveSearch(); input.focus(); });

const sortFilter = document.getElementById('sortFilter');
sortFilter.addEventListener('change', function() {
    const sortVal = this.value;
    const rows = Array.from(tbody.querySelectorAll('tr:not(#noResultsRow)'));
    rows.sort((a, b) => {
        const nameA  = (a.dataset.name || '').toLowerCase();
        const nameB  = (b.dataset.name || '').toLowerCase();
        const billsA = parseInt(a.querySelector('.bills-count')?.textContent || '0', 10);
        const billsB = parseInt(b.querySelector('.bills-count')?.textContent || '0', 10);
        const totalA = parseFloat((a.querySelector('.amount')?.textContent || '0').replace(/[^0-9.]/g, '')) || 0;
        const totalB = parseFloat((b.querySelector('.amount')?.textContent || '0').replace(/[^0-9.]/g, '')) || 0;
        switch (sortVal) {
            case 'name_asc':   return nameA.localeCompare(nameB);
            case 'name_desc':  return nameB.localeCompare(nameA);
            case 'bills_desc': return billsB - billsA;
            case 'total_desc': return totalB - totalA;
            default:           return 0;
        }
    });
    rows.forEach(r => tbody.appendChild(r));
    tbody.appendChild(noResultsRow);
    runLiveSearch();
});

if (input.value.trim() !== '') runLiveSearch();
</script>
</body>
</html>