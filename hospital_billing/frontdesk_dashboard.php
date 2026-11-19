<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php'); exit;
}
require_once 'config.php';
require_once 'explanation_helper.php';

$user_id   = $_SESSION['user_id'] ?? 0;
$user_name = $_SESSION['username'] ?? 'User';
$full_name = $_SESSION['full_name'] ?? $user_name;
$user_role = $_SESSION['role'] ?? 'User';

$r = strtolower(trim($user_role));
$is_frontdesk = strpos($r,'reception')   !== false
             || strpos($r,'frontdesk')   !== false
             || strpos($r,'front desk')  !== false
             || strpos($r,'front-desk')  !== false
             || strpos($r,'front office')!== false
             || in_array($r, ['receptionist','front desk receptionist','front desk reception','reception'], true);

if (!$is_frontdesk) { header('Location: login.php?msg=access_denied'); exit; }

$sidebar_brand = 'Hospital Billing';
$sidebar_sub   = 'RECEPTIONIST';
$accent        = '#0F766E';
$accent_light  = '#CCFBF1';
$dash_url      = 'frontdesk_dashboard.php';

/* Detect optional columns (safe on any schema) */
$has_amount_paid = false;
try {
    $col = $pdo->query("SHOW COLUMNS FROM billing_records LIKE 'amount_paid'")->fetch(PDO::FETCH_ASSOC);
    $has_amount_paid = !empty($col);
} catch (PDOException $e) {}

$has_explanation_col = false;
try {
    $col = $pdo->query("SHOW COLUMNS FROM billing_records LIKE 'patient_explanation'")->fetch(PDO::FETCH_ASSOC);
    $has_explanation_col = !empty($col);
} catch (PDOException $e) {}

/* STATS */
try { $total_patients = (int)$pdo->query("SELECT COUNT(*) FROM patients")->fetchColumn(); } catch (PDOException $e) { $total_patients = 0; }
try { $today_patients = (int)$pdo->query("SELECT COUNT(*) FROM patients WHERE DATE(created_at) = CURDATE()")->fetchColumn(); } catch (PDOException $e) { $today_patients = 0; }
try { $total_bills    = (int)$pdo->query("SELECT COUNT(*) FROM billing_records")->fetchColumn(); } catch (PDOException $e) { $total_bills = 0; }
try { $today_bills    = (int)$pdo->query("SELECT COUNT(*) FROM billing_records WHERE DATE(date) = CURDATE()")->fetchColumn(); } catch (PDOException $e) { $today_bills = 0; }

/* Recent — adapt to schema */
$recent_select = "id, patient_id, patient_name, category, service_description, total_amount, status, date"
               . ($has_explanation_col ? ", patient_explanation" : ", NULL AS patient_explanation")
               . ($has_amount_paid     ? ", amount_paid"          : ", 0 AS amount_paid");
try {
    $recent = $pdo->query("SELECT $recent_select FROM billing_records ORDER BY id DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $recent = []; }

/* Batch-fetch explanations for old records that predate the save-time fix */
$recent_services = [];
foreach ($recent as $rr) {
    if (!empty($rr['service_description'])) {
        $recent_services[$rr['service_description']] = true;
    }
}
$recent_explanations = fetch_patient_explanations($pdo, array_keys($recent_services));

/* Show welcome toast once per session */
$show_welcome_toast = false;
if (empty($_SESSION['dash_welcome_shown'])) {
    $_SESSION['dash_welcome_shown'] = true;
    $show_welcome_toast = true;
}

$initials = strtoupper(substr($full_name, 0, 2));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Hospital Billing Dashboard</title>
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
.sidebar-menu li a.active{background:<?= $accent ?>;color:#fff;box-shadow:0 4px 12px rgba(15,118,110,.25)}
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
.top-bar h1 .h-icon{width:40px;height:40px;border-radius:11px;background:<?= $accent ?>;color:#fff;display:flex;align-items:center;justify-content:center;font-size:17px;box-shadow:0 4px 12px rgba(15,118,110,.25)}
.top-bar .sub{font-size:12.5px;color:#64748b;margin-top:4px;margin-left:52px}
.top-bar-actions{display:flex;align-items:center;gap:15px}

.user-chip{display:flex;align-items:center;gap:10px;padding:6px 16px 6px 6px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:30px}
.user-chip .avatar{width:36px;height:36px;border-radius:50%;background:<?= $accent ?>;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:12px;flex-shrink:0}
.user-chip .info .name{font-size:13px;font-weight:600;color:#0f172a;line-height:1.2}
.user-chip .info .role{font-size:10.5px;color:<?= $accent ?>;font-weight:700;text-transform:uppercase;margin-top:2px}

.content{padding:0 32px}

.welcome{background:<?= $accent ?>;color:#fff;padding:28px 32px;border-radius:16px;margin-bottom:22px;position:relative;box-shadow:0 4px 15px rgba(15,118,110,.2)}
.welcome h2{font-size:22px;font-weight:800;margin-bottom:6px;position:relative;z-index:1}
.welcome p{font-size:13.5px;color:rgba(255,255,255,.9);position:relative;z-index:1;max-width:620px;line-height:1.55}

.stats-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:22px}
.stat-card{background:#fff;padding:20px 22px;border-radius:14px;border:1px solid #e2e8f0;transition:all .25s ease;position:relative;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.04)}
.stat-card:hover{transform:translateY(-3px);box-shadow:0 10px 25px rgba(15,118,110,.12);border-color:<?= $accent ?>}
.stat-card::before{content:'';position:absolute;top:0;left:0;width:4px;height:100%;background:<?= $accent ?>}
.stat-card.orange::before{background:#f59e0b}
.stat-card.green::before{background:#10b981}
.stat-card.blue::before{background:<?= $accent ?>}
.stat-label{font-size:11.5px;color:#64748b;font-weight:600;text-transform:uppercase;letter-spacing:.4px;margin-bottom:8px}
.stat-value{font-size:26px;font-weight:800;color:#0f172a;line-height:1.1}

.table-card{background:#fff;border-radius:14px;border:1px solid #e2e8f0;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.04)}
.table-header{padding:18px 24px;border-bottom:1px solid #f1f5f9;display:flex;justify-content:space-between;align-items:center}
.table-header h3{font-size:15px;font-weight:700;color:#0f172a;display:flex;align-items:center;gap:9px}
.table-header h3 i{color:<?= $accent ?>;font-size:15px}
.table-header a{font-size:12.5px;font-weight:600;color:<?= $accent ?>;text-decoration:none;display:inline-flex;align-items:center;gap:5px}
.table-header a:hover{text-decoration:underline}
table{width:100%;border-collapse:collapse;font-size:13px}
thead th{text-align:left;padding:12px 22px;background:#f8fafc;color:#64748b;font-weight:600;font-size:11px;text-transform:uppercase;letter-spacing:.5px;border-bottom:1px solid #f1f5f9}
tbody td{padding:14px 22px;border-bottom:1px solid #f1f5f9;color:#334155;vertical-align:middle}
tbody tr:last-child td{border-bottom:none}
tbody tr:hover{background:#f8fafc}
.patient-name,.amount{font-weight:600;color:#0f172a}

.service-cell { max-width: 350px; }
.svc-title { font-size: 12.5px; font-weight: 700; color: #0f172a; }
.service-cell .cat { font-size: 10.5px; color: #64748b; margin-top: 3px; display: inline-block; }
.service-cell .cat i { font-size: 9px; margin-right: 4px; color: #94a3b8; }
.service-cell .explanation {
    font-size: 11.5px; color: #475569; margin-top: 6px; padding: 6px 10px;
    background: #f0fdfa; border-left: 2px solid #14b8a6; border-radius: 0 4px 4px 0;
    line-height: 1.4;
}
.service-cell .explanation strong { color: #0f766e; }
.service-cell .explanation.empty {
    background: #f8fafc;
    border-left-color: #cbd5e1;
    color: #94a3b8;
    font-style: italic;
}

.status-badge{display:inline-flex;align-items:center;padding:4px 11px;border-radius:20px;font-size:11px;font-weight:600}
.status-badge.Paid{background:#d1fae5;color:#065f46}
.status-badge.Pending{background:#fef3c7;color:#92400e}
.status-badge.Processing{background:#fef3c7;color:#92400e}
.status-badge.Partial{background:#fef3c7;color:#92400e}
.empty-state{text-align:center;padding:50px 25px;color:#94a3b8}
.empty-state i{font-size:38px;color:#cbd5e1;display:block;margin-bottom:12px}

.toast-container { position: fixed; top: 24px; right: 24px; z-index: 9999; display: flex; flex-direction: column; gap: 12px; max-width: 380px; width: calc(100% - 48px); pointer-events: none; }
.toast { background: #fff; border-radius: 14px; box-shadow: 0 12px 40px rgba(15,23,42,.18); border: 1px solid #e2e8f0; padding: 16px 18px; display: flex; gap: 12px; align-items: flex-start; pointer-events: auto; animation: toastIn .4s cubic-bezier(.2,.9,.3,1.1); position: relative; overflow: hidden; }
.toast::before { content: ''; position: absolute; top: 0; left: 0; width: 4px; height: 100%; background: var(--toast-accent, #60a5fa); }
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

@media(max-width:1100px){.stats-grid{grid-template-columns:repeat(2,1fr)}}
@media(max-width:768px){
  .sidebar{transform:translateX(-100%)}.sidebar.open{transform:translateX(0)}
  .burger-btn{display:flex}.main{margin-left:0}
  .top-bar,.content{padding-left:18px;padding-right:18px}
  .stats-grid{grid-template-columns:1fr}
  .user-chip .info{display:none}.user-chip{padding:5px}
  .toast-container{top:16px;right:16px;left:16px;width:auto;max-width:none;}
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
                <div class="brand-name"><?= $sidebar_brand ?></div>
                <div class="brand-sub"><?= $sidebar_sub ?></div>
            </div>
        </div>
    </div>
    <ul class="sidebar-menu">
        <li><a href="frontdesk_dashboard.php" class="active"><i class="fas fa-gauge-high"></i> <span>Dashboard</span></a></li>
        <li><a href="patients.php"><i class="fas fa-hospital-user"></i> <span>Patient Records</span></a></li>
        <li><a href="add_patient.php"><i class="fas fa-user-plus"></i> <span>Register &amp; Bill</span></a></li>
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
        <h1><span class="h-icon"><i class="fas fa-gauge-high"></i></span> Hospital Billing Dashboard</h1>
        <div class="sub">Welcome back, <strong><?= htmlspecialchars($full_name) ?></strong>!</div>
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

<div class="welcome">
    <h2>Hello, <?= htmlspecialchars($full_name) ?>!</h2>
    <p>Register new patients and add their initial charges in one flow. Every visit creates a fresh bill under the same patient — no duplication.</p>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-label">Total Patients</div>
        <div class="stat-value"><?= number_format($total_patients) ?></div>
    </div>
    <div class="stat-card orange">
        <div class="stat-label">New Today</div>
        <div class="stat-value"><?= number_format($today_patients) ?></div>
    </div>
    <div class="stat-card green">
        <div class="stat-label">Total Bills</div>
        <div class="stat-value"><?= number_format($total_bills) ?></div>
    </div>
    <div class="stat-card blue">
        <div class="stat-label">Bills Today</div>
        <div class="stat-value"><?= number_format($today_bills) ?></div>
    </div>
</div>

<div class="table-card">
    <div class="table-header">
        <h3><i class="fas fa-clock-rotate-left"></i> Recent Billing Records</h3>
        <a href="billing_records.php">View all <i class="fas fa-arrow-right"></i></a>
    </div>
    <?php if (empty($recent)): ?>
        <div class="empty-state"><i class="fas fa-file-invoice"></i>No billing records yet.</div>
    <?php else: ?>
        <div style="overflow-x:auto;">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Patient</th>
                        <th>Service Details</th>
                        <th style="text-align:right;">Amount</th>
                        <th style="text-align:center;">Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($recent as $rec):
                    $paid_amt = (float)($rec['amount_paid'] ?? 0);
                    $balance  = (float)$rec['total_amount'] - $paid_amt;
                    $display_status = ($paid_amt > 0 && $balance > 0) ? 'Partial' : $rec['status'];
                ?>
                    <tr>
                        <td><?= date('M d, Y', strtotime($rec['date'])) ?></td>
                        <td>
                            <div class="patient-name"><?= htmlspecialchars($rec['patient_name']) ?></div>
                            <?php if (!empty($rec['patient_id'])): ?>
                                <div style="font-size:11px;color:#94a3b8;margin-top:2px;"><?= htmlspecialchars($rec['patient_id']) ?></div>
                            <?php endif; ?>
                        </td>

                        <td>
                            <?= render_service_details_cell($rec, $recent_explanations) ?>
                        </td>

                        <td style="text-align:right;"><span class="amount">₱ <?= number_format($rec['total_amount'],2) ?></span></td>
                        <td style="text-align:center;"><span class="status-badge <?= htmlspecialchars($display_status) ?>"><?= htmlspecialchars($display_status) ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

</div>
</main>

<div class="toast-container" id="toastContainer"></div>

<script>
function toggleSidebar(){
    document.getElementById('sidebar').classList.toggle('open');
    document.getElementById('burgerOverlay').classList.toggle('active');
}

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
    setTimeout(() => {
        const closeBtn = toast.querySelector('.toast-close');
        if (closeBtn) dismissToast(closeBtn);
    }, duration);
}

function dismissToast(btn) {
    const toast = btn.closest('.toast');
    if (!toast || toast.classList.contains('closing')) return;
    toast.classList.add('closing');
    setTimeout(() => toast.remove(), 300);
}

<?php if ($show_welcome_toast): ?>
document.addEventListener('DOMContentLoaded', function () {
    showToast(
        'Welcome back!',
        <?= json_encode('You are signed in as ' . $full_name . '. Have a productive shift!') ?>,
        'success',
        4500
    );
});
<?php endif; ?>
</script>
</body>
</html>