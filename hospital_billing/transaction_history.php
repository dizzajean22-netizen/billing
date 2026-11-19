<?php
/* ============================================================
   Access: billing / cashier / reception / front-desk.
   Read-only — no DB writes.
   ============================================================ */

if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php'); exit;
}
require_once 'config.php';
require_once 'explanation_helper.php';

$user_id   = $_SESSION['user_id'] ?? 0;
$full_name = $_SESSION['full_name'] ?? 'User';
$user_role = $_SESSION['role'] ?? 'User';
$r = strtolower(trim($user_role));

$is_frontdesk = strpos($r,'reception') !== false || strpos($r,'front') !== false
             || in_array($r, ['receptionist','reception','front desk receptionist'], true);
$is_billing   = in_array($r, ['billing','cashier'], true)
             || strpos($r,'billing') !== false || strpos($r,'cashier') !== false;

if (!$is_frontdesk && !$is_billing) { header('Location: login.php?msg=access_denied'); exit; }

$sidebar_sub  = $is_billing ? 'BILLING PORTAL' : 'RECEPTIONIST';
$accent       = '#0f766e';
$accent_light = '#14b8a6';
$dash_url     = $is_billing ? 'billing_dashboard.php' : 'frontdesk_dashboard.php';

$q             = trim($_GET['q'] ?? '');
$patient_db_id = (int)($_GET['patient_db_id'] ?? 0);

$sql    = "SELECT * FROM billing_records WHERE amount_paid > 0";
$params = [];

if ($patient_db_id > 0) {
    $sql .= " AND patient_db_id = ?";
    $params[] = $patient_db_id;
}
if ($q !== '') {
    $sql .= " AND (patient_name LIKE ? OR patient_id LIKE ? OR service_description LIKE ?
                   OR CAST(id AS CHAR) LIKE ?)";
    $lk = "%$q%";
    $params[] = $lk; $params[] = $lk; $params[] = $lk; $params[] = $lk;
}
$sql .= " ORDER BY date DESC, id DESC LIMIT 2000";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $raw_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $raw_records = []; }

$transactions = [];
foreach ($raw_records as $rec) {
    $rmk = (string)($rec['remarks'] ?? '');
    $re  = '/\[Payment\s+([^\]]+)\]\s*([^—\-\n]*?)\s*[—\-]\s*₱\s*([0-9\.,]+)(?:\s*\(Balance:\s*₱\s*([0-9\.,]+)\))?/u';

    if (preg_match_all($re, $rmk, $pm, PREG_SET_ORDER)) {
        foreach ($pm as $m) {
            $pay_date_str = trim($m[1]);
            $method_str   = trim($m[2] ?? '');
            $amount       = (float)str_replace(',', '', $m[3]);
            $bal_after    = isset($m[4]) && $m[4] !== ''
                            ? (float)str_replace(',', '', $m[4]) : null;

            if ($method_str === '' || strtolower($method_str) === 'cash') $method_str = 'Cash';

            $transactions[] = [
                'record'      => $rec,
                'pay_date'    => $pay_date_str,
                'pay_ts'      => strtotime($pay_date_str) ?: strtotime($rec['date']),
                'method'      => $method_str,
                'amount'      => $amount,
                'balance_after' => $bal_after,
            ];
        }
    } else {
        $transactions[] = [
            'record'        => $rec,
            'pay_date'      => date('M d, Y g:i A', strtotime($rec['date'])),
            'pay_ts'        => strtotime($rec['date']),
            'method'        => 'Cash',
            'amount'        => (float)($rec['amount_paid'] ?? 0),
            'balance_after' => max(0, (float)$rec['total_amount'] - (float)($rec['amount_paid'] ?? 0)),
        ];
    }
}

usort($transactions, fn($a, $b) => $b['pay_ts'] <=> $a['pay_ts']);

$display_limit = 300;
$shown = array_slice($transactions, 0, $display_limit);

/* ========= FETCH PATIENT UNDERSTANDING ========= */
$unique_services = [];
foreach ($shown as $tx) {
    $svc = $tx['record']['service_description'] ?? '';
    if (!empty($svc)) $unique_services[$svc] = true;
}
$explanations = fetch_patient_explanations($pdo, array_keys($unique_services));

/* ===== SUMMARY STATS ===== */
$sum_amount = 0.0;
$patients   = [];
foreach ($transactions as $t) {
    $sum_amount += $t['amount'];
    $key = ($t['record']['patient_db_id'] ?? 'x') . '|' . strtolower(trim($t['record']['patient_name'] ?? ''));
    $patients[$key] = $t['record']['patient_name'] ?? 'Unknown';
}

/* ===== PATIENT BANNER ===== */
$patient_info = null;
if ($patient_db_id > 0) {
    try {
        $ps = $pdo->prepare("SELECT patient_name, patient_id, total_amount, amount_paid, status
                             FROM billing_records WHERE patient_db_id = ?
                             ORDER BY date DESC, id DESC LIMIT 1");
        $ps->execute([$patient_db_id]);
        $patient_info = $ps->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {}
}

$initials = strtoupper(substr($full_name, 0, 2));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Transaction History · Hospital Billing System</title>
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

.sidebar{width:270px;background:#fff;display:flex;flex-direction:column;padding:25px 0 20px;position:fixed;top:0;left:0;height:100vh;z-index:200;overflow-y:auto;border-right:1px solid #e2e8f0}
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

.mini-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:22px}
.mini-stat{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:18px 20px;transition:all .25s ease;box-shadow:0 1px 3px rgba(0,0,0,.04);position:relative;overflow:hidden}
.mini-stat:hover{border-color:<?= $accent_light ?>;box-shadow:0 6px 16px rgba(0,0,0,.06)}
.mini-stat::before{content:'';position:absolute;top:0;left:0;width:4px;height:100%;background:<?= $accent_light ?>}
.ms-label{font-size:11.5px;color:#64748b;font-weight:600;text-transform:uppercase;letter-spacing:.4px;margin-bottom:6px}
.ms-value{font-size:24px;font-weight:800;color:#0f172a;line-height:1.1}
.ms-value.money{font-size:19px}

.patient-banner{background:#fff;border:1px solid #e2e8f0;border-left:4px solid <?= $accent ?>;border-radius:12px;padding:16px 22px;margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:14px;box-shadow:0 1px 3px rgba(0,0,0,.04)}
.patient-banner .info-block .name{font-size:15px;font-weight:800;color:#0f172a}
.patient-banner .info-block .pid{font-size:11.5px;color:#64748b;margin-top:3px}
.patient-banner .amounts{display:flex;gap:22px;flex-wrap:wrap}
.patient-banner .amounts .item .lbl{font-size:10.5px;color:#94a3b8;text-transform:uppercase;letter-spacing:.4px;font-weight:600}
.patient-banner .amounts .item .val{font-size:15px;font-weight:800;color:#0f172a;margin-top:2px}
.patient-banner .amounts .item.balance .val{color:#dc2626}

.filter-bar{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:16px 20px;margin-bottom:20px;box-shadow:0 1px 3px rgba(0,0,0,.04)}
.filter-bar form{display:flex;gap:10px;align-items:center;flex-wrap:nowrap;width:100%}
.filter-bar input[type="text"]{flex:1 1 auto;min-width:0;padding:11px 16px 11px 42px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:13.5px;font-family:inherit;background:url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%2394a3b8' viewBox='0 0 16 16'><path d='M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z'/></svg>") no-repeat 14px center #fff;background-size:16px;color:#1e293b;transition:border-color .2s, box-shadow .2s}
.filter-bar input[type="text"]:focus{outline:none;border-color:<?= $accent_light ?>;box-shadow:0 0 0 4px rgba(20,184,166,.1)}

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
table{width:100%;border-collapse:collapse;font-size:13px;min-width:1050px;}
thead th{text-align:left;padding:12px 20px;background:#f8fafc;color:#64748b;font-weight:600;font-size:11px;text-transform:uppercase;letter-spacing:.5px;border-bottom:1px solid #f1f5f9;white-space:nowrap}
tbody td{padding:14px 20px;border-bottom:1px solid #f1f5f9;color:#334155;vertical-align:middle}
tbody tr:last-child td{border-bottom:none}
tbody tr:hover{background:#f8fafc}

.patient-name{font-weight:600;color:#0f172a}
.invoice-num{font-family:'Courier New',monospace;font-weight:700;color:<?= $accent ?>;font-size:12px}
.date-cell{font-size:12.5px;color:#64748b}
.paid-amount{color:#10b981;font-weight:700;font-size:13px}
.balance-amount{color:#dc2626;font-weight:800;font-size:12.5px}
.balance-amount.zero{color:#10b981}
.method-badge{display:inline-flex;align-items:center;gap:5px;padding:4px 10px;background:#f1f5f9;color:#475569;border-radius:20px;font-size:11px;font-weight:600}
.method-badge i{font-size:10px;color:<?= $accent ?>}

.service-cell{max-width: 320px;}
.svc-title{font-size:12.5px;font-weight:700;color:#0f172a;}
.service-cell .cat{font-size:10.5px;color:#64748b;margin-top:3px;display:inline-block;}
.service-cell .explanation {
    font-size: 11.5px; color: #475569; margin-top: 6px; padding: 6px 10px;
    background: #f0fdfa; border-left: 2px solid #14b8a6; border-radius: 0 4px 4px 0;
    line-height: 1.4;
}
.service-cell .explanation strong { color: #0f766e; }

.btn-receipt{display:inline-flex;align-items:center;gap:6px;padding:6px 12px;background:#64748b;color:#fff;border-radius:8px;font-size:11.5px;font-weight:600;text-decoration:none;transition:all .2s;white-space:nowrap}
.btn-receipt:hover{background:#475569;color:#fff;transform:translateY(-1px)}

.empty{text-align:center;padding:60px 20px;color:#94a3b8}
.empty i{font-size:44px;color:#cbd5e1;display:block;margin-bottom:14px}
.empty strong{color:#475569;display:block;margin-bottom:6px;font-size:15px}

.truncated-note{padding:14px 24px;background:#fffbeb;border-top:1px solid #fef3c7;font-size:12.5px;color:#92400e;display:flex;align-items:center;gap:9px}
.truncated-note i{color:#f59e0b}

@media(max-width:1100px){ .mini-stats{grid-template-columns:repeat(2,1fr)} }
@media(max-width:820px){
    .filter-bar form{flex-wrap:wrap}
    .filter-bar input[type="text"]{flex:1 1 100%}
}
@media(max-width:768px){
    .sidebar{transform:translateX(-100%)}.sidebar.open{transform:translateX(0)}
    .burger-btn{display:flex}.main{margin-left:0}
    .top-bar{padding:70px 18px 18px}
    .top-bar,.content{padding-left:18px;padding-right:18px}
    .top-bar h1{font-size:18px}
    .top-bar h1 .h-icon{width:36px;height:36px;font-size:15px}
    .top-bar .sub{font-size:11.5px;margin-left:0}
    .user-chip .info{display:none}
    .user-chip{padding:5px}
    .mini-stats{grid-template-columns:1fr}
    .filter-bar{padding:14px}
    .filter-bar form{flex-direction:column;align-items:stretch;gap:10px}
    .filter-bar input[type="text"]{width:100%}
    table{font-size:12px;min-width:1000px}
    thead th{padding:10px 12px;font-size:10px}
    tbody td{padding:12px 12px}
    .patient-banner{padding:14px 16px}
    .patient-banner .amounts{gap:14px}
}
@media(max-width:480px){
    .content{padding:0 14px}
    .filter-bar{padding:12px}
    .filter-bar input[type="text"]{padding:11px 14px 11px 40px;font-size:13px}
    .filter-bar .btn{padding:11px 16px;font-size:13px}
    table{font-size:11.5px;min-width:950px}
    thead th{padding:9px 10px;font-size:9.5px;letter-spacing:.3px}
    tbody td{padding:10px 10px}
    .invoice-num{font-size:11px}
    .patient-name{font-size:12.5px}
    .method-badge{padding:3px 8px;font-size:10px}
    .btn-receipt{padding:5px 10px;font-size:10.5px}
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
        <?php if ($is_frontdesk): ?>
            <li><a href="patients.php"><i class="fas fa-hospital-user"></i> <span>Patient Records</span></a></li>
            <li><a href="add_patient.php"><i class="fas fa-user-plus"></i> <span>Register & Bill</span></a></li>
        <?php endif; ?>
        <?php if ($is_billing): ?>
            <li><a href="patients.php"><i class="fas fa-hospital-user"></i> <span>Patient Records</span></a></li>
        <?php endif; ?>
        <li><a href="billing_records.php"><i class="fas fa-file-invoice-dollar"></i> <span>Billing Records</span></a></li>
        <li><a href="transaction_history.php" class="active"><i class="fas fa-clock-rotate-left"></i> <span>Transaction History</span></a></li>
    </ul>
    <div class="sidebar-footer">
        <a href="logout.php"><i class="fas fa-right-from-bracket"></i> <span>Logout</span></a>
    </div>
</aside>

<main class="main">

<div class="top-bar">
    <div>
        <h1><span class="h-icon"><i class="fas fa-clock-rotate-left"></i></span> Transaction History</h1>
        <div class="sub">Every recorded payment — one row per payment transaction.</div>
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
        <div class="ms-label">Total Collected</div>
        <div class="ms-value money">₱ <?= number_format($sum_amount, 2) ?></div>
    </div>
    <div class="mini-stat">
        <div class="ms-label">Transactions</div>
        <div class="ms-value"><?= number_format(count($transactions)) ?></div>
    </div>
    <div class="mini-stat">
        <div class="ms-label">Patients Served</div>
        <div class="ms-value"><?= number_format(count($patients)) ?></div>
    </div>
    <div class="mini-stat">
        <div class="ms-label">Showing</div>
        <div class="ms-value"><?= number_format(count($shown)) ?></div>
    </div>
</div>

<?php if ($patient_info): ?>
<div class="patient-banner">
    <div class="info-block">
        <div class="name"><?= htmlspecialchars($patient_info['patient_name']) ?></div>
        <div class="pid"><?= htmlspecialchars($patient_info['patient_id'] ?? '') ?></div>
    </div>
    <div class="amounts">
        <div class="item">
            <div class="lbl">Total Bill</div>
            <div class="val">₱ <?= number_format((float)$patient_info['total_amount'], 2) ?></div>
        </div>
        <div class="item">
            <div class="lbl">Paid</div>
            <div class="val" style="color:#10b981;">₱ <?= number_format((float)$patient_info['amount_paid'], 2) ?></div>
        </div>
        <div class="item balance">
            <div class="lbl">Balance</div>
            <div class="val">₱ <?= number_format(max(0, (float)$patient_info['total_amount'] - (float)$patient_info['amount_paid']), 2) ?></div>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="filter-bar">
    <form method="GET">
        <input type="text" name="q" placeholder="Search patient, service, or invoice #..." value="<?= htmlspecialchars($q) ?>">
        <?php if ($patient_db_id > 0): ?>
            <input type="hidden" name="patient_db_id" value="<?= (int)$patient_db_id ?>">
        <?php endif; ?>
        <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i> Search</button>
        <?php if ($q !== '' || $patient_db_id > 0): ?>
            <a href="transaction_history.php" class="btn btn-light"><i class="fas fa-xmark"></i> Clear</a>
        <?php endif; ?>
    </form>
</div>

<div class="table-card">
    <div class="table-header">
        <h3>
            <i class="fas fa-list"></i>
            Payment Transactions
            <span class="count-badge"><?= count($shown) ?></span>
        </h3>
    </div>

    <?php if (empty($shown)): ?>
        <div class="empty">
            <i class="fas fa-receipt"></i>
            <strong>No transactions found.</strong>
            <div><?= ($q !== '' || $patient_db_id > 0) ? 'Try adjusting your search.' : 'No payments have been recorded yet.' ?></div>
        </div>
    <?php else: ?>
        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th>Invoice</th>
                        <th>Payment Date</th>
                        <th>Patient</th>
                        <th>Service Details</th>
                        <th style="text-align:right;">Amount Paid</th>
                        <th style="text-align:center;">Method</th>
                        <th style="text-align:right;">Balance After</th>
                        <th style="text-align:center;">Receipt</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($shown as $tx):
                    $rec       = $tx['record'];
                    $exp_text  = resolve_patient_explanation($rec, $explanations);
                    $bal_after = $tx['balance_after'];
                    $method    = $tx['method'] ?: 'Cash';
                    $m_icon    = (stripos($method, 'gcash') !== false || stripos($method, 'maya') !== false) ? 'fa-mobile-screen-button' : 'fa-money-bill-wave';
                ?>
                    <tr>
                        <td><span class="invoice-num">#<?= str_pad($rec['id'], 5, '0', STR_PAD_LEFT) ?></span></td>
                        <td><span class="date-cell"><?= htmlspecialchars($tx['pay_date']) ?></span></td>
                        <td>
                            <div class="patient-name"><?= htmlspecialchars($rec['patient_name']) ?></div>
                            <?php if (!empty($rec['patient_id'])): ?>
                                <div style="font-size:11px;color:#94a3b8;margin-top:2px;"><?= htmlspecialchars($rec['patient_id']) ?></div>
                            <?php endif; ?>
                        </td>

                        <td>
                            <div class="service-cell">
                                <div class="svc-title"><?= htmlspecialchars($rec['service_description']) ?></div>
                                <div class="cat"><?= htmlspecialchars($rec['category'] ?? '—') ?></div>

                                <?php if ($exp_text !== ''): ?>
                                    <div class="explanation">
                                        <strong>Patient Understanding:</strong><br>
                                        <?= htmlspecialchars($exp_text) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </td>

                        <td style="text-align:right;">
                            <span class="paid-amount">₱ <?= number_format($tx['amount'], 2) ?></span>
                        </td>
                        <td style="text-align:center;">
                            <span class="method-badge">
                                <i class="fas <?= $m_icon ?>"></i>
                                <?= htmlspecialchars($method) ?>
                            </span>
                        </td>
                        <td style="text-align:right;">
                            <?php if ($bal_after === null): ?>
                                <span style="color:#94a3b8;font-size:12px;">—</span>
                            <?php else: ?>
                                <span class="balance-amount <?= $bal_after <= 0 ? 'zero' : '' ?>">
                                    ₱ <?= number_format($bal_after, 2) ?>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align:center;">
                            <a href="print_receipt.php?id=<?= (int)$rec['id'] ?>"
                               target="_blank"
                               class="btn-receipt"
                               title="View / Reprint Receipt">
                                <i class="fas fa-print"></i> Receipt
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if (count($transactions) > $display_limit): ?>
            <div class="truncated-note">
                <i class="fas fa-circle-info"></i>
                Showing the most recent <strong><?= $display_limit ?></strong> of <strong><?= count($transactions) ?></strong> transactions.
                Refine your search to narrow results.
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

</div>
</main>

<script>
function toggleSidebar(){
    document.getElementById('sidebar').classList.toggle('open');
    document.getElementById('burgerOverlay').classList.toggle('active');
}
</script>
</body>
</html>